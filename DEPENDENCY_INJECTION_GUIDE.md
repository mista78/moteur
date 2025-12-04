# Dependency Injection Guide

## How Dependency Injection Works in This Project

This project uses **PHP-DI** (PHP Dependency Injection) container to automatically wire dependencies into controllers and services.

> **💡 NEW**: Want to inject dependencies into **methods** instead of constructors?
> See `METHOD_INJECTION_GUIDE.md` for examples of action-specific dependency injection!

## Configuration: `config/dependencies.php`

Dependencies are defined in the DI container configuration:

```php
<?php
use App\IJCalculator;
use App\Repositories\RateRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        // Rate Repository - loads rates from database/CSV
        RateRepository::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['settings'];
            return new RateRepository($settings['paths']['rates_csv']);
        },

        // IJ Calculator - receives rates from RateRepository
        IJCalculator::class => function (ContainerInterface $c) {
            $rateRepo = $c->get(RateRepository::class);
            return new IJCalculator($rateRepo->loadRates());
        },

        // Logger - configured with app settings
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['settings'];
            $loggerSettings = $settings['logger'];

            $logger = new Logger($loggerSettings['name']);
            $logger->pushHandler(new StreamHandler($loggerSettings['path']));

            return $logger;
        },
    ]);
};
```

## Using Dependencies in Controllers

### Example 1: CalculationController (Existing)

**File**: `src/Controllers/CalculationController.php`

```php
<?php

namespace App\Controllers;

use App\IJCalculator;
use App\Helpers\ResponseFormatter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class CalculationController
{
    // Declare dependencies as private properties
    private IJCalculator $calculator;
    private LoggerInterface $logger;

    // Constructor receives dependencies (auto-wired by PHP-DI)
    public function __construct(IJCalculator $calculator, LoggerInterface $logger)
    {
        $this->calculator = $calculator;
        $this->logger = $logger;
    }

    /**
     * POST /api/calculations
     */
    public function calculate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $input = $request->getParsedBody();

            // Use the injected calculator
            $result = $this->calculator->calculateAmount($input);

            // Use the injected logger
            $this->logger->info('Calculation completed', [
                'nb_jours' => $result['nb_jours'],
                'montant' => $result['montant']
            ]);

            return ResponseFormatter::success($response, $result);

        } catch (Exception $e) {
            $this->logger->error('Calculation error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }
}
```

### Example 2: Creating a New Controller with Dependencies

**File**: `src/Controllers/ReportController.php` (example)

```php
<?php

namespace App\Controllers;

use App\IJCalculator;
use App\Models\IjRecap;
use App\Models\IjTaux;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ReportController
{
    private IJCalculator $calculator;
    private LoggerInterface $logger;

    /**
     * Constructor - Dependencies are automatically injected
     *
     * @param IJCalculator $calculator - Injected by PHP-DI
     * @param LoggerInterface $logger - Injected by PHP-DI
     */
    public function __construct(IJCalculator $calculator, LoggerInterface $logger)
    {
        $this->calculator = $calculator;
        $this->logger = $logger;
    }

    /**
     * GET /api/reports/summary
     */
    public function summary(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            // Use injected calculator
            $passValue = $this->calculator->getPassValue();

            // Use Eloquent models
            $totalRecaps = IjRecap::count();
            $rateCount = IjTaux::count();

            // Use injected logger
            $this->logger->info('Report summary generated');

            $data = [
                'pass_value' => $passValue,
                'total_calculations' => $totalRecaps,
                'rate_records' => $rateCount,
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Report error', ['error' => $e->getMessage()]);

            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
```

### Example 3: Controller with Custom Dependencies

If you need to inject custom services or repositories:

**Step 1**: Define the dependency in `config/dependencies.php`:

```php
<?php

use App\Services\CustomService;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        // ... existing definitions ...

        // Custom Service
        CustomService::class => function (ContainerInterface $c) {
            $logger = $c->get(LoggerInterface::class);
            $calculator = $c->get(IJCalculator::class);

            return new CustomService($logger, $calculator);
        },
    ]);
};
```

**Step 2**: Use it in your controller:

