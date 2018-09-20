<?php defined('APP_PATH') or define('APP_PATH', dirname(__FILE__));
date_default_timezone_set('Asia/Jakarta');

switch (APP_PATH) {
    case '/u/k2427808/sites/api.indonesiasatu.co/www': 
        define('ENVIRONMENT', 'production'); 
        break;
    
    default: 
        define('ENVIRONMENT', 'development');
}

switch (ENVIRONMENT) {
    case 'production':
    
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>='))
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
        else
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;
    default:
        error_reporting(-1);
        ini_set('display_errors', 1);
}

require APP_PATH.'/vendor/autoload.php';

// Instantiate the app
$settings = require APP_PATH . '/config/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require APP_PATH . '/config/dependencies.php';

// Register constants
//require APP_PATH . '/config/constants.php';

// Register routes
require APP_PATH . '/config/routes.php';

$app->run();