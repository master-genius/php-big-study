<?php
require '../vendor/autoload.php';

// 定义系统常量
define('APP_PATH', __DIR__ . '/');
define('ROOT_PATH', __DIR__ . '/../');

// 实例化 App 对象
$app = new \Slim\App($config);


// 添加路由回调, 默认显示index.html
$app->get('/', function ($request, $response, $args) {

});

$app->get('/phpinfo',function($request,$response,$args){
    
});


// 运行应用
$app->run();

