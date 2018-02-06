<?php

namespace workon;
/*
    本程序实现分布式第0步：单机模式。
    为什么使用单机模式，因为我没有深入研究过分布式，
    尽管我学习了一下，并设计了一个简单可用的架构，
    但是实现起来过于复杂，时间紧迫，我没有那么多时间。
    对于一个系统来说，开始不会有很多用户。

    现在使用openstack部署的云计算环境已经能够实现动态调整配置，
    分布式仅仅在公司规模巨大，需要自己部署服务器集群环境的时候有用。
    
    等我挣了钱，再考虑分布式的情况。
    
    swoole异步性能很高，部署在4核8G内存、带宽10M的机器上，
    同时十几万人在线是没有问题的。
    
    API接口的实现与swoole通信可以先部署在一台机器上，
    两者本身没有依赖，可以独立。这样，单机swoole消息通信，
    单机API调用足够支撑几十万甚至上百万的用户。到时候用户
    规模过大，可以先使用8核16G内存，100M带宽顶上去，先整
    1~3台文件存储服务器用于消息文件存储。搞一台数据库服务器。
    ！百万注册用户，同时在线人数使用2/8定律考虑也就20万，
    对半计算也就50万。
*/

calss workon_msgws
{
    private $local_cache = '';


    private $port = 9876;

    //本机IP
    private $self_ip = $_SERVER['SERVER_ADDR'];

    //本机服务器token
    private $server_token = 'master';

    /*
        错误码规则：
            0：表示没有错误
            10开头表示用户消息错误
            11开头表示系统消息错误
            12未定义
    */
    private $error_code = [
        0   => 'ok',

        //用户验证失败
        101 => 'user verify failed.',
        
        //消息格式错误
        102 => 'message type illegal.',

        //非法请求，用户不接收临时消息
        103 => 'message is refused',

        //
    ];

    private $error_msg_temp = [
        
        'user_verify_failed' => '{"err_code":%d,"err_msg":"%s"}',

        'msg_verify_error' => '{"err_code":%d,"err_msg":"%s"}',

        'message_resused' => '{"err_code":%d,"err_msg":"%s"}',

    ];


    //消息模板
    private $msg_template = [

        'text_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"text","msg_time":%d,"content":"%s"}',

        'image_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"image","msg_time":%d,"image_id":"%s"}',

        'audio_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"audio","msg_time":%d,"audio_id":"%s"}',

        'video_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"video","msg_time":%d,"video_id":"%s"}',

        'note_msg'=>'{"user_id":"%s","to_user":"%s","msg_type":"note","msg_time":%d,"note_id":"%s"}',
    ];

    private $sysmsg_temp = [

        //加好友申请
        'add_friends_apply'=>'',


    ];


    public function __construct()
    {
        $this->local_cache = new Memcached('local');
        $this->local_cache->addServer('localhost',11211);

        $this->server = new swoole_websocket_server('localhost', $this->port);

        $this->server->on('open',[$this,'on_open']);

        $this->server->on('message',[$this,'on_message']);

        $this->server->on('close',[$this,'on_close']);

    }

    private function on_open($server, $req)
    {
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
        } elseif (isset($req->get['server_client_token'])) {
            $r = $this->server_verify($server,$req->get['server_client_token']);
            if (!$r) {
                $server->close($req->fd);
            }
        }
    }

    private function on_message($server,$cnn)
    {
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

    }

    private function on_close($server, $fd)
    {
        $userid = $this->local_cache->get('user_'.$fd);
        $this->local_cache->delete('user_'.$userid,0);
        $this->local_cache->delete('user_'.$fd,0);
        $this->total_cache->delete('user_'.$userid,0);
        $this->broadcast_user_outline($userid);
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
            $server->push(sprintf($this->error_msg_template['msg_verify_error'],$errcode,
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
        return true;
    }

    private function server_verify($server, $token)
    {
        if ($this->server_token !== $token) {
            return false;
        }
        //记录服务器客户端连接到缓存
        $this->local_cache->add('server_client_'.$fd, $fd);
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
