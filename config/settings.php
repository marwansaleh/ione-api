<?php
return [
    'settings' => [
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => true,//(ENVIRONMENT=='production' ? false:true),
        'addContentLengthHeader' => false,
        //'routerCacheFile' => '../caches/fastroute.cache'
        'db' => [
            'development' => [
                'driver'        => 'mysql',
                'host'          => 'localhost',
                'user'          => 'root',
                'pwd'           => '',
                'dbname'        => 'stabilitas_new',
                'port'          => '3306'
            ],
            'production' => [
                'driver'        => 'mysql',
                'host'          => 'localhost',
                'user'          => 'stabilit_new2',
                'pwd'           => 'stabilit_new2',
                'dbname'        => 'stabilit_new2',
                'port'          => '3306'
            ]
        ],
        'logger' => [
            'channel' => 'STAB',
            'path_file' => APP_PATH.'/logs/ws.log',
            'level' => \Monolog\Logger::DEBUG,
            'extra_level' => \Monolog\Logger::CRITICAL,
            'mail_level' => \Monolog\Logger::CRITICAL,
            'mail_from' => 'support@stabilitas.co.id',
            'mail_to' => 'support@stabilitas.co.id',
        ]
    ],
    'notFoundHandler' => function ($container) {
        return function ($request, $response) use ($container) {
            return $container['response']
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['status'=>FALSE, 'code'=>400, 'message'=>'Service not found']));
        };
    },
    'notAllowedHandler' => function ($container) {
        return function ($request, $response) use ($container) {
            return $container['response']
                ->withStatus(405)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['status'=>FALSE, 'code'=>405, 'message'=>'Method not allowed']));
        };
    },
    'phpErrorHandler' => function ($container) {
        return function ($request, $response, $exception) use ($container) {
            return $container['response']
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['status'=>FALSE, 'code'=>500, 'message'=>$exception->getMessage()]));
        };
    },
    'errorHandler' => function ($container) {
        return function ($request, $response, $exception) use ($container) {
            return $container['response']
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['status'=>FALSE, 'code'=>500, 'message'=>$exception->getMessage()]));
        };
    }
];