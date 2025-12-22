<?php

declare(strict_types=1);

use App\Controllers\CalculationController;
use App\Controllers\HomeController;
use App\Controllers\MockController;
use App\Controllers\MethodInjectionDemoController;
use App\Controllers\MoteurijController;
use App\Controllers\SinistreController;
use App\Controllers\SwaggerController;
use App\Middlewares\CorsMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Add CORS middleware globally
    $app->add(CorsMiddleware::class);

    // API Documentation routes (Swagger UI)
    $app->get('/api-docs', [SwaggerController::class, 'ui']);
    $app->get('/api/docs', [SwaggerController::class, 'json']);
    $app->get('/api/docs/yaml', [SwaggerController::class, 'yaml']);

    // Root route - redirect to frontend
    $app->get('/', [SwaggerController::class, 'ui']);

    // API routes group
    $app->group('/api', function (RouteCollectorProxy $group) {

        // Calculation endpoints
        $group->get('/test', [HomeController::class, 'index']);
        $group->get('/dateeffect', [MoteurijController::class, 'dateEffect']);
        $group->post('/calculations', [CalculationController::class, 'calculate']);
        $group->post('/calculations/date-effet', [CalculationController::class, 'dateEffet']);
        $group->post('/calculations/end-payment', [CalculationController::class, 'endPayment']);
        $group->post('/calculations/revenu', [CalculationController::class, 'revenu']);
        $group->post('/calculations/classe', [CalculationController::class, 'determineClasse']);
        $group->post('/calculations/arrets-date-effet', [CalculationController::class, 'arretsDateEffet']);

        // Mock endpoints
        $group->get('/mocks', [MockController::class, 'list']);
        $group->get('/mocks/{file}', [MockController::class, 'load']);

        // Sinistre endpoints (with date-effet calculation)
        $group->get('/sinistres/{id}/date-effet', [SinistreController::class, 'getSinistreWithDateEffet']);
        $group->get('/adherents/{adherent_number}/{numero_dossier}', [SinistreController::class, 'getSinistreForAdherent']);
        $group->get('/adherents/{adherent_number}/sinistres/date-effet', [SinistreController::class, 'getAllSinistresForAdherent']);

        // Demo endpoints - Method Injection examples
        $group->post('/demo/calculate', [MethodInjectionDemoController::class, 'calculateWithMethodInjection']);
        $group->get('/demo/rates', [MethodInjectionDemoController::class, 'getRatesWithMethodInjection']);
        $group->get('/demo/rate/{year:\d+}', [MethodInjectionDemoController::class, 'getRateByYear']);
        $group->post('/demo/advanced', [MethodInjectionDemoController::class, 'advancedMethodInjection']);
    });
    
};
