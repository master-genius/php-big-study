<?php
namespace bravemaster\action;

use bravemaster\core\wdb;

class syncdb
{
    public function __construct(){
    
    }

    public function udata(){
        $data = request_data('get','content','');
        $in_data = [
            'add_time' => time(),
            'content' => $data
        ];

        $r = wdb::instance()->insert('content',$in_data);
        return $r;
    }

}

