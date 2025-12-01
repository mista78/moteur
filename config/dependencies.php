<?php

declare(strict_types=1);

use App\IJCalculator;
use App\Repositories\RateRepository;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
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

        // IJ Calculator
        IJCalculator::class => function (ContainerInterface $c) {
            $rateRepo = $c->get(RateRepository::class);
            return new IJCalculator($rateRepo->loadRates());
        },

        // MockController needs settings
        \App\Controllers\MockController::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['settings'];
            return new \App\Controllers\MockController($settings, $c->get(LoggerInterface::class));
        },

        // Settings
        'settings' => function () {
            return require __DIR__ . '/settings.php';
        },
    ]);
};
