# Slim Framework Migration Plan

## Overview

Migration du système IJ Calculator standalone vers une architecture Slim Framework 4 avec structure MVC propre.

## Nouvelle Structure

```
project/
├── public/
│   ├── index.php                 # Entry point Slim
│   ├── index.html                # Frontend (existing)
│   ├── app.js                    # Frontend JS (existing)
│   └── calendar_functions.js    # Calendar JS (existing)
│
├── src/
│   ├── Application.php           # Slim app bootstrap
│   │
│   ├── Controllers/
│   │   ├── CalculationController.php  # POST /api/calculate, /api/date-effet, etc.
│   │   └── MockController.php         # GET /api/mocks, GET /api/mocks/{file}
│   │
│   ├── Services/                       # EXISTING - just move
│   │   ├── RateService.php
│   │   ├── DateService.php
│   │   ├── TauxDeterminationService.php
│   │   ├── AmountCalculationService.php
│   │   ├── RecapService.php
│   │   ├── DetailJourService.php
│   │   ├── ArretService.php
│   │   └── DateNormalizer.php
│   │
│   ├── Repositories/
│   │   └── RateRepository.php         # CSV rate loading logic
│   │
│   ├── Middlewares/
│   │   ├── CorsMiddleware.php         # CORS headers
│   │   └── JsonBodyParserMiddleware.php  # JSON parsing
│   │
│   ├── Helpers/
│   │   └── ResponseFormatter.php      # Standardized JSON responses
│   │
│   └── Models/
│       └── IJCalculator.php           # EXISTING - just move
│
├── config/
│   ├── dependencies.php              # DI Container configuration
│   ├── settings.php                  # App settings (paths, etc.)
│   └── routes.php                    # Route definitions
│
├── tests/
│   ├── Unit/                         # EXISTING tests - move here
│   │   ├── RateServiceTest.php
│   │   ├── DateServiceTest.php
│   │   ├── TauxDeterminationServiceTest.php
│   │   ├── AmountCalculationServiceTest.php
│   │   └── RechuteTest.php
│   │
│   └── Integration/
│       └── test_mocks.php           # EXISTING - move here
│
├── data/
│   ├── taux.csv                      # Rate data
│   └── mocks/                        # Mock JSON files
│       ├── mock.json
│       ├── mock2.json
│       └── ...
│
├── logs/
│   └── app.log
│
├── .env
├── composer.json
├── run_all_tests.php                # EXISTING - update paths
└── README.md

```

## Mapping Actuel → Slim

### Endpoints API

| Actuel | Slim (RESTful) | Controller | Method |
|--------|----------------|------------|--------|
| POST /api.php?endpoint=calculate | POST /api/calculations | CalculationController | calculate |
| POST /api.php?endpoint=date-effet | POST /api/calculations/date-effet | CalculationController | dateEffet |
| POST /api.php?endpoint=end-payment | POST /api/calculations/end-payment | CalculationController | endPayment |
| POST /api.php?endpoint=revenu | POST /api/calculations/revenu | CalculationController | revenu |
| POST /api.php?endpoint=determine-classe | POST /api/calculations/classe | CalculationController | determineClasse |
| POST /api.php?endpoint=calculate-arrets-date-effet | POST /api/calculations/arrets-date-effet | CalculationController | arretsDateEffet |
| GET /api.php?endpoint=list-mocks | GET /api/mocks | MockController | list |
| GET /api.php?endpoint=load-mock | GET /api/mocks/{file} | MockController | load |

### Services (NO CHANGES - just move)

- Services/ → src/Services/ (keep all existing code)
- Tests/ → tests/Unit/ (keep all existing tests)
- IJCalculator.php → src/Models/IJCalculator.php

### Nouveaux Composants

1. **RateRepository** - Extract CSV loading logic from api.php
2. **CorsMiddleware** - Extract CORS headers
3. **JsonBodyParserMiddleware** - JSON parsing
4. **ResponseFormatter** - Standardized responses
5. **CalculationController** - Group calculation endpoints
6. **MockController** - Group mock endpoints

## Migration Steps

### Phase 1: Setup Slim (composer.json)

