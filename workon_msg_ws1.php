<?php

namespace workon;
/*
    本程序实现分布式第一步：仅仅是一些机器的相互通信，没有分组。
    能够支持的机器数量不会太大，几台到上百。过多的服务器导致相互
    连接数量太大，对用户使用有所影响。
*/

calss workon_msgws
{

    //服务器节点列表，可以暂时使用本地程序存储，也可以存储到数据库从数据库获取。
    /*
        文件分发形式：这种情况，在初始化的时候直接从本地文件获取，
        服务器支持文件推送用于动态更新
    */
    private $server_table = [
        ['ip'=>'127.0.0.1','token'=>'master'],
    ];


    //连接到其他服务器的客户端列表
    private $client_list = [
        
    ];

    /*
        服务器角色：
        server 普通服务器
        group_leader 组长服务器
        total_cache  组内总缓存服务器
    */
    private $server_role = 'server';

    //连接到组长服务器的客户端连接
    private $group_leader_client = '';

    /*
        对于更大规模的分布式，缓存聚合器使用集群的方式
        同时当前服务器保存一个缓存聚合器的列表，通过一种算法把用户
        映射过去。
        对于更大的集群，也可以使用另一种实现方式：
            当前服务器只保存一些用于查询服务的服务器地址，
            这些服务器进行查询服务器，把缓存集群隔离出来。
        目前的实现仅仅使用了一台缓存聚合器，不过可以比较容易的进行分布式处理。
        对于单机情况，使用缓存聚合器指向本地即可。
    */
    //缓存聚合器IP地址
    private $total_cache_ip = 'localhost';

    private $local_cache = '';
    private $total_cache = '';

    private $port = 9876;

    //本机IP
    private $self_ip = $_SERVER['SERVER_ADDR'];

    //本机服务器token
    private $server_token = 'master';

    //消息模板
    private $send_msg_template = [
        
        'user_verify_failed'=>'{"err_code":%d,"err_msg":"%s"}',

        'msg_verify_error'=>'{"err_code":%d,"err_msg":"%s"}',

        'text_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"text","msg_time":%d,"content":"%s"}',

        'image_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"image","msg_time":%d,"image_id":"%s"}',

        'audio_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"audio","msg_time":%d,"audio_id":"%s"}',

        'video_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"video","msg_time":%d,"video_id":"%s"}',

        'note_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"note","msg_time":%d,"note_id":"%s"}',

        /*
           以下是服务器消息模板
         */
        
        //转发消息
        'msg_transmit'=>'{"server_id":"%s","action":"transmit","user_msg":"%s"}',

        //组消息转发
        'group_msg_transmit'=>'{"server_id":"%s","action":"group_msg_transmit","user_msg":"%s"}',
        
        //
    ];

    public function __construct()
    {
        $this->local_cache = new Memcached('local');
        //如果单机部署，使缓存聚合器指向本地
        if ($this->total_cache_ip == 'localhost' || $this->total_cache_ip=='127.0.0.1') {
            $this->total_cache = $this->local_cache;
        } else {
            $this->total_cache = new Memcached('total');
        }

        $this->local_cache->addServer('localhost',11211);
        
        if ($this->local_cache!==$this->total_cache) {
            $this->total_cache->addServer($this->total_cache_ip,11211);
        }

        $this->server = new swoole_websocket_server('localhost', $this->port);
        
        //初始化客户端连接
        $this->init_client_connect();

        $this->server->on('open',function($server, $req){
            /*
                $req携带get参数，通过ticket与msg_token进行验证
            */
            if (isset($req->get['ticket']) && isset($req->get['msg_token'])) {
                //用户连接验证
                $r = $this->user_verify($req->get['ticket'],$req->get['msg_token'], $cnn->fd);
                if (!$r) {
                    //验证失败则关闭连接
                    $server->push(sprintf($this->msg_template['user_verify_failed']));
                    $server->close($req->fd);
                }
            } elseif (isset($req->get['sign'])) {
                //服务器连接验证
                $r = $this->server_verify($req->get['sign'],$req->server['remote_addr'], $cnn->fd);
                if (!$r) {
                    $server->close();
                }
            }
        });

        $this->server->on('message',function($server,$cnn){
            /*
                消息处理，根据不同类型交给不同方法去处理
            */
            $msg = json_decode($cnn->data,true);
            if (!$msg) {
                $this->add_illegal_msg($cnn->fd);
            } elseif (isset($msg['user_id']) && isset($msg['msg_type'])) {
                //普通用户消息处理
                $this->msg_handle($server,$cnn,$msg);
            } elseif (isset($msg['server']) && isset($msg['action'])) {
                //服务器之间消息通信处理
                $this->server_msg_handle($server, $cnn, $msg);

            } else {
                $this->add_illegal_msg($cnn->fd);
            }

        });

        $this->server->on('close',function($server, $fd) {
            $userid = $this->local_cache->get('user_'.$fd);
            $this->local_cache->delete('user_'.$userid,0);
            $this->local_cache->delete('user_'.$fd,0);
            $this->total_cache->delete('user_'.$userid,0);
            $this->broadcast_user_outline($userid);
        });

    }
    
    public function start_server()
    {
        $this->server->start();
    }

    private function msg_handle($server,$cnn,$msg)
    {
        //消息完整性检查
        $r = $this->user_msg_verify($msg);
        if (!$r) {
            $server->push(sprintf($this->msg_template['msg_verify_error'],$errcode,
                        'message verify failed.'));
            return false;
        }

        switch ($msg['msg_type']) {
            case 'text':
                $server->push(
                    sprintf($this->msg_template['text_msg'],);
                );
                break;
            case 'image':
                break;
            case 'audio':
                break;
            case 'video':
                break;
            case 'note':
                break;
            default:
                $server->push(sprintf($this->msg_template['unknow_message_type'],
                            $errcode,'unknow message'));

        }
    }

    private function server_msg_handle($server,$cnn,$msg)
    {
        switch ($msg['action']) {
            case 'msg_transmit':
                if ($this->local_cache)
                break;
            case 'group_msg_transmit':
                
                break;
            case '':
        }
    }

    private function get_group_user()
    {
        $user = $this->total_cache();
    }

    private function broadcast_user_outline($userid)
    {
        foreach ($this->client_list as $cli) {
            $cli->push('{"server_ip":"'.$this->self_ip.
                        '","action":"user_outline","data":{"user_id":"'.
                        $userid.'"}}');
        }
    }

    private function broadcast_user_online($userid)
    {
        foreach ($this->client_list as $cli) {
            $cli->push('');
        }
    }

    private function user_verify($ticket, $token, $fd)
    {
        /*
            用户验证使用已获取的ticket以及token去ticket服务器获取信息,
            $user['info']:[
                'username','email','headimg','user_id','self_signature'
            ]
        */
        
        $user = $this->ticket_server->get('ticket_' . $ticket);
        if (empty($user)) {
            return false;
        } elseif ($user['token'] !== $token) {
            return false;
        }
        $this->local_cache->add('user_'. $user['user_id'], $user);
        $this->local_cache->add('user_'. $fd, $user['user_id']);
        $this->total_cache->add('user_'. $user['user_id'], $user);
        return true;
    }

    private function server_verify($sign, $client_ip, $fd)
    {
        $self_sign = md5($this->self_ip . $this->server_token);
        if ($self_sign !== $sign) {
            return false;
        }
        //记录服务器客户端连接到缓存
        $this->local_cache->add('server_client_'.$fd, $fd);
        return true;
    }
    
    private function init_client_connect()
    {
        /*
            根据组内服务器列表进行初始化，连接到其他客户端并保存连接信息。
        */
        $verify_str = '';
        foreach ($this->server_table as $sv) {
            $verify_str = '?sign=' . md5($sv['ip'] . $sv['token']);
            $this->client_list['cli_'.$sv['ip']] = new swoole_http_client($sv . $verify_str,$this->port);

            $this->client_list['cli_'.$sv['ip']]->on('message',function($cli,$frame){

            });

            $this->client_list['cli_'.$sv['ip']]->upgrade('/',function($cli){
                //验证失败则关闭连接
            });

        }
        //组长服务器之间的相互连接
        if ('group_leader' !== $this->server_role) {
            return true;
        }

        foreach ($this->leader_table as $sv) { 
            $verify_str = '?sign=' . md5($sv['ip'] . $sv['token']);
            $this->client_list['cli_'.$sv['ip']] = new swoole_http_client($sv . $verify_str,$this->port);

            $this->client_list['cli_'.$sv['ip']]->on('message',function($cli,$frame){

            });

            $this->client_list['cli_'.$sv['ip']]->upgrade('/',function($cli){
                //验证失败则关闭连接
            });
        }

        //连接到组长机
        $verify_str = '?sign=' . md5($this->group_leader['ip'] . $this->group_leader['token']);
        $this->group_leader_client = new swoole_http_client($this->group_leader['ip'] . $verify_str,
                                $this->port);
        
        $this->group_leader_client->on('message',function($cli){

        });

        $this->group_leader_client->upgrade('/',function($cli){
            
        });

        return true;
    }

    private function format_msg_template($msg_type,$msg)
    {
        switch ($type) {
            case 'text':
                return sprintf('{"user_id":"%s","to_user":"%s","msg_type":"text","msg_time":%d,"content":"%s"}',);
        }
    }

}
