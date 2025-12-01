<?php

declare(strict_types=1);

return [
    'settings' => [
        // Display error details
        'displayErrorDetails' => getenv('APP_DEBUG') === 'true',
        'logErrors' => true,
        'logErrorDetails' => true,

        // Application paths
        'paths' => [
            'rates_csv' => __DIR__ . '/../' . (getenv('RATES_CSV_PATH') ?: 'data/taux.csv'),
            'mocks' => __DIR__ . '/../' . (getenv('MOCKS_PATH') ?: 'data/mocks'),
            'logs' => __DIR__ . '/../' . (getenv('LOG_PATH') ?: 'logs/app.log'),
        ],

        // Logging
        'logger' => [
            'name' => 'ij-calculator',
            'path' => __DIR__ . '/../' . (getenv('LOG_PATH') ?: 'logs/app.log'),
            'level' => getenv('LOG_LEVEL') ?: 'debug',
        ],
    ],
];
