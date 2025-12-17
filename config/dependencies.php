<?php

declare(strict_types=1);

use App\Controllers\MockController;
use App\Controllers\SinistreController;
use App\IJCalculator;
use App\Repositories\RateRepository;
use App\Repositories\PassRepository;
use App\Services\TauxDeterminationService;
use App\Services\DateService;
use App\Services\SinistreService;
use App\Services\SinistreServiceInterface;
use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
            // Eloquent Database (Multi-Database Support)
        Capsule::class => function (ContainerInterface $c) {
            $config = require __DIR__ . '/database.php';

            $capsule = new Capsule;

            // Register all database connections
            foreach ($config['connections'] as $name => $connection) {
                $capsule->addConnection($connection, $name);
            }

            // Set default connection
            $capsule->getDatabaseManager()->setDefaultConnection($config['default']);

            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            return $capsule;
        },
            // Logger
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['settings'];
            $loggerSettings = $settings['logger'];

            $logger = new Logger($loggerSettings['name']);
            $logger->pushProcessor(new UidProcessor());
            $logger->pushHandler(new StreamHandler($loggerSettings['path'], $loggerSettings['level']));

            return $logger;
        },

            // Rate Repository
        RateRepository::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['settings'];
            return new RateRepository($settings['paths']['rates_csv']);
        },

            // PASS Repository
        PassRepository::class => function (ContainerInterface $c) {
            return new PassRepository();
        },

            // Taux Determination Service (with PASS values from database)
        TauxDeterminationService::class => function (ContainerInterface $c) {
            $passRepo = $c->get(PassRepository::class);
            $service = new TauxDeterminationService();

            // Load PASS values from database
            $passValues = $passRepo->loadPassValuesByYear();
            $service->setPassValuesByYear($passValues);

            return $service;
        },

        // Date Service
        DateService::class => function (ContainerInterface $c) {
            return new DateService();
        },

        // Sinistre Service (with DateService injection)
        SinistreServiceInterface::class => function (ContainerInterface $c) {
            return new SinistreService($c->get(DateService::class));
        },

        // Sinistre Controller (with SinistreService injection)
        SinistreController::class => function (ContainerInterface $c) {
            return new SinistreController(
                $c->get(SinistreServiceInterface::class),
                $c->get(LoggerInterface::class)
            );
        },

            // IJ Calculator
        IJCalculator::class => function (ContainerInterface $c) {
            $rateRepo = $c->get(RateRepository::class);
            $passRepo = $c->get(PassRepository::class);
            return new IJCalculator($rateRepo->loadRates(), null, null, null, null, $passRepo);
        },


            // MockController needs settings
        MockController::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['settings'];
            return new MockController($settings, $c->get(LoggerInterface::class));
        },

        // Settings
        'settings' => function () {
            return require __DIR__ . '/settings.php';
        },
    ]);
};
