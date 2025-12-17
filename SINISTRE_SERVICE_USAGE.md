# SinistreService Usage Guide

## Overview

The `SinistreService` provides a clean, maintainable way to calculate `date-effet` for sinistres (claims) while maintaining **separation of concerns** and following **SOLID principles**.

## Architecture

```
Controller → SinistreService → DateService
   ↓              ↓                  ↓
 HTTP         Business            Date
Request      Orchestration      Calculations
```

### Why This Approach?

✅ **Separation of Concerns**: Models handle data, Services handle business logic
✅ **Single Responsibility**: Each layer has one job
✅ **Easy Testing**: Services can be unit tested in isolation
✅ **Maintainability**: Business logic is centralized, not scattered in models
✅ **Consistency**: Follows existing architecture (RateService, DateService, etc.)

## API Endpoints

### 1. Get Single Sinistre with Date-Effet

```http
GET /api/sinistres/{id}/date-effet
```

**Example:**
```bash
curl http://localhost:8000/api/sinistres/123/date-effet
```

**Response:**
```json
{
  "success": true,
  "data": {
    "sinistre": {
      "id": 123,
      "adherent_number": "123456",
      "date_debut": "2024-01-01",
      "date_fin": "2024-03-31",
      "arrets": [...]
    },
    "arrets_with_date_effet": [
      {
        "arret-from-line": "2024-01-01",
        "arret-to-line": "2024-01-31",
        "date-effet": "2024-04-01",
        "decompte_days": 0,
        "is_rechute": false
      }
    ]
  }
}
```

### 2. Get Sinistre for Specific Adherent

```http
GET /api/adherents/{adherent_number}/sinistres/{id}/date-effet
```

**Example:**
```bash
curl http://localhost:8000/api/adherents/123456/sinistres/123/date-effet
```

This ensures the sinistre belongs to the specified adherent (security check).

### 3. Get All Sinistres for Adherent

```http
GET /api/adherents/{adherent_number}/sinistres/date-effet
```

**Example:**
```bash
curl http://localhost:8000/api/adherents/123456/sinistres/date-effet
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "sinistre": {...},
      "arrets_with_date_effet": [...]
    },
    {
      "sinistre": {...},
      "arrets_with_date_effet": [...]
    }
  ]
}
```

## Usage in Code

### Using in Controllers (Dependency Injection)

```php
use App\Controllers\SinistreController;
use App\Services\SinistreServiceInterface;
use Psr\Log\LoggerInterface;

class SinistreController
{
    public function __construct(
        SinistreServiceInterface $sinistreService,
        LoggerInterface $logger
    ) {
        $this->sinistreService = $sinistreService;
        $this->logger = $logger;
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $sinistreId = (int) $args['id'];

        // Business logic is in the service, not the controller
        $data = $this->sinistreService->getSinistreWithDateEffet($sinistreId);

        return ResponseFormatter::success($response, $data);
    }
}
```

### Using in Custom Code

```php
use App\Services\SinistreService;
use App\Services\DateService;

// Manual instantiation (for scripts, tests, etc.)
$dateService = new DateService();
$sinistreService = new SinistreService($dateService);

// Get data
$result = $sinistreService->getSinistreWithDateEffet(123);

// Access the data
$sinistre = $result['sinistre'];
$arrets = $result['arrets_with_date_effet'];

foreach ($arrets as $arret) {
    echo "Date effet: " . $arret['date-effet'] . "\n";
    echo "Is rechute: " . ($arret['is_rechute'] ? 'Yes' : 'No') . "\n";
}
```

### Using via Dependency Injection Container

```php
// The service is auto-wired in config/dependencies.php
// Just type-hint it in your constructor

use App\Services\SinistreServiceInterface;

class MyCustomService
{
    private SinistreServiceInterface $sinistreService;

    public function __construct(SinistreServiceInterface $sinistreService)
    {
        $this->sinistreService = $sinistreService;
    }

    public function processAdherent(string $adherentNumber)
    {
        $sinistres = $this->sinistreService->getAllSinistresWithDateEffet($adherentNumber);

        // Process the data...
    }
}
```

## Service Methods

### `getSinistreWithDateEffet(int $sinistreId): array`

Get a single sinistre with calculated date-effet for all arrets.

**Parameters:**
- `$sinistreId` - The sinistre ID

**Returns:**
```php
[
    'sinistre' => IjSinistre,
    'arrets_with_date_effet' => array
]
```

**Throws:**
- `RuntimeException` if sinistre not found

---