```php
<?php

namespace App\Controllers;

use App\Services\CustomService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CustomController
{
    private CustomService $customService;

    public function __construct(CustomService $customService)
    {
        $this->customService = $customService;
    }

    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->customService->doSomething();

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

## Common Dependency Patterns

### Pattern 1: Auto-Wiring (Recommended)

For simple classes without complex initialization, PHP-DI can auto-wire them:

```php
<?php

namespace App\Controllers;

use App\IJCalculator;
use Psr\Log\LoggerInterface;

// No need to define in dependencies.php if constructor is simple
class SimpleController
{
    public function __construct(
        private IJCalculator $calculator,
        private LoggerInterface $logger
    ) {}
}
```

### Pattern 2: Factory Definition

For classes with complex initialization:

```php
<?php
// In config/dependencies.php

ComplexService::class => function (ContainerInterface $c) {
    $settings = $c->get('settings')['settings'];
    $logger = $c->get(LoggerInterface::class);
    $calculator = $c->get(IJCalculator::class);

    $service = new ComplexService($logger);
    $service->setCalculator($calculator);
    $service->configure($settings['complex_settings']);

    return $service;
},
```

### Pattern 3: Singleton Services

For services that should be created once and reused:

```php
<?php
// In config/dependencies.php

use DI\Container;

CacheService::class => DI\create(CacheService::class)->constructor(
    DI\get(LoggerInterface::class),
    DI\get('settings')
),
```

## Accessing Dependencies in Routes

Routes can access dependencies through controller methods:

**File**: `config/routes.php`

```php
<?php

use App\Controllers\CalculationController;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        // Controller method will be called with dependencies auto-injected
        $group->post('/calculations', [CalculationController::class, 'calculate']);
        $group->post('/calculations/date-effet', [CalculationController::class, 'dateEffet']);
    });
};
```

## Using IJCalculator Methods

Once injected, you can use all IJCalculator methods:

```php
<?php

// In your controller method
public function someMethod(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    // Calculate amount
    $result = $this->calculator->calculateAmount($input);

    // Get/Set PASS value
    $passValue = $this->calculator->getPassValue();
    $this->calculator->setPassValue(42000.0);

    // Determine class from revenue
    $classe = $this->calculator->determineClasse($revenuNMoins2, $dateOuverture, $taxeOffice);

    // Calculate date effet
    $dateEffet = $this->calculator->calculateDateEffet($arrets, $birthDate);

    // Get rate information
    $dailyRate = $this->calculator->getDailyRate($statut, $classe, $option, $taux, $year);

    return ResponseFormatter::success($response, $result);
}
```

## Benefits of Dependency Injection

1. **Testability**: Easy to mock dependencies in unit tests
2. **Loose Coupling**: Controllers don't create their own dependencies
3. **Maintainability**: Change implementations without modifying controllers
4. **Single Responsibility**: Each class focuses on its own logic
5. **Configuration**: Centralized dependency configuration

## Example: Testing with Mocked Dependencies

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\CalculationController;

class CalculationControllerTest extends TestCase
{
    public function testCalculate()
    {
        // Create mock dependencies
        $mockCalculator = $this->createMock(IJCalculator::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        // Configure mock behavior
        $mockCalculator->expects($this->once())
            ->method('calculateAmount')
            ->willReturn(['montant' => 1500.0, 'nb_jours' => 30]);

        // Inject mocks into controller
        $controller = new CalculationController($mockCalculator, $mockLogger);

        // Test the controller
        // ... test logic ...
    }
}
```

## Common Issues and Solutions

### Issue 1: "Class not found"
**Solution**: Ensure class is imported with `use` statement and namespace is correct

### Issue 2: "Circular dependency"
**Solution**: Refactor to remove circular references, or use setter injection

### Issue 3: "Cannot autowire parameter"
**Solution**: Define the dependency explicitly in `config/dependencies.php`

### Issue 4: "Settings not found"
**Solution**: Ensure settings are defined in `config/settings.php` and loaded in dependencies

## Summary

**Key Points**:
1. **Define** dependencies in `config/dependencies.php`
2. **Inject** via constructor parameters (type-hinted)
3. **Use** injected dependencies in controller methods
4. **Route** to controller methods in `config/routes.php`

**PHP-DI handles**:
- Automatic instantiation
- Dependency resolution
- Singleton management
- Factory patterns

This pattern keeps your code clean, testable, and maintainable! 🚀
