# Method Injection Guide

## Two Ways to Inject Dependencies

### 1. Constructor Injection (Traditional)
Dependencies shared across all methods
```php
public function __construct(IJCalculator $calculator) {}
```

### 2. Method Injection (Action-specific)
Dependencies only for specific methods
```php
public function calculate(Request $request, Response $response, IJCalculator $calculator) {}
```

---

## Method Injection with Slim 4

Slim 4 with PHP-DI supports **automatic method parameter injection** when using invokable controllers or callable arrays.

### Pattern 1: Invokable Controller (Single Action)

**File**: `src/Controllers/QuickCalculationController.php`

```php
<?php

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\IJCalculator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Invokable Controller - One action per class
 * Dependencies injected directly into __invoke method
 */
class QuickCalculationController
{
    /**
     * __invoke is called when controller is used as callable
     *
     * Dependencies are automatically injected by PHP-DI:
     * - $request, $response: From Slim routing
     * - $calculator, $logger: From DI container
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator,              // ✅ Injected from container
        LoggerInterface $logger                 // ✅ Injected from container
    ): ResponseInterface {
        try {
            $input = $request->getParsedBody();

            // Use injected dependencies
            $result = $calculator->calculateAmount($input);
            $logger->info('Quick calculation completed', ['result' => $result]);

            return ResponseFormatter::success($response, $result);

        } catch (\Exception $e) {
            $logger->error('Quick calculation failed', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }
}
```

**Route**: `config/routes.php`
```php
<?php
use App\Controllers\QuickCalculationController;

$group->post('/quick-calc', QuickCalculationController::class);
// Slim will call __invoke() with auto-injected dependencies
```

---

### Pattern 2: Method-Specific Injection in Regular Controller

**File**: `src/Controllers/FlexibleController.php`

```php
<?php

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\IJCalculator;
use App\Models\IjTaux;
use App\Repositories\RateRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Flexible Controller - Mix constructor and method injection
 */
class FlexibleController
{
    // Some dependencies in constructor (shared across methods)
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Method 1: Uses method-injected calculator
     *
     * Route: POST /api/flexible/calculate
     */
    public function calculate(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator                // ✅ Injected per method
    ): ResponseInterface {
        try {
            $input = $request->getParsedBody();

            // Use method-injected calculator
            $result = $calculator->calculateAmount($input);

            // Use constructor-injected logger
            $this->logger->info('Calculation done');

            return ResponseFormatter::success($response, $result);

        } catch (\Exception $e) {
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * Method 2: Different dependencies
     *
     * Route: GET /api/flexible/rates
     */
    public function getRates(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RateRepository $rateRepo                // ✅ Different dependency
    ): ResponseInterface {
        try {
            // Use method-injected repository
            $rates = $rateRepo->loadRates();

            $this->logger->info('Rates loaded', ['count' => count($rates)]);

            return ResponseFormatter::success($response, [
                'rates' => $rates,
                'count' => count($rates)
            ]);

        } catch (\Exception $e) {
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * Method 3: Multiple injected dependencies
     *
     * Route: POST /api/flexible/advanced
     */
    public function advanced(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator,               // ✅ Injected
        RateRepository $rateRepo                // ✅ Injected
    ): ResponseInterface {
        try {
            // Use both injected dependencies
            $rates = $rateRepo->loadRates();
            $calculator->setPassValue(42000);

            $result = $calculator->calculateAmount($request->getParsedBody());

            return ResponseFormatter::success($response, [
                'result' => $result,
                'rate_count' => count($rates)
            ]);

        } catch (\Exception $e) {
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }
}
```

**Routes**: `config/routes.php`
```php
<?php
use App\Controllers\FlexibleController;

$group->post('/flexible/calculate', [FlexibleController::class, 'calculate']);
$group->get('/flexible/rates', [FlexibleController::class, 'getRates']);
$group->post('/flexible/advanced', [FlexibleController::class, 'advanced']);
```

---

### Pattern 3: Route Parameters + Method Injection

**File**: `src/Controllers/RateController.php`

```php
<?php

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\IjTaux;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class RateController
{
    /**
     * Get rate by ID
     *
     * Route: GET /api/rates/{id}
     *
     * Combines route parameters with dependency injection
     */
    public function getById(
        ServerRequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger,                // ✅ Injected from container
        int $id                                  // ✅ From route parameter
    ): ResponseInterface {
        try {
            // Use injected logger
            $logger->info('Fetching rate', ['id' => $id]);

            // Use route parameter
            $rate = IjTaux::find($id);

            if (!$rate) {
                return ResponseFormatter::error($response, "Rate not found", 404);
            }

            return ResponseFormatter::success($response, [
                'id' => $rate->id,
                'date_start' => $rate->date_start->format('Y-m-d'),
                'date_end' => $rate->date_end->format('Y-m-d'),
                'rates' => [
                    'A' => [$rate->taux_a1, $rate->taux_a2, $rate->taux_a3],
                    'B' => [$rate->taux_b1, $rate->taux_b2, $rate->taux_b3],
                    'C' => [$rate->taux_c1, $rate->taux_c2, $rate->taux_c3],
                ]
            ]);

        } catch (\Exception $e) {
            $logger->error('Rate fetch failed', ['id' => $id, 'error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * Get rates by year
     *
     * Route: GET /api/rates/year/{year}
     */
    public function getByYear(
        ServerRequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger,                // ✅ Injected
        int $year                                // ✅ Route parameter
    ): ResponseInterface {
        try {
            $rate = IjTaux::getRateForYear($year);

            if (!$rate) {
                return ResponseFormatter::error($response, "No rate found for year {$year}", 404);
            }

            $logger->info('Rate found for year', ['year' => $year]);

            return ResponseFormatter::success($response, [
                'year' => $year,
                'date_start' => $rate->date_start->format('Y-m-d'),
                'date_end' => $rate->date_end->format('Y-m-d'),
                'taux_a1' => $rate->taux_a1,
                'taux_b1' => $rate->taux_b1,
                'taux_c1' => $rate->taux_c1,
            ]);

        } catch (\Exception $e) {
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }
}
```

