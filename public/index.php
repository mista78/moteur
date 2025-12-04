<?php

declare(strict_types=1);

// Allow PHP built-in server to serve static files
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Slim\Factory\ServerRequestCreatorFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables
if (file_exists(dirname(__DIR__) . '/.env')) {
    $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        putenv(trim($line));
    }
}

// Build DI Container
$containerBuilder = new ContainerBuilder();

// Add container definitions
$dependencies = require dirname(__DIR__) . '/config/dependencies.php';
$dependencies($containerBuilder);

// Build container
$container = $containerBuilder->build();

// Initialize Eloquent ORM
$container->get(\Illuminate\Database\Capsule\Manager::class);

// Create Slim App with DI Bridge (enables method injection)
$app = Bridge::create($container);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Add routing middleware
$app->addRoutingMiddleware();

// Add JSON body parser middleware (for additional parsing)
$app->add(App\Middlewares\JsonBodyParserMiddleware::class);

// Add error middleware
$displayErrorDetails = $container->get('settings')['settings']['displayErrorDetails'];
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);

// Register routes
$routes = require dirname(__DIR__) . '/config/routes.php';
$routes($app);

// Run app
$app->run();
