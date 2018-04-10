<?php

class WebSocket
{
    private $sock_list = [];
    private $server;
    function __construct()
    {
        $this->server = new swoole_websocket_server('localhost',9876);
        
        $this->server->on('open',function($server, $req){
            echo 'websocket open by ' . $req->fd . "\n";
        });

        $this->server->on('message',function($server,$req){
            echo "recieved " . $req->data . " from " . $req->fd . "\n";
            $server->push($req->fd, mt_rand(0,1024));
        });

        $this->server->on('close',function($server, $fd){
            echo $fd . " closed\n";
        });

        $this->server->start();

    }
}

new WebSocket();