**Routes**: `config/routes.php`
```php
<?php
use App\Controllers\RateController;

$group->get('/rates/{id:\d+}', [RateController::class, 'getById']);
$group->get('/rates/year/{year:\d+}', [RateController::class, 'getByYear']);
```

---

## Comparison: Constructor vs Method Injection

### When to Use Constructor Injection ✅

```php
class ReportController
{
    // ✅ Good: Logger used in ALL methods
    public function __construct(private LoggerInterface $logger) {}

    public function daily() { $this->logger->info(...); }
    public function monthly() { $this->logger->info(...); }
    public function yearly() { $this->logger->info(...); }
}
```

**Use when:**
- Dependency used across multiple methods
- Dependency is essential for class functionality
- You want to enforce dependency at construction time

### When to Use Method Injection ✅

```php
class MultiActionController
{
    // ✅ Good: Calculator only needed in one method
    public function calculate(Request $r, Response $res, IJCalculator $calc) {
        return $calc->calculateAmount(...);
    }

    // ✅ Good: Different dependency for different action
    public function export(Request $r, Response $res, ExportService $export) {
        return $export->toPdf(...);
    }
}
```

**Use when:**
- Dependency only needed in one method
- Different methods need different dependencies
- You want lightweight, focused controllers

### Mixing Both ✅

```php
class HybridController
{
    // Constructor: Shared dependencies
    public function __construct(private LoggerInterface $logger) {}

    // Method: Action-specific dependencies
    public function calculate(Request $r, Response $res, IJCalculator $calc) {
        $this->logger->info('Starting calculation');  // Constructor-injected
        return $calc->calculateAmount($r->getParsedBody());  // Method-injected
    }
}
```

---

## How PHP-DI Resolves Method Parameters

PHP-DI automatically resolves method parameters in this order:

1. **Route attributes** (e.g., `{id}`, `{year}`)
2. **Registered container services** (IJCalculator, LoggerInterface, etc.)
3. **PSR-7 objects** (ServerRequestInterface, ResponseInterface)

**Example method signature:**
```php
public function process(
    ServerRequestInterface $request,    // ✅ From Slim (PSR-7)
    ResponseInterface $response,        // ✅ From Slim (PSR-7)
    IJCalculator $calculator,           // ✅ From DI Container
    LoggerInterface $logger,            // ✅ From DI Container
    int $id                             // ✅ From route parameter
): ResponseInterface
```

---

## Complete Working Example

### Step 1: Create Controller

**File**: `src/Controllers/DemoMethodInjectionController.php`

```php
<?php

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\IJCalculator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class DemoMethodInjectionController
{
    /**
     * POST /api/demo/method-injection
     */
    public function demo(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator,           // Method injection!
        LoggerInterface $logger             // Method injection!
    ): ResponseInterface {
        try {
            $logger->info('Demo method injection called');

            $input = $request->getParsedBody();
            $result = $calculator->calculateAmount($input);

            return ResponseFormatter::success($response, [
                'message' => 'Method injection works!',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            $logger->error('Demo failed', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }
}
```

### Step 2: Register Route

**File**: `config/routes.php`

```php
<?php
use App\Controllers\DemoMethodInjectionController;

return function (App $app) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        // Method injection route
        $group->post('/demo/method-injection', [
            DemoMethodInjectionController::class,
            'demo'
        ]);
    });
};
```

### Step 3: Test

```bash
curl -X POST http://localhost:8000/api/demo/method-injection \
  -H "Content-Type: application/json" \
  -d '{
    "statut": "M",
    "classe": "A",
    "option": 100,
    "birth_date": "1989-09-26",
    "arrets": [{
      "arret-from-line": "2024-01-01",
      "arret-to-line": "2024-01-31"
    }]
  }'
```

---

## Benefits of Method Injection

✅ **Focused dependencies** - Only inject what you need per action
✅ **Cleaner controllers** - No unused dependencies in constructor
✅ **Better testability** - Mock only what each method uses
✅ **Flexibility** - Different methods, different dependencies
✅ **Route parameters** - Mix route params with DI seamlessly

## Key Takeaways

| Pattern | Best For | Example |
|---------|----------|---------|
| **Constructor Injection** | Shared dependencies | Logger used everywhere |
| **Method Injection** | Action-specific dependencies | Calculator for one endpoint |
| **Mixed** | Large controllers | Logger in constructor, specific deps per method |

---

## No Configuration Needed!

**The best part**: Method injection works **automatically** with PHP-DI in Slim 4. No extra configuration needed in `config/dependencies.php`!

Just add type-hinted parameters to your controller methods, and PHP-DI handles the rest! 🚀

---

## Summary

```php
// ❌ OLD WAY: Everything in constructor
class OldController {
    public function __construct(
        private IJCalculator $calc,
        private Logger $log,
        private RateRepo $rates,
        private ExportService $export
    ) {}
}

// ✅ NEW WAY: Only what each method needs
class NewController {
    public function calculate($req, $res, IJCalculator $calc) {}
    public function export($req, $res, ExportService $export) {}
    public function rates($req, $res, RateRepo $rates) {}
}
```

**Method injection = Leaner, cleaner, more flexible controllers!** 🎯
