<?php
namespace drt;

use action;

class dispatch
{

     public $error_info = [
        'success' => [
            'errcode' => 0,
            'errmsg' => 'success'
        ],

        'api_not_found' => [
            'errcode' => 101,
            'errmsg' => 'API not found.'
        ],
        
        'class_run_error' => [
            'errcode' => 102,
            'errmsg' => 'class running failed.'
        ],

        'api_run_error' => [
            'errcode' => 103,
            'errmsg' => 'API running error'
        ],

    ];

    public $api = '';

    public function __construct()
    {
    
    }

    protected function apidriver($api)
    {
        if(empty($api)) {
            $api = '/';
        }

        $drtc = include (APP_PATH . '/config/drivertable.php');

        if (!isset($drtc[$api])) {
            $this->jsonExit($this->error_info['api_not_found']);
        }

        $apiclass = $drtc[$api]['class'];
        $apimethod = $drtc[$api]['method'];

        $apiclass = 'action\\' . $apiclass;
        
        //include (APP_PATH . '/action/' . $apiclass . '.php');

        if (!class_exists($apiclass)) {
            $this->jsonExit($this->error_info['class_run_error']);
        }

        if (!method_exists($apiclass, $apimethod)) {
            $this->jsonExit($this->error_info['api_run_error']);
        }

        try{
            $apiobj = new $apiclass;
            $ret = $apiobj->$apimethod();
            return $ret;
        }
        catch (\Exception $e) {
            exit($e->getMessage());
        }
    }

    public function pathInfoParse($pinfo)
    {
        $this->api = empty($pinfo)?'/':$pinfo;
        return $pinfo;
    }

    protected function jsonExit($ret)
    {
        exit(json_encode($ret));
    }

    public function run()
    {
        //echo $_SERVER['PATH_INFO'];return;
        $this->pathInfoParse($_SERVER['PATH_INFO']);
        return $this->apidriver($this->api);
    }

}