### `getSinistreWithDateEffetForAdherent(string $adherentNumber, int $sinistreId): array`

Get a sinistre with date-effet, ensuring it belongs to the specified adherent.

**Parameters:**
- `$adherentNumber` - The adherent number
- `$sinistreId` - The sinistre ID

**Returns:**
```php
[
    'sinistre' => IjSinistre,
    'arrets_with_date_effet' => array
]
```

**Throws:**
- `RuntimeException` if sinistre not found or doesn't belong to adherent

---

### `getAllSinistresWithDateEffet(string $adherentNumber): array`

Get all sinistres for an adherent with date-effet calculated.

**Parameters:**
- `$adherentNumber` - The adherent number

**Returns:**
```php
[
    [
        'sinistre' => IjSinistre,
        'arrets_with_date_effet' => array
    ],
    // ... more sinistres
]
```

## Performance Considerations

### Eager Loading

The service uses **eager loading** to avoid N+1 queries:

```php
// ✅ GOOD - Loads sinistre with arrets and adherent in ONE query
$sinistre = IjSinistre::with(['arrets', 'adherent'])->find($id);

// ❌ BAD - Would cause N+1 queries
$sinistre = IjSinistre::find($id);
$arrets = $sinistre->arrets; // Separate query
$adherent = $sinistre->adherent; // Another query
```

### Caching (Optional Enhancement)

If needed, you can add caching to the service:

```php
public function getSinistreWithDateEffet(int $sinistreId): array
{
    $cacheKey = "sinistre_date_effet_{$sinistreId}";

    // Check cache first
    if ($cached = Cache::get($cacheKey)) {
        return $cached;
    }

    // Calculate
    $result = $this->calculateDateEffetForSinistre(...);

    // Cache for 1 hour
    Cache::put($cacheKey, $result, 3600);

    return $result;
}
```

## Testing

### Unit Testing the Service

```php
use PHPUnit\Framework\TestCase;
use App\Services\SinistreService;
use App\Services\DateService;

class SinistreServiceTest extends TestCase
{
    private SinistreService $service;
    private DateService $dateService;

    protected function setUp(): void
    {
        $this->dateService = $this->createMock(DateService::class);
        $this->service = new SinistreService($this->dateService);
    }

    public function testGetSinistreWithDateEffet()
    {
        // Mock the DateService behavior
        $this->dateService
            ->expects($this->once())
            ->method('calculateDateEffet')
            ->willReturn([
                ['date-effet' => '2024-04-01']
            ]);

        // Test the service
        $result = $this->service->getSinistreWithDateEffet(123);

        $this->assertArrayHasKey('sinistre', $result);
        $this->assertArrayHasKey('arrets_with_date_effet', $result);
    }
}
```

## Why NOT Put This in the Model?

### ❌ Bad Practice: Business Logic in Model

```php
// DON'T DO THIS
class IjSinistre extends Model
{
    public function calculateDateEffet()
    {
        // 240 lines of complex business logic...
        // 90-day threshold calculations...
        // Rechute detection...
        // Penalties...
    }
}
```

**Problems:**
1. Violates Single Responsibility Principle
2. Hard to test (requires database)
3. Tight coupling
4. Breaks existing architecture
5. Can't mock dependencies

### ✅ Good Practice: Business Logic in Service

```php
// DO THIS
class SinistreService
{
    public function __construct(DateCalculationInterface $dateService)
    {
        $this->dateService = $dateService;
    }

    public function getSinistreWithDateEffet(int $id): array
    {
        $sinistre = IjSinistre::with(['arrets', 'adherent'])->find($id);

        // Delegate to service layer
        $arretsWithDateEffet = $this->dateService->calculateDateEffet(...);

        return ['sinistre' => $sinistre, 'arrets_with_date_effet' => $arretsWithDateEffet];
    }
}
```

**Benefits:**
1. ✅ Separation of concerns
2. ✅ Easy to test
3. ✅ Follows SOLID principles
4. ✅ Consistent with existing architecture
5. ✅ Dependencies can be mocked

## Swagger/OpenAPI Documentation

All endpoints are documented with OpenAPI attributes. Access the interactive documentation at:

```
http://localhost:8000/api-docs
```

## See Also

- `DEPENDENCY_INJECTION_GUIDE.md` - How DI works in this project
- `SLIM_MIGRATION_PLAN.md` - Architecture overview
- `src/Services/DateService.php` - Date-effet calculation logic
- `src/Controllers/SinistreController.php` - Controller implementation
- `config/dependencies.php` - Service registration
