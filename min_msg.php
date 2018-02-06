<?php

namespace workon;

/*
    双子模式：两台机器相互可通信，但是不需要使用缓存聚合器，直接进行消息转发。
    如果当前机器没有此用户连接，则直接转发到另一台机器，另一台机器如果检查存在
    连接则发送消息，否则直接丢弃。


*/

calss workon_msgws
{

    //服务器节点列表，可以暂时使用本地程序存储，也可以存储到数据库从数据库获取。
    /*
        文件分发形式：这种情况，在初始化的时候直接从本地文件获取，
        服务器支持文件推送用于动态更新
    */
    private $server_table = [
        ['ip'=>'192.168.124.3','token'=>'master'],
    ];


    //连接到其他服务器的客户端列表
    private $client_list = [
        
    ];

    private $leader_server = ['127.0.0.1','master'];

    /*
        服务器角色：
        server 普通服务器
        group_leader 组长服务器
        total_cache  组内总缓存服务器
    */
    private $server_role = 'server';

    //连接到组长服务器的客户端连接
    private $group_leader_client = '';

    private $local_cache = '';

    private $port = 9876;

    private $self_ip = $_SERVER['SERVER_ADDR'];

    private $server_token = 'master';

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
        $this->local_cache->addServer('localhost',11211);

        $this->server = new swoole_websocket_server('localhost', $this->port);
        
        //初始化客户端连接
        $this->init_client_connect();

        $this->server->on('open',function($server, $req){
            /*
                $req携带get参数，通过ticket与msg_token进行验证
            */
            if (isset($req->get['username']) && isset($req->get['passwd'])) {
                //用户连接验证
                $r = $this->user_verify($req->get['username'],$req->get['passwd'], $cnn->fd);
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
                $server->push(sprintf($this->msg_template['msg_verify_error'],1002,'message type error.'));
            } elseif (isset($msg['user_id']) && isset($msg['msg_type'])) {
                //普通用户消息处理
                $this->msg_handle($server,$cnn,$msg);
            } elseif (isset($msg['server']) && isset($msg['action'])) {
                //服务器之间消息通信处理
                $this->server_msg_handle($server, $cnn, $msg);

            } else {
                $server->push(sprintf($this->msg_template['msg_verify_error'],1002,'message type error.'));
            }

        });

        $this->server->on('close',function($server, $fd) {
            $userid = $this->local_cache->get('user_'.$fd);
            $this->local_cache->delete('user_'.$userid,0);
            $this->local_cache->delete('user_'.$fd,0);
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

    private function user_verify($username, $passwd, $fd)
    {
        /*
            用户验证使用已获取的ticket以及token去ticket服务器获取信息,
            $user['info']:[
                'username','email','headimg','user_id','self_signature'
            ]
        */
        
        $user = $this->ticket_server->get('user_' . $username);
        if (empty($user)) {
            return false;
        } elseif ($user['passwd'] !== $passwd) {
            return false;
        }
        $this->local_cache->add('user_'. $username, $user);
        $this->local_cache->add('user_'. $fd, $user['user_id']);
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
                if ($cli['errCode']) {
                    unset($this->client_list['cli_' . $sv['ip']]);
                }
            });

        }
        return true;
    }

}
