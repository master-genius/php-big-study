<?php

class wsChat
{
    
    private $sock_list = [];
    private $server;
    private $mcache;
    private $sock_head = 'user_sock_';

    function __construct()
    {
        $this->mcache = new Memcached('websocket_pool');
        $this->mcache->addServer('localhost',11211);
        $this->server = new swoole_websocket_server('localhost',9876);
        $this->server->set([
            'daemonize' => 1
        ]);
    }

    public function on_message($server, $req) {
        $data = json_decode($req->data,true);
        $msg = (isset($data['msg'])?$data['msg']:'');
        if (empty($msg)){
            return ;
        }
        $send_msg = [
            'from_id'=>$req->fd,
            'msg'=>$msg,
            'time'=>time(),
        ];
        if ($msg==='::--phpmaster--::') {
            $send_msg['msg'] = '<p style="font-size:234%;">I am the programming master.</p>';
        }
        elseif ($msg === '::clear@') {
            $send_msg['msg'] = '--//--clear';
        }
        elseif ($msg === '::kill@') {
            $send_msg['msg'] = '--//--kill';
        }

        $keys = $this->mcache->getAllKeys();
        $this->mcache->getDelayed($keys);
        $key_vals = $this->mcache->fetchAll();
        foreach ($key_vals as $kv) {
            if ($kv['value']==$req->fd) {
                continue;
            }
            $server->push($kv['value'],json_encode($send_msg));
        }
    }

    public function on_shutdown($server) {
        $this->mcache->deleteMulti($this->mcache->getAllKeys());
        $this->mcache->quit();
    }

    public function on_open($server, $req) {
        $this->mcache->set($this->sock_head.$req->fd, $req->fd);
        $sys_msg = [
            'msg_source'=>'server',
            'msg'=>'you are login at '.$req->fd
        ];

        $server->push($req->fd,json_encode($sys_msg));
    }

    public function on_close($server,$fd) {
        $this->mcache->delete($this->sock_head.$fd,0);
    }

    public function start_server() {
        $this->server->on('open',[$this,'on_open']);

        $this->server->on('message',[$this,'on_message']);
        
        $this->server->on('close',[$this,'on_close']);

        $this->server->on('shutdown',[$this,'on_shutdown']);
        
        $this->server->start();
    }
}

(new wsChat())->start_server();

