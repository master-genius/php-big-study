<?php
namespace action;

class auth
{
    public function __construct()
    {
    
    }
    
    public function login($req, $res)
    { 
        $post = $req->getParsedBody();
        $username = $post['username'];
        $passwd = $post['passwd'];
        $token = (new \model\user)->login($username, $passwd);
        if (empty($token)) {
            return response_api($res,-1,'Error: permission denied');
        }

        return ret_api($res, ['user_token' => $token]);
    }

    public function register($req, $res)
    {
        $post = $req->getParsedBody();
        $u = []; 
        $u['username'] = $post['username'];
        $u['passwd'] = $post['passwd'];
        $r = (new \model\user)->register($u);
        if ($r === -1) {
            return response_api($res, -1, 'Error: user already register');
        }
        elseif (!$r) {
            return response_api($res, -1, 'Error: failed to register');
        }
        
        return response_api($res);
    }

}

