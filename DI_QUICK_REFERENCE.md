# Dependency Injection - Quick Reference

## The 3-Step Pattern

### 1️⃣ Define in `config/dependencies.php`

```php
<?php
use App\IJCalculator;
use App\Repositories\RateRepository;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        // Define how to create IJCalculator
        IJCalculator::class => function (ContainerInterface $c) {
            $rateRepo = $c->get(RateRepository::class);
            return new IJCalculator($rateRepo->loadRates());
        },
    ]);
};
```

### 2️⃣ Inject in Controller Constructor

```php
<?php
namespace App\Controllers;

use App\IJCalculator;
use Psr\Log\LoggerInterface;

class YourController
{
    // Declare dependencies
    private IJCalculator $calculator;
    private LoggerInterface $logger;

    // Constructor receives dependencies (auto-injected by PHP-DI)
    public function __construct(IJCalculator $calculator, LoggerInterface $logger)
    {
        $this->calculator = $calculator;
        $this->logger = $logger;
    }

    // ... controller methods ...
}
```

### 3️⃣ Use in Controller Methods

```php
<?php
public function calculate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    try {
        $input = $request->getParsedBody();

        // Use the injected calculator
        $result = $this->calculator->calculateAmount($input);

        // Use the injected logger
        $this->logger->info('Calculation completed', ['result' => $result]);

        return ResponseFormatter::success($response, $result);

    } catch (Exception $e) {
        $this->logger->error('Error', ['error' => $e->getMessage()]);
        return ResponseFormatter::error($response, $e->getMessage());
    }
}
```

## Available Dependencies (Pre-configured)

| Dependency | Type | Purpose |
|------------|------|---------|
| `IJCalculator::class` | `App\IJCalculator` | Main calculation engine |
| `LoggerInterface::class` | `Psr\Log\LoggerInterface` | Logging service |
| `RateRepository::class` | `App\Repositories\RateRepository` | Rate data loader |
| `Capsule::class` | `Illuminate\Database\Capsule\Manager` | Eloquent ORM |

## Common IJCalculator Methods

```php
// Calculate amount
$result = $this->calculator->calculateAmount($input);
// Returns: ['montant' => float, 'nb_jours' => int, 'details' => array, ...]

// Set PASS value
$this->calculator->setPassValue(42000.0);

// Determine class from revenue
$classe = $this->calculator->determineClasse($revenuNMoins2, $dateOuverture, $taxeOffice);
// Returns: 'A', 'B', or 'C'

// Calculate date effet
$dateEffet = $this->calculator->calculateDateEffet($arrets, $birthDate);
// Returns: string date 'Y-m-d'

// Get daily rate
$rate = $this->calculator->getDailyRate($statut, $classe, $option, $taux, $year);
// Returns: float
```

## Example: Complete New Controller

```php
<?php

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\IJCalculator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class NewController
{
    private IJCalculator $calculator;
    private LoggerInterface $logger;

    // Step 1: Inject dependencies
    public function __construct(IJCalculator $calculator, LoggerInterface $logger)
    {
        $this->calculator = $calculator;
        $this->logger = $logger;
    }

    // Step 2: Create endpoint method
    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $input = $request->getParsedBody();

            // Step 3: Use dependencies
            $result = $this->calculator->calculateAmount($input);
            $this->logger->info('Processing completed');

            return ResponseFormatter::success($response, $result);

        } catch (\Exception $e) {
            $this->logger->error('Processing failed', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }
}
```

## Register Route

Add to `config/routes.php`:

```php
<?php

use App\Controllers\NewController;

return function (App $app) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->post('/new-endpoint', [NewController::class, 'process']);
    });
};
```

## Testing Your Endpoint

```bash
curl -X POST http://localhost:8000/api/new-endpoint \
  -H "Content-Type: application/json" \
  -d '{"statut": "M", "classe": "A", "arrets": [...]}'
```

## Key Benefits

✅ **No manual instantiation** - PHP-DI handles everything
✅ **Type safety** - Type hints ensure correct dependencies
✅ **Easy testing** - Mock dependencies in unit tests
✅ **Centralized config** - All wiring in one place
✅ **Loose coupling** - Controllers don't know how dependencies are created

## Full Documentation

See `DEPENDENCY_INJECTION_GUIDE.md` for complete examples and advanced patterns.