```json
{
    "name": "carmf/ij-calculator",
    "description": "CARMF IJ Calculator with Slim Framework",
    "require": {
        "php": "^8.1",
        "slim/slim": "^4.12",
        "slim/psr7": "^1.6",
        "php-di/php-di": "^7.0",
        "monolog/monolog": "^3.5"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

### Phase 2: Create Directory Structure

```bash
mkdir -p public src/{Controllers,Services,Repositories,Middlewares,Helpers,Models}
mkdir -p config tests/{Unit,Integration} data/mocks logs
```

### Phase 3: Configuration Files

**config/settings.php**
```php
<?php
return [
    'settings' => [
        'displayErrorDetails' => true,
        'logErrors' => true,
        'logErrorDetails' => true,
        'paths' => [
            'rates_csv' => __DIR__ . '/../data/taux.csv',
            'mocks' => __DIR__ . '/../data/mocks',
        ],
    ],
];
```

**config/dependencies.php**
```php
<?php
use DI\ContainerBuilder;
use App\Models\IJCalculator;
use App\Repositories\RateRepository;
use App\Services\*;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        RateRepository::class => function ($container) {
            $settings = $container->get('settings');
            return new RateRepository($settings['paths']['rates_csv']);
        },

        IJCalculator::class => function ($container) {
            $rateRepo = $container->get(RateRepository::class);
            return new IJCalculator($rateRepo->loadRates());
        },

        // Services are auto-wired
    ]);
};
```

**config/routes.php**
```php
<?php
use Slim\App;
use App\Controllers\CalculationController;
use App\Controllers\MockController;

return function (App $app) {
    // Calculation endpoints
    $app->post('/api/calculations', [CalculationController::class, 'calculate']);
    $app->post('/api/calculations/date-effet', [CalculationController::class, 'dateEffet']);
    $app->post('/api/calculations/end-payment', [CalculationController::class, 'endPayment']);
    $app->post('/api/calculations/revenu', [CalculationController::class, 'revenu']);
    $app->post('/api/calculations/classe', [CalculationController::class, 'determineClasse']);
    $app->post('/api/calculations/arrets-date-effet', [CalculationController::class, 'arretsDateEffet']);

    // Mock endpoints
    $app->get('/api/mocks', [MockController::class, 'list']);
    $app->get('/api/mocks/{file}', [MockController::class, 'load']);
};
```

### Phase 4: Create Controllers

**src/Controllers/CalculationController.php** - Convert api.php logic to controller methods

**src/Controllers/MockController.php** - Mock loading endpoints

### Phase 5: Create Middleware

**src/Middlewares/CorsMiddleware.php** - Extract CORS logic

**src/Middlewares/JsonBodyParserMiddleware.php** - JSON parsing

### Phase 6: Create Helpers

**src/Helpers/ResponseFormatter.php** - Standardized responses

### Phase 7: Create Repository

**src/Repositories/RateRepository.php** - Extract loadRates() function

### Phase 8: Move Existing Code

```bash
# Move services (no changes to code)
mv Services/* src/Services/

# Move IJCalculator
mv IJCalculator.php src/Models/

# Move tests
mv Tests/* tests/Unit/

# Move data
mv taux.csv data/
mv mock*.json data/mocks/
```

### Phase 9: Update Namespaces

Update all imports:
- `App\IJCalculator\Services\*` → `App\Services\*`
- `App\IJCalculator\IJCalculator` → `App\Models\IJCalculator`

### Phase 10: Update Frontend

Update `app.js` API calls:
- `/api.php?endpoint=calculate` → `/api/calculations`
- `/api.php?endpoint=load-mock` → `/api/mocks/mock.json`

### Phase 11: Update Tests

Update test paths and autoloading to work with new structure.

## Benefits of Migration

1. **RESTful API** - Proper HTTP methods and resource-based URLs
2. **Dependency Injection** - Easier testing and maintenance
3. **Middleware** - Reusable request/response handling
4. **PSR Standards** - PSR-7 (HTTP messages), PSR-11 (Container), PSR-15 (Middleware)
5. **Better Structure** - Clear separation of concerns
6. **Scalability** - Easier to add new features
7. **Modern PHP** - Using latest best practices

## Backward Compatibility

To maintain backward compatibility during migration, we can:

1. Keep old `api.php` temporarily with redirect logic
2. Frontend can support both old and new endpoints
3. Gradual migration of frontend calls

## Testing Strategy

1. Keep all existing unit tests working
2. Integration tests should pass with new structure
3. Add new controller tests
4. Verify all endpoints with Postman/curl

## Timeline Estimation

- Phase 1-2 (Setup): 30 minutes
- Phase 3 (Config): 30 minutes
- Phase 4 (Controllers): 1-2 hours
- Phase 5-6 (Middleware/Helpers): 30 minutes
- Phase 7 (Repository): 15 minutes
- Phase 8-9 (Move/Update): 1 hour
- Phase 10-11 (Frontend/Tests): 1 hour
- **Total**: ~4-5 hours

## Next Steps

1. Review and approve this plan
2. Run composer install for Slim dependencies
3. Start with Phase 1-2 (setup)
4. Implement incrementally, testing at each phase
