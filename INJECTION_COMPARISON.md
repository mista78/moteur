# Dependency Injection: Constructor vs Method

## Visual Comparison

### ❌ Constructor Injection Only

```php
<?php
namespace App\Controllers;

class OldWayController
{
    // ALL dependencies in constructor
    private IJCalculator $calculator;
    private RateRepository $rateRepo;
    private LoggerInterface $logger;
    private ExportService $export;

    // Constructor gets heavy
    public function __construct(
        IJCalculator $calculator,
        RateRepository $rateRepo,
        LoggerInterface $logger,
        ExportService $export
    ) {
        $this->calculator = $calculator;
        $this->rateRepo = $rateRepo;
        $this->logger = $logger;
        $this->export = $export;
    }

    // Method 1: Only uses calculator
    public function calculate($request, $response) {
        $result = $this->calculator->calculateAmount(...);
        // ⚠️ Other 3 dependencies unused here
    }

    // Method 2: Only uses rateRepo
    public function getRates($request, $response) {
        $rates = $this->rateRepo->loadRates();
        // ⚠️ Other 3 dependencies unused here
    }

    // Method 3: Only uses export
    public function export($request, $response) {
        $pdf = $this->export->toPdf(...);
        // ⚠️ Other 3 dependencies unused here
    }
}
```

**Problems**:
- 🔴 Heavy constructor with ALL dependencies
- 🔴 Most dependencies unused in most methods
- 🔴 Difficult to test (must mock everything)
- 🔴 Unclear which method needs what

---

### ✅ Method Injection (Modern Way)

```php
<?php
namespace App\Controllers;

class ModernWayController
{
    // NO constructor needed! (or only shared deps)

    // Method 1: Only gets what it needs
    public function calculate(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator              // ✅ Only this
    ): ResponseInterface {
        $result = $calculator->calculateAmount(...);
        return ResponseFormatter::success($response, $result);
    }

    // Method 2: Different dependency
    public function getRates(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RateRepository $rateRepo              // ✅ Only this
    ): ResponseInterface {
        $rates = $rateRepo->loadRates();
        return ResponseFormatter::success($response, $rates);
    }

    // Method 3: Another different dependency
    public function export(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ExportService $export                 // ✅ Only this
    ): ResponseInterface {
        $pdf = $export->toPdf(...);
        return $response->withBody($pdf);
    }
}
```

**Benefits**:
- ✅ No heavy constructor
- ✅ Each method gets only what it needs
- ✅ Easy to test (mock only what's used)
- ✅ Crystal clear dependencies per action

---

## Side-by-Side: Real Example

### Constructor Injection

```php
class CalculationController
{
    // Step 1: Declare properties
    private IJCalculator $calculator;
    private LoggerInterface $logger;

    // Step 2: Constructor
    public function __construct(
        IJCalculator $calculator,
        LoggerInterface $logger
    ) {
        $this->calculator = $calculator;
        $this->logger = $logger;
    }

    // Step 3: Use via $this->
    public function calculate($req, $res) {
        $result = $this->calculator->calculateAmount(...);
        $this->logger->info('Done');
        return ResponseFormatter::success($res, $result);
    }
}
```

### Method Injection

```php
class CalculationController
{
    // No constructor!
    // No properties!

    // Just use it directly
    public function calculate(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator,      // Injected here
        LoggerInterface $logger        // Injected here
    ): ResponseInterface {
        $result = $calculator->calculateAmount(...);
        $logger->info('Done');
        return ResponseFormatter::success($response, $result);
    }
}
```

**Lines of code**:
- Constructor way: **~15 lines**
- Method way: **~10 lines**

---

## When to Use Each

### Use Constructor Injection When:
✅ Dependency used in **multiple methods**
✅ Dependency is **core** to class functionality
✅ You want to **enforce** dependency at object creation

```php
class ReportController
{
    // Logger used in ALL methods
    public function __construct(private LoggerInterface $logger) {}

    public function daily() { $this->logger->info(...); }
    public function weekly() { $this->logger->info(...); }
    public function monthly() { $this->logger->info(...); }
}
```

### Use Method Injection When:
✅ Dependency used in **one method only**
✅ Different methods need **different dependencies**
✅ You want **lightweight, focused** actions

```php
class ApiController
{
    // Each method gets only what it needs
    public function calculate($req, $res, IJCalculator $calc) { ... }
    public function export($req, $res, ExportService $export) { ... }
    public function report($req, $res, ReportGenerator $gen) { ... }
}
```

### Best of Both Worlds:
✅ **Mix them!** Constructor for shared, method for specific

```php
class HybridController
{
    // Constructor: Shared deps
    public function __construct(private LoggerInterface $logger) {}

    // Method 1: Action-specific dep
    public function calculate($req, $res, IJCalculator $calc) {
        $this->logger->info('Starting');  // Constructor
        return $calc->calculateAmount(...); // Method
    }

    // Method 2: Different action-specific dep
    public function export($req, $res, ExportService $export) {
        $this->logger->info('Exporting');  // Constructor
        return $export->toPdf(...);        // Method
    }
}
```

---

## Quick Decision Tree

```
Do you need this dependency in multiple methods?
├─ YES → Constructor Injection
│         public function __construct(Dep $dep) {}
│
└─ NO → Method Injection
          public function action($req, $res, Dep $dep) {}
```

---

## Summary Table

| Aspect | Constructor | Method |
|--------|------------|---------|
| **Scope** | Class-wide | Method-only |
| **Setup** | Constructor + Properties | Just method params |
| **Clarity** | Deps at top of class | Deps right where used |
| **Testing** | Mock everything | Mock only what's needed |
| **Flexibility** | Less | More |
| **Code** | More lines | Fewer lines |
| **Best for** | Shared services | Action-specific needs |

---

## Live Example in This Project

See the demo controller at:
- **File**: `src/Controllers/MethodInjectionDemoController.php`
- **Routes**: `/api/demo/*`
- **Tests**: `TEST_METHOD_INJECTION.md`

```bash
# Start server
cd public && php -S localhost:8000

# Test method injection
curl http://localhost:8000/api/demo/rates
```

---

## Configuration Required?

**NO!** ❌

Both patterns work automatically with PHP-DI in Slim 4:
- ✅ Constructor injection: Auto-wired
- ✅ Method injection: Auto-wired
- ✅ No changes needed in `config/dependencies.php`

Just add type-hinted parameters and PHP-DI handles the rest! 🚀

---

## Further Reading

- `DEPENDENCY_INJECTION_GUIDE.md` - Constructor injection patterns
- `METHOD_INJECTION_GUIDE.md` - Method injection patterns and examples
- `DI_QUICK_REFERENCE.md` - Quick reference cheat sheet
