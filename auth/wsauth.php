<?php
header('Access-Control-Allow-Origin: *');
(new wsAuth)->pass();

class wsAuth
{
    private $user_data = [
        'brave' => [
            'passwd' => 'abc101'
        ],

        'albert' => [
            'passwd' =>'abc101'
        ],

        'bruce' => [
            'passwd' => 'abc101'
        ]
    ];

    public function __construct()
    {
    
    }

    public function pass()
    {
        $username = (isset($_POST['username']))?$_POST['username']:'';
        $passwd = (isset($_POST['passwd']))?$_POST['passwd']:'';

        if (isset($this->user_data[$username])&&($this->user_data[$username]['passwd'] == $passwd)){
            
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

    private function genToken($user)
    {
        return hash('sha256',md5($user.time()) . mt_rand(1000,10000));
    }
}

