<?php
require '../vendor/autoload.php';

//use Medoo\Medoo;
//use bravemaster\core\apicall;
use bravemaster\action\wsuser;

define('ROOT_PATH', dirname(__FILE__) . '/');
define('APP_PATH', dirname(ROOT_PATH) );

$api = new apicall;

$config = [];

$config['wsuser'] = new wsuser;
// 实例化 App 对象
$app = new \Slim\App($config);

$app->get('/login', function($request, $response, $args){
    $r = $this->get('wsuser')->login();
    return $response->withHeader('Access-Control-Allow-Origin','*')
                    ->withStatus(200)
                    ->write(json_encode($r));
});

$app->get('/register', function($request, $response, $args){
    $r = $this->get('wsuser')->register();
    return $response->withHeader('Access-Control-Allow-Origin','*')
                    ->withStatus(200)
                    ->write(json_encode($r));
});

/*
$app->any('/api/{call}/{action}', function($request, $response, $args){
    $r = $this->get('apicall')
            ->dispatch($args['call'],$args['action'],$request,$response);
    return json_encode($r, JSON_UNESCAPED_UNICODE);
})->add(function($request, $response, $next){
    $user_id = request_data('get', 'user_id', '');
    $api_token = request_data('get', 'api_token', '');

    $r = apiauthorise($user_id, $api_token);

    $response = $next($request, $response);
    return $response;
});
*/

$app->run();

