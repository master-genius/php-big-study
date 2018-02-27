<?php

class braveWebSocket
{
    
    private $sock_list = [];
    private $server;
    private $server_pid;
    private $mcache;
    function __construct()
    {
        $this->server_pid = posix_getpid();
        $this->mcache = new Memcached('websocket_pool');
        $this->mcache->addServer('localhost',11211);
        $this->server = new swoole_websocket_server('localhost',9876);
        
        $this->server->on('open',function($server, $req){
            var_dump($req);
            echo 'websocket open by ' . $req->fd . "\n";
            $this->mcache->set('cnn_'.$req->fd,$req->fd);
            $sys_msg = [
                'id'=>0,
                'msg'=>$this->server_pid . ':hello, you are login at '.$req->fd
            ];

            $server->push($req->fd,json_encode($sys_msg));
        });

        $this->server->on('message',function($server,$cnn){
            echo "recieved " . $cnn->data . " from " . $cnn->fd . "\n";
            $jmsg = json_decode($cnn->data,true);
            $send_msg = (isset($jmsg['msg'])?$jmsg['msg']:'');
            $token = (isset($jmsg['token'])?$jmsg['token']:null);

            if ('w1001001'===$token) {
                if ($jmsg['msg']==='sendall') {
                    $server_msg = [
                        'id'=>0,
                        'msg'=>''
                    ];
                    $client_list = $server->connection_list(0,100);
                    foreach ($client_list as $fd) {
                        if ($fd==$cnn->fd) {
                            continue;
                        }
                        $server_msg['msg'] =  time() . ': hello<br>';
                        $server->push($fd,json_encode($server_msg));
                    }
                }
            } else {
                $json_msg = [
                    "id"=>$cnn->fd,
                    "msg"=>$send_msg
                ];
            
                $server->push($this->mcache->get('cnn_'.$jmsg['id']),json_encode($json_msg));
            }

            if (isset($jmsg['id']) && $this->mcache->get('cnn_'.$jmsg['id'])) {
                
            }

        });

        $this->server->on('close',function($server, $fd){
            $this->mcache->delete('cnn_'.$fd,0);
            echo $fd . " closed\n";
        });

        $this->server->start();

    }
}

new braveWebSocket();

