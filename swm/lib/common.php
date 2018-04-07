<?php
/*
    系统助手函数
*/

function what_system()
{
    $si = php_uname('a');
    $si = strtolower($si);
    $in_system=['windows','win','ubuntu','debian'];
    if(strstr($si,'debian') && strstr($si,'linux') ){
        return 'debian';
    }
    elseif(strstr($si,'ubuntu') && strstr($si,'linux') ){
        return 'ubuntu';
    }
    elseif( strstr($si,'windows') ){
        return 'win';
    }
    elseif( strstr($si,'linux') ){
        return 'linux';
    }
    else{
        return null;
    }

}

function is_linux()
{
    $what = what_system();
    if( $what == 'ubuntu' || $what == 'debian' || $what == 'linux' ){
        return true;
    }
    return false;
}

function is_windows()
{
    $what = what_system();
    if( $what == 'win'  ){
        return true;
    }
    return false;
}

//用于获取提交的数据
//参数格式：[ 'a'=>['post','name',''] , 'b'=>['get','id',''], 'c'=>['session','uid',''] ]
//
function request_data_table($field_arr=[],$strap=false)
{
    $req_data = [];
    $data = '';
    foreach($field_arr as $d){
        if($d[0] == 'post'){
            $data = isset($_POST[$d[1]])?$_POST[$d[1]]:(isset($d[2])?$d[2]:null);
        }
        elseif($d[0] == 'get'){
            $data = isset($_GET[$d[1]])?$_GET[$d[1]]:(isset($d[2])?$d[2]:null);
        }
        elseif($d[0] == 'session'){
            $data = isset($_SESSION[$d[1]])?$_SESSION[$d[1]]:(isset($d[2])?$d[2]:null);
        }
        else{
            $data = null;
        }
        
        if($data === null){
            continue;
        }
        if($strap){
            $data=trim($data);
        }
        if(isset($d[3])){
            $req_data[$d[3]] = $data;
        }
        else{
            $req_data[$d[1]] = $data;
        }
    }
    return $req_data;
}

function request_data($val_type,$ind,$val_def=null,$strap=true)
{
    $data = null;
    switch($val_type){
        case 'post':
            $data = isset($_POST[$ind])?$_POST[$ind]:$val_def;
            break;
        case 'get':
            $data = isset($_GET[$ind])?$_GET[$ind]:$val_def;
            break;
        case 'session':
            $data = isset($_SESSION[$ind])?$_SESSION[$ind]:$val_def;
            break;
        default:;
    }

    return ($strap?trim($data):$data);
}

function json_exit($data)
{
    if(is_array($data)){
        exit(json_encode($data));
    }
    elseif (is_string($data)){
        exit($data);
    }
    else {
        exit('');
    }
}

function jsonexit($status,$data,$ind='info')
{
    $ret=[
        'status'=>$status,
        $ind=>$data,
    ];
    exit(json_encode($ret));
}

function set_sys_error($info='')
{
    $GLOBALS['sys_error_info'] = $info;
}

function get_sys_error()
{
    if(isset($GLOBALS['sys_error_info'])){
        return $GLOBALS['sys_error_info'];
    }
    return '';
}

function total_page($total,$pagesize)
{
    return (
            ($total%$pagesize)
            ?
                (( (int)($total/$pagesize) )+1)
            :
                ((int)($total/$pagesize))
           );
}

function format_time($t,$mode='')
{
    $tm = localtime($t,true);
    $ft = ($tm['tm_year']+1900) . '.' . 
          ($tm['tm_mon']+1) . '.' . $tm['tm_mday'];

    if($mode!=='ymd'){
        $ft .= '  ' . $tm['tm_hour'] . ':' . $tm['tm_min'];
    }
    return $ft;
}

function number_test(&$n,$v=0)
{
  if(!is_numeric($n)){
    $n=$v;
  }
}

//递归输出数组信息
function walk_arr_echo($a, $tab, $end){
    if ( !is_array($a) && !is_object($a) ) {
        echo $tab . $a . $end;
        return ;
    }

    foreach($a as $k=>$v){
        if(is_array($v)){
            echo $tab.$k . '=>[' . $end;
            walk_arr_echo($v,$tab.$tab,$end);
            echo $tab . ']' . $end;
        } elseif ( !is_array($v) && !is_object($a) ) {
            echo $tab.(is_numeric($k)?'':$k.'=>') . $v . $end;
        }
    }
}
//递归输出数组信息，实际使用此函数，此函数调用walk_arr_echo
function walk_arr($a)
{
    $sapi = php_sapi_name();
    $tab = ($sapi == 'cli')?'    ':'&nbsp;&nbsp;&nbsp;&nbsp;';
    $endline = ($sapi == 'cli')?"\n":"<br>";
    walk_arr_echo($a,$tab,$endline);
}

function response_api($response, $status=0, $info='success')
{
    return $response->withHeader('Access-Control-Allow-Origin:','*')
            ->withStatus(200)->write(json_encode([
                'status' => $status,
                'info' => $info
               ],JSON_UNESCAPED_UNICODE)
           );
}

function ret_api($response, $data)
{
    return $response->withHeader('Access-Control-Allow-Origin:','*')
            ->withStatus(200)
            ->write( json_encode($data,JSON_UNESCAPED_UNICODE) );
}

