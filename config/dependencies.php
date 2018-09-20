<?php
// DIC configuration
$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    
    //create new logger
    $logger = new \Monolog\Logger($settings['channel']);
    // handler init, making days separated logs
    $rotating = new \Monolog\Handler\RotatingFileHandler($settings['path_file'], 0, $settings['level']);
    //Add formatter
    $json_formatter = new \Monolog\Formatter\JsonFormatter();
    //FilePhp for better log
    $firephp = new \Monolog\Handler\FirePHPHandler();
    $rotating->setFormatter($json_formatter);
    // Now add  handler
    $logger->pushHandler($rotating);
    $logger->pushHandler($firephp);

    //Handler to add extra information Line, Filename, ClassName and FuncName only for critical error
    $logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor($settings['extra_level']));

    //Handler to send email on critical (or above) errors
    //Uses the FingersCrossed strategy which buffers all messages preceeding the critical error
    $mailHandler = new \Monolog\Handler\NativeMailerHandler(
        $settings['mail_to'], //TODO: The email address where to send emails
        'Alert: %level_name% %message% [' . date('Y-m-d H:i:s').']',
        $settings['mail_from'],
        $settings['mail_level']
    );
    //Handler for buffering the log and filter only the selected level to mail
    $logger->pushHandler(new \Monolog\Handler\FingersCrossedHandler($mailHandler, $settings['mail_level']));

    return $logger;
};

//PDO Database Connection
$container['DB'] = function($c) {
    $db_settings = $c->get('settings')['db'];
    $param = $db_settings[ENVIRONMENT];
    
    $db_connect_str = sprintf("%s:host=%s;dbname=%s;port=%s", $param['driver'], $param['host'], $param['dbname'], $param['port']);

    try {
        $dbConnection = new PDO($db_connect_str, $param['user'], $param['pwd']);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        return $dbConnection;
    } catch (PDOException $e) {
        die($e->getMessage());
    }
};

//Helper
$container['helper'] = function($c) {
    $base_url_setting = $c->get('settings')['base_url'][ENVIRONMENT];
    
    $helper = new App\Helpers\Helper($base_url_setting['site_url'],$base_url_setting['image_url']);
    return $helper;
};