<?php

use \mcore\DB;

$app = new \Slim\App;

$app->group('/user',function() use ($app) {
    
    $app->post(
        '/login', 
        function ($request, $response) {
            return (new \action\auth)->login($request, $response);
        }
    );

    $app->post(
        '/register',
        function ($request, $response) {
            return (new \action\auth)->register($request, $response);
        }
    );

})->add(function($request, $response, $next){
    
    $post = $request->getParsedBody();
    if (!isset($post['username']) && !isset($post['passwd'])) {
        exit(response_api(
                    $response, 
                    -1, 
                    'Error: less username and password'
                )
            ); 
    }

    $response = $next($request, $response);

    return $response;
});



//teacher api

$app->group('/api',function () use ($app) {

    $app->get(
        '/logout',
        function ($request, $response) {
            return (new \action\auth)->logout($request,$response);
        }
    );
    
    $app->post('addfriend', function($request, $response){
            return (new \action\user)->addfriend($request, $response);
        }
    );

    $app->post('delfriend', function($request, $response){
            return (new \action\user)->delfriend($request, $response);
        }
    );

    $app->post('setfriendsign', function($request, $response){
            return (new \action\user)->setfriendsign($request, $response);
        }
    );

    $app->post('settimemsg', function($request, $response){
            return (new \action\setime)->timemsg($request, $response);
        }
    );

})->add(function($request, $response, $next){
    $get = $request->getQueryParams();
    if (!isset($get['token'])) {
        exit(response_api($response, -1, 'Error: less token'));
    }
    $check = (new \model\user)->checkToken($token);
    if (!$check) {
        exit(response_api($response,-1,'Error: permission denied'));
    }

    $response = $next($request, $response);

    return $response;
});

$app->run();

