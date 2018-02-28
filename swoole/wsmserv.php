<?php
class phpWebSocket
{
    private $server;

    public function __construct()
    {
        $this->server = new swoole_websocket_server('localhost',9876);
        
        $this->server->on('open',function($server, $req){
            $server->push($req->fd,"Hey. $req->fd");
        });

        $this->server->on('message',function($server,$cnn){
            $server->push($cnn->fd,$cnn->data);
        });

        $this->server->on('close',function($server, $fd){
            echo $fd . " closed\n";
        });

        $this->server->start();

    }
}

new phpWebSocket();

