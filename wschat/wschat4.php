<?php
class wsChat
{
    private $server;
    private $server_pid;
    private $auth_cache;
    private $sock_head = 'user_sock_';
    private $redis_out = '';
    //客户端验证密码
    private $client_key = '1001001';

    function __construct()
    {
        $this->server_pid = posix_getpid();
        $this->auth_cache = new Memcached('auth');
        $this->auth_cache->addServer('localhost',11211);

        $this->server = new swoole_websocket_server('localhost',7654);
        $this->server->set([
            'daemonize' => 1
        ]);
    }

    //get username by token_$fd
    public function getUserByConn($fd)
    {
        $token = $this->auth_cache->get('sock_'.$fd);
        $seri_info = $this->auth_cache->get('user_'.$token);
        return unserialize($seri_info);
    }

    //bind token and sock id
    public function bindTokenSock($token, $fd)
    {
        /*
            绑定token和sock连接，此操作要获取之前已保存的token => [user info]信息
            通过sock_$fd可以找到token，通过token可以找到用户信息，为了可以快速转发消息
            在用户发送消息时，要能够通过username直接获取sock连接（如果存在）。
            这要求必须要存储username到sock的映射关系
        */
        $this->auth_cache->set('sock_'.$fd, $token);
        $userinfo = $this->auth_cache->get($token);
        $userinfo = unserialize($userinfo);
        $this->auth_cache->set($userinfo['username'], $fd);
        //$this->auth_cache->set($token, serialize($userinfo));
    }

    public function getSockByUsername($username)
    {
        $sock = $this->auth_cache->get($username);
        return ($sock?$sock:false);
    }

    public function checkUser($token)
    {
        return ($this->auth_cache->get($token)?true:false);
    }
    
    /*
        如果通过用户名没有获取连接如何操作：
            一种情况是分布式系统用户连接在另一台服务器
            另一种情况是单机系统离线消息处理
        此函数的操作仅仅是把消息放入消息队列，监听消息队列的
        进程进行处理
    */
    public function handleNotSock($from, $to, $msg)
    {
        $this->redis_out = new Redis();
        $this->sockect('127.0.0.1',6379);
        $send_data = [
            'from' => $from,
            'to' => $to,
            'type' => 'nosock',
            'msg' => $msg
        ];
        $this->publish('outline_or_distribute', serialize($send_data));
    }

    public function logout($server,$fd)
    {
        $token = $this->auth_cache->get('sock_' . $fd);
        $userinfo = serialize($this->auth_cache->get($token));
        if ($userinfo) {
            $this->auth_cache->delete($userinfo['username'],0);
        }
        $this->auth_cache->delete('sock_'.$fd,0);
        $this->auth_cache->delete($token,0);
    }
    //格式化用户消息
    public function format_usermsg($from, $to, $msg_type, $msg, $msg_time=0)
    {
        return json_encode([
            'from'      => $from,
            'to'        => $to,
            'msg'       => $msg,
            'msg_type'  => $msg_type,
            'time'      => (($msg_time==0)?time():$msg_time)
        ],JSON_UNESCAPED_UNICODE);
    }

    //格式化系统推送消息
    public function format_sysmsg($to, $push_type, $msg, $errcode=0)
    {
        /*push_type: error , url , img , text*/
        $sysmsg = [
            'msg_type' => 'server_push',
            'to' => $to,
            'msg' => $msg,
            'errcode' => $errcode,
            'push_type' => $push_type
        ];

        return  json_encode($sysmsg,JSON_UNESCAPED_UNICODE);
    }

    public function sys_errmsg($to,$msg) {
        return $this->format_sysmsg($to, 'error', $msg, -1);
    }

    public function parsemsg($data)
    {
        $org_msg = json_decode($data, true);
        if (empty($org_msg)) {
            return false;
        }
        if (!isset($org_msg['msg_type'])) {
            return false;
        }
        else{
            if (false===array_search($org_msg['msg_type'], ['text','image','note'])) {
                return false;
            }
        }
        if (
                !isset($org_msg['msg']) 
                || 
                !isset($org_msg['from']) 
                || 
                !isset($org_msg['to'])
           ) {
            return false;
        }

        return $org_msg;
    }

    /*
        转发消息的函数，处理过程要区分是普通用户之间转发还是群发消息
        群发消息涉及到批量推送，还要考虑离线消息处理
        群发消息并不在此处处理，群发消息是通过API发送并交给消息队列的
        监听进程处理，此进程处理后发送给消息服务处理程序
    */
    public function transMsg($server, $req, $msg)
    {

        $sock = $this->getSockByUsername($msg['to']);
        if (empty($sock)) {
            $msg['msg_time'] = time();
            $server->push($sock, json_encode($msg, JSON_UNESCAPED_UNICODE));
        }
        else{
            $this->handleNotSock($msg['from'], $msg['to'], $msg);
        }
    }

    public function on_message($server, $req)
    {
        $data = json_decode($req->data,true);
        $msg = $this->parsemsg($req->data);
        if (empty($msg)) {
            $server->push($req->fd, $this->sys_errmsg($req->fd, 'Error: message type wrong'));
            return ;
        }
        //check if logout
        if ($msg=='//logout') {
            $this->logout($req->fd);
            $server->close($req->fd);
            return ;
        }
        $this->transMsg($server, $req, $msg);
    }

    public function on_shutdown($server)
    {
        $this->auth_cache->deleteMulti($this->auth_cache->getAllKeys());
        $this->auth_cache->quit();
    }

    public function on_open($server, $req)
    {
        if (!$this->checkUser($req->get['user_token'])) {

            $server->push(
                $req->fd,
                $this->sys_errmsg($req->fd, 'not login')
            );
            $server->close($req->fd);
            return ;
        }

        $this->bindTokenSock($req->get['user_token'], $req->fd);
        $server->push($req->fd, 'success');
    }

    /*
        onclose事件处理要先通过连接获取token，通过token获取用户信息
        然后删除用户名对应的连接sock值。
    */
    public function on_close($server,$fd)
    {
        $token = $this->auth_cache->get('sock_' . $fd);
        $userinfo = serialize($this->auth_cache->get($token));
        if ($userinfo) {
            $this->auth_cache->delete($userinfo['username'],0);
        }
    }

    public function start_server()
    {
        $this->server->on('open',[$this,'on_open']);

        $this->server->on('message',[$this,'on_message']);
        
        $this->server->on('close',[$this,'on_close']);

        $this->server->on('shutdown',[$this,'on_shutdown']);
        
        $this->server->start();
    }
}

(new wsChat())->start_server();

