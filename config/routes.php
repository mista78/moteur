<?php

declare(strict_types=1);

use App\Controllers\CalculationController;
use App\Controllers\HomeController;
use App\Controllers\MockController;
use App\Controllers\MethodInjectionDemoController;
use App\Controllers\MoteurijController;
use App\Middlewares\CorsMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Add CORS middleware globally
    $app->add(CorsMiddleware::class);

    // Root route - redirect to frontend
    // $app->get('/', [HomeController::class, 'index']);

    // API routes group
    $app->group('/api', function (RouteCollectorProxy $group) {

        // Calculation endpoints
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

        // Demo endpoints - Method Injection examples
        $group->post('/demo/calculate', [MethodInjectionDemoController::class, 'calculateWithMethodInjection']);
        $group->get('/demo/rates', [MethodInjectionDemoController::class, 'getRatesWithMethodInjection']);
        $group->get('/demo/rate/{year:\d+}', [MethodInjectionDemoController::class, 'getRateByYear']);
        $group->post('/demo/advanced', [MethodInjectionDemoController::class, 'advancedMethodInjection']);
    });

    // Backward compatibility routes (old api.php style)
    // $app->get('/api.php', function ($request, $response) {
    //     $endpoint = $request->getQueryParams()['endpoint'] ?? '';

    //     // Map old endpoints to new ones
    //     $mapping = [
    //         'list-mocks' => '/api/mocks',
    //         'load-mock' => '/api/mocks/' . ($request->getQueryParams()['file'] ?? 'mock.json'),
    //     ];

    //     if (isset($mapping[$endpoint])) {
    //         return $response
    //             ->withHeader('Location', $mapping[$endpoint])
    //             ->withStatus(301);
    //     }

    //     $response->getBody()->write(json_encode([
    //         'success' => false,
    //         'error' => 'Please use new API endpoints. See documentation.'
    //     ]));
    //     return $response->withStatus(400);
    // });

    // $app->post('/api.php', function ($request, $response) {
    //     $endpoint = $request->getQueryParams()['endpoint'] ?? '';

    //     // Map old endpoints to new ones
    //     $mapping = [
    //         'calculate' => '/api/calculations',
    //         'date-effet' => '/api/calculations/date-effet',
    //         'end-payment' => '/api/calculations/end-payment',
    //         'revenu' => '/api/calculations/revenu',
    //         'determine-classe' => '/api/calculations/classe',
    //         'calculate-arrets-date-effet' => '/api/calculations/arrets-date-effet',
    //     ];

    //     if (isset($mapping[$endpoint])) {
    //         $response->getBody()->write(json_encode([
    //             'success' => false,
    //             'error' => 'Please use new API endpoint: ' . $mapping[$endpoint]
    //         ]));
    //         return $response->withStatus(301);
    //     }

    //     $response->getBody()->write(json_encode([
    //         'success' => false,
    //         'error' => 'Unknown endpoint. Please use new API endpoints.'
    //     ]));
    //     return $response->withStatus(400);
    // });
};
