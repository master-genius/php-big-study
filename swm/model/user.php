<?php
namespace model;

use mcore\DB;

class user
{
    private $table = 'sw_user';

    public function __construct()
    {
    
    }

    public function login($username, $passwd)
    {
        $u = DB::instance()->select(
                        $this->table, "*",
                        [
                            'AND'=>[
                                'username' => $username,
                                'passwd' => $passwd
                            ]
                        ]
                    );
        if (empty($u)) {
            return false;
        }

        
        $token = $mch->get($username); 

        if ($token) {
            $mch->quit();
            return $token;
        }


        return $token;
    }

    private function genToken($user)
    {
        return hash('sha512',md5($user.time()) . mt_rand(1000,10000));
    }

    public function register($u)
    {
        $check = $this->userInfo($u['username']);
        if  ($check) {
            return -1;
        }

        $u['passwd'] = $this->hashPasswd($u['passwd']);
        
        $r = DB::instance()->insert($this->table, $u);
        if (!$r) {
            return false;
        }
        return true;
    }

    private function hashPasswd($p)
    {
        return hash('sha512', $p);
    }

    private function userInfo($username, $passwd=null)
    {
        $w = [];

        if ($passwd!==null) {
            $w = [
                'AND'=>[
                    'username' => $username,
                    'passwd' => $this->hashPasswd($passwd)
                ]
            ];
        }
        else {
            $w = [
                'username' => $username
            ];
        }
        $u = DB::instance()->select($this->table, "*", $w);
        if (empty($u)) {
            return [];
        }
        return $u;
    }
    
    public function checkUser($username, $passwd)
    {
        return (empty($this->userInfo($username,$passwd))?false:true);
    }

    private function memCache(){
        $mch = new Memcached('auth');
        $mch->addServer('localhost',11211);
        return $mch;
    }

    public function setToken($username, $token){
        $mch = $this->memCache();
        $token = $this->genToken($username);
        $mch->set($token,$username);
        $mch->set($username, $token);
        $mch->quit();
    }

    public function checkToken($token)
    {
        $mch = $this->memCache();
        $token = $mch->get($token);
        return ($token?true:false); 
    }

    public function getUserByName($username)
    {
        return $this->userInfo($username);
    }

    public function setPass($username, $new_passwd)
    {
        $r = DB::instance()->update($this->table,
                ['passwd'=>$this->hashPasswd($new_passwd)],
                ['username'=>$username]
            )->rowCount();
        if ($r > 0) {
            return true;
        }
        return false;
    }

}
