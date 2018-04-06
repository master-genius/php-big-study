<?php
namespace app;

use mcore\DB;

class api
{
    public function __construct()
    {
    
    }
    

    public function upload($request, $response, $args)
    {
        return (new \action\task)->upload($request, $response, $args); 
    }

    public function setpass($request, $response, $args)
    {
        return (new \action\user)->setpass($request, $response, $args); 
    }

    public function pubTask($request, $response, $args)
    {
        return (new \action\task)->pubTask($request, $response, $args); 
    }


    public function postTest($request, $response, $args)
    {
        //exit($_POST['test']);
        //var_dump($request->getParsedBody());exit;
        $data = $request->getParsedBody()['test'];
        return $response->withStatus(200)->write($data);
    }

    public function createuser($request, $response, $args)
    {
        exit("deny!");
        /*
        $file_start = '/home/brave/tmp/';
        $file_mid = 'linux-students-';
        $file_list = [
            '12','34','56','78'
        ];
        
        $file = '';
        $ulist=[];
        foreach ($file_list as $k) {
            $file = $file_start . $file_mid . $k;
            if (is_file($file)) {
                $content = file_get_contents($file);
                $u_split = explode("\n",$content);
                foreach ($u_split as $u) {
                    $tmp = explode(" ", $u);
                    $au = [];
                    foreach ($tmp as $v) {
                        if(!empty($v) && $v!=="\n") {
                            $au[] = trim($v);
                        }
                    }
                    if (empty($au[0])) {
                        continue;
                    }

                    $ulist[] = [
                                'username'=>$au[0],
                                'passwd'=>hash('sha512',$au[0]),
                                'student_name'=>$au[1],
                                'student_class'=>$k
                            ];
                }
                DB::instance()->insert('students',$ulist);
                $ulist = [];
                //echo str_replace("\n", "<br>", $content);
            }
            else {
                echo 'error<br>';
            }
        }
        */
        //walk_arr($ulist);



    }

}

