<?php
class wsChat
{
    private $sock_list = [];
    private $server;
    private $server_pid;
    private $mcache;
    private $auth_cache;
    private $conn_head = 'user_cnn_';
    private $redis_out = '';

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
    protected function getUserByConn($fd)
    {
        $token = $this->auth_cache->get('sock_'.$fd);
        $seri_info = $this->auth_cache->get('user_'.$token);
        return unserialize($seri_info);
    }

    //bind token and sock id
    protected function bindTokenSock($token, $fd)
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

    protected function getSockByUsername($username)
    {
        $sock = $this->auth_cache->get($username);
        return ($sock?$sock:false);
    }

    protected function checkUser($token)
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
    protected function handleNotSock($from, $to, $msg)
    {
        $this->redis_out = new Redis();
        $this->connect('127.0.0.1',6379);
        $send_data = [
            'from' => $from,
            'to' => $to,
            'type' => 'nosock',
            'msg' => $msg
        ];
        $this->publish('outline_or_distribute', serialize($send_data));
    }

    protected function logout($server,$fd)
    {
        $username = $this->getUser($fd);
        //通过token和连接套接字的关联获取user_token并删除
        $this->auth_cache->delete($this->auth_cache->get('token_'.$fd),0);
        $this->auth_cache->delete($username,0);
    }
    //格式化用户消息
    public function format_usermsg($from,$to,$msg,$msg_type,$msg_time)
    {
        return json_encode([
            'from'      => $from,
            'to'        => $to,
            'msg'       => $msg,
            'msg_type'  => $msg_type,
            'time'      => $msg_time
        ],JSON_UNESCAPED_UNICODE);
    }
    //格式化组群发消息
    public function format_groupmsg($msg,$msg_type,$msg_time,$from)
    {
        return json_encode([
            'from'     => $from,
            'msg'      => $msg,
            'msg_time' => $msg_time,
            'msg_type' => $msg_type
        ],JSON_UNESCAPED_UNICODE);
    }
    //格式化系统推送消息
    public function format_sysmsg($msg, $to, $push_type, $errcode=0)
    {
        $sysmsg = [
            'msg_type' => 'server_push',
            'to' => $to,
            'msg' => $msg,
            'errcode' => $errcode,
            'push_type' => $push_type
        ];

        return  json_encode($sysmsg,JSON_UNESCAPED_UNICODE);
    }

    protected function parsemsg($data)
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
            $server->push($req->fd, $this->format_sysmsg());
            return ;
        }
        //check if logout
        if ($msg=='//logout') {
            $this->logout($req->fd);
            $server->close($req->fd);
            return ;
        }
        $this->transMsg($server, $req, $msg);
        /*
        $send_msg = $this->format_groupmsg(
                            $msg,
                            'text',
                            time(),
                            $this->getUserByConn($cnn->fd)
                       );

        $keys = $this->mcache->getAllKeys();
        $this->mcache->getDelayed($keys);
        $key_vals = $this->mcache->fetchAll();
        foreach ($key_vals as $kv) {
            if ($kv['value']==$cnn->fd) {
                continue;
            }
            $server->push($kv['value'],$send_msg);
        }
        */
    }

    public function on_shutdown($server)
    {
        $this->mcache->deleteMulti($this->mcache->getAllKeys());
        $this->mcache->quit();
        $this->auth_cache->deleteMulti($this->auth_cache->getAllKeys());
        $this->auth_cache->quit();
    }

    public function on_open($server, $req)
    {
        if (!$this->checkUser($req->get['user_token'])) {

            $server->push(
                $req->fd,
                $this->format_sysmsg(
                            'not login',
                            $req->fd,
                            'sys_error',
                            -1
                        )
            );
            $server->close($req->fd);
            return ;
        }

        $this->bindTokenSock($req->get['user_token'], $req->fd);
        
        $sys_msg = $this->format_sysmsg(
                            'you are login at '.  $req->fd,
                            'server',
                            '',
                            'text'
                        );

        $server->push($req->fd,$sys_msg);
    }

    public function on_close($server,$fd)
    {
        $this->mcache->delete($this->conn_head.$fd,0);
        $this->auth_cache->delete('token_'.$fd,0);
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

