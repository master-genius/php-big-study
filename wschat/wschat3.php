<?php
class wsChat
{
    private $server;
    private $server_pid;
    private $auth_cache;
    private $sock_head = 'user_sock_';

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
        $token = $this->auth_cache->get('token_'.$fd);
        return $this->auth_cache->get($token);
    }

    //bind token and sock id
    protected function bindTokenConn($token, $fd)
    {
        $this->auth_cache->set('token_'.$fd, $token);
    }

    protected function checkUser($token)
    {
        return ($this->auth_cache->get($token)?true:false);
    }

    protected function logout($server,$fd)
    {
        $username = $this->getUser($fd);
        //通过token和连接套接字的关联获取user_token并删除
        $this->auth_cache->delete($this->auth_cache->get('token_'.$fd),0);
        $this->auth_cache->delete($username,0);
    }

    public function format_usermsg($from,$to,$msg,$msg_type,$msg_time)
    {
        return json_encode([
            'from'      => $from,
            'to'        => $to,
            'msg'       => $msg,
            'msg_type'  => $msg_type,
            'time'      => $msg_time
        ]);
    }

    public function format_groupmsg($msg,$msg_type,$msg_time,$from)
    {
        return json_encode([
            'from'     => $from,
            'msg'      => $msg,
            'msg_time' => $msg_time,
            'msg_type' => $msg_type
        ]);
    }

    public function format_sysmsg($msg, $type, $to, $msg_type, $errcode=0)
    {
        switch ($type) {
            //system message: error,notice
            case 'server':
                $fmsg['msg_source'] = 'server';
                $fmsg['msg'] = $msg;
                $fmsg['error'] = $errcode;
                break;
            //system push
            case 'push':
                $fmsg['msg_source'] = 'push';
                $fmsg['msg_type'] = $msg_type;
                $fmsg['msg'] = $msg;
                $fmsg['msg_time'] = time();
                break;
            default:;
        }

        return  json_encode($fmsg);
    }


    public function on_message($server, $req)
    {
        $data = json_decode($req->data,true);
        $msg = (isset($data['msg'])?$data['msg']:'');
        if (empty($msg)) {
            return ;
        }
        //check if logout
        if ($msg=='//logout') {
            $this->logout($req->fd);
            $server->close($req->fd);
            return ;
        }

        $send_msg = $this->format_groupmsg(
                            $msg,
                            'text',
                            time(),
                            $this->getUserByConn($req->fd)
                       );

        $keys = $this->mcache->getAllKeys();
        $this->mcache->getDelayed($keys);
        $key_vals = $this->mcache->fetchAll();
        foreach ($key_vals as $kv) {
            if ($kv['value']==$req->fd) {
                continue;
            }
            $server->push($kv['value'],$send_msg);
        }
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
                            'you need to login',
                            'server',
                            '',
                            'text',
                            -1
                        )
            );
            $server->close($req->fd);
        }

        $this->mcache->set($this->sock_head.$req->fd, $req->fd);
        $this->bindTokenConn($req->get['user_token'], $req->fd);
        
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
        $this->mcache->delete($this->sock_head.$fd,0);
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

