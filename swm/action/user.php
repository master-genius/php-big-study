<?php
namespace action;


class user
{
    public function __construct()
    {
    
    }

    public function setpass($request, $response)
    {
        $username = $request->getParsedBody()['username'];
        $new_pass = $request->getParsedBody()['new_passwd'];

        $r = (new \model\user)->setPass($username, $new_pass);
        if (!$r) {
            return $response->withStatus(200)->write(
                    json_encode([
                        'status' => -1,
                        'info' => 'Error: failed to update password'
                    ])
                );
        }
        return $response->withStatus(200)->write(
                json_encode([
                    'status' => 0,
                    'info' => 'success'
                ])
            );
    }

}

