<?php
namespace action;

use drt\core\wdb;

class wsuser
{
    public function __construct()
    {
    
    }

    public function login()
    {
        $username = request_data('post','username','');
        $passwd = request_data('post','passwd','');
        $condition = [
            'AND' => [
                'username' => $username,
                'passwd'   => md5($passwd)
            ]
        ];

        $user = wdb::instance()->get('users','username,passwd',$condition);

        if ($user){
            
            $token = $this->genToken($username);
            
            $mch = new Memcached('auth');
            $mch->addServer('localhost',11211);
            
            if ($mch->get($username)) {
                exit(json_encode(['error'=>0,'token'=>$mch->get($username)]));  
            }

            $mch->set($token,$username);
            $mch->set($username, $token);
            $mch->quit();

            exit(json_encode(['token'=>$token,'error'=>0]));
        }

        exit(json_encode(['error'  => -1,'errmsg' => 'user not exists or password error.']));
    }

    private function genToken($u)
    {
        return hash('sha256',md5($u.time()) . mt_rand(1000,10000));
    }
    
    public function register()
    {
        $username = request_data('get','username','');
        $passwd = request_data('get','passwd','');
        
        if (empty($username) || empty($passwd)) {
            return ['errcode'=>-1,'errmsg'=>'username/passwd not be empty.'];
        }

        $user = wdb::instance()->get('users','username',['username'=>$username]);

        if (!$user) {
            return ['errcode'=>-1,'errmsg'=>'username already register.'];
        }
        $r = wdb::instance()->insert('users',['username'=>$username,'passwd'=>md5($passwd)]);
        if (!$r) {
            return ['errcode'=>-1,'errmsg'=>'failed to register.'];
        }
        return ['errcode'=>0,'errmsg'=>'success'];
    }
}

