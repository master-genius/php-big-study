<?php

class wsPush
{
    private $server;
    private $mcache;
    private $conn_head = 'user_sock_';
    private $client_index = 'push_manager';
    private $port = 4567;

    function __construct()
    {
        $this->mcache = new Memcached('websocket_push');
        $this->mcache->addServer('localhost',11211);
        $this->server = new swoole_websocket_server('localhost',$this->port);
        $this->server->set([
            'daemonize'=>0
        ]);
    }

    public function on_message($server, $cnn) {
        
        if ($cnn->fd == $this->mcache->get($this->client_index)) {
            //start push
            if(!empty($cnn->data)) {
                $keys = $this->mcache->getAllKeys();
                $this->mcache->getDelayed($keys);
                $key_vals = $this->mcache->fetchAll();
                foreach ($key_vals as $kv) {
                    $server->push($kv['value'],$cnn->data);
                }
            }
        }

    }

    public function on_shutdown($server) {
        $this->mcache->deleteMulti($this->mcache->getAllKeys());
        $this->mcache->quit();
    }

    public function on_open($server, $req) {
        //var_dump($req);
        $tmp_key = $this->conn_head . $req->fd;
        if ($req->server['path_info'] == '/push_client/1001001') {
            $tmp_key = $this->client_index;
        }
        
        $this->mcache->set($tmp_key, $req->fd);
    }

    public function on_close($server,$fd) {
        $this->mcache->delete($this->conn_head.$fd,0);
    }

    public function start_server() {
        $this->server->on('open',[$this,'on_open']);

        $this->server->on('message',[$this,'on_message']);
        
        $this->server->on('close',[$this,'on_close']);

        $this->server->on('shutdown',[$this,'on_shutdown']);
        
        $this->server->start();
        //$this->real_start();
    }

    public function real_start() {
        $pid = pcntl_fork();
        if ($pid<0) {
            exit(-1); 
        }
        elseif ($pid==0) {
            //start a client
            sleep(1);
            include 'wspush_client.php';
        }
        elseif ($pid > 0) {
            $this->server->start();
        }

    }
}

(new wsPush())->start_server();

