<?php

//$data = 'abcdshdiufhewihflidshlfkhdslkfhoiuawehfishdifiuwer9y329ry93243y2';

$data = [
    'username' => 'BraveWang',
    'user_id' => 1001,
    'email' => '1146040444@qq.com',
    'mobile' => '13223439296'
];

$serial_data = serialize($data);

$method = 'AES-256-CBC';

$key = '1234';

$iv = 'csdbfseruweoihoi';

$ssl_data = openssl_encrypt($serial_data, $method, $key , 0 , $iv);

echo $ssl_data,"\n";

$sd = openssl_decrypt($ssl_data, $method, $key , 0 , $iv);

var_dump(unserialize($sd));
