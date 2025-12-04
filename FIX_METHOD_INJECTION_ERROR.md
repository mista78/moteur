# Fix: Method Injection TypeError in Slim 4

## The Error

```
TypeError: App\Controllers\HomeController::index(): Argument #3 ($calculator)
must be of type App\IJCalculator, array given
```

## Why This Happens

Slim's **default invocation strategy** (`RequestResponse`) only passes:
1. `ServerRequestInterface $request`
2. `ResponseInterface $response`
3. `array $args` (route parameters like `{id}`)

It **doesn't resolve dependencies** from the container for additional parameters.

## The Fix: Use PHP-DI Bridge

There are **2 ways** to fix this:

---

## Option 1: Use DI Bridge (Recommended)

### Step 1: Install PHP-DI Bridge (if not installed)

```bash
composer require php-di/slim-bridge
```

### Step 2: Modify `public/index.php`

**BEFORE** (current code that causes error):
```php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Slim\Factory\AppFactory;

// Build container
$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../config/dependencies.php');
$container = $containerBuilder->build();

// Create app with container
AppFactory::setContainer($container);
$app = AppFactory::create();  // ❌ This uses default Slim invocation strategy

// ... routes, middleware, etc.
$app->run();
```

**AFTER** (use DI Bridge):
```php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use DI\Bridge\Slim\Bridge;  // ✅ Add this

// Build container
$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../config/dependencies.php');
$container = $containerBuilder->build();

// Create app using Bridge (enables method injection)
$app = Bridge::create($container);  // ✅ Use Bridge instead of AppFactory

// ... routes, middleware, etc.
$app->run();
```

**That's it!** This single change enables method injection throughout your app.

---

## Option 2: Custom Invocation Strategy (Alternative)

If you can't use Bridge, manually set the invocation strategy:

### Step 1: Create Custom Invoker

**File**: `src/Helpers/ControllerInvoker.php`

```php
<?php

namespace App\Helpers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class ControllerInvoker implements InvocationStrategyInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        // Get method reflection
        if (is_array($callable)) {
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflection = new \ReflectionFunction($callable);
        }

        // Build parameters
        $parameters = [];
        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();

            if (!$type || $type->isBuiltin()) {
                // Primitive type - check route arguments
                if (isset($routeArguments[$param->getName()])) {
                    $parameters[] = $routeArguments[$param->getName()];
                } else {
                    $parameters[] = null;
                }
            } elseif ($type->getName() === ServerRequestInterface::class) {
                $parameters[] = $request;
            } elseif ($type->getName() === ResponseInterface::class) {
                $parameters[] = $response;
            } else {
                // Resolve from container
                $parameters[] = $this->container->get($type->getName());
            }
        }

        return call_user_func_array($callable, $parameters);
    }
}
```

### Step 2: Use Custom Invoker in `public/index.php`

```php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Helpers\ControllerInvoker;

// Build container
$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../config/dependencies.php');
$container = $containerBuilder->build();

// Create app
$app = AppFactory::setContainer($container);
$app = AppFactory::create();

// Set custom invocation strategy
$routeCollector = $app->getRouteCollector();
$routeCollector->setDefaultInvocationStrategy(new ControllerInvoker($container));

// ... routes, middleware, etc.
$app->run();
```

---

## Verify It Works

### Your Controller (should work now):

```php
<?php

namespace App\Controllers;

use App\IJCalculator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator  // ✅ Now this works!
    ): ResponseInterface {
        // Use calculator
        $result = $calculator->calculateAmount($request->getParsedBody());

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

### Test:

```bash
curl http://localhost:8000/
```

Should work without TypeError! ✅

---

## Comparison: Before vs After

### Before (Error)
```
Slim's default strategy: Request + Response + array $args
                                                    ↑
                                    Tries to pass this as IJCalculator
                                              = TypeError ❌
```

### After (Works)
```
DI Bridge / Custom Invoker: Resolves dependencies from container
                           Request + Response + IJCalculator from DI
                                                ↑
                                    Properly resolved from container ✅
```

---

## Which Option Should You Use?

| Option | Pros | Cons |
|--------|------|------|
| **Option 1: DI Bridge** | ✅ Simple (1 line change)<br>✅ Official solution<br>✅ Well tested | Requires composer package |
| **Option 2: Custom Invoker** | ✅ No extra package<br>✅ Full control | More code to maintain |

**Recommendation**: Use **Option 1 (DI Bridge)** - it's simpler and official.

---

## Quick Fix Summary

**Single line change in `public/index.php`:**

```php
// OLD:
$app = AppFactory::create();

// NEW:
$app = Bridge::create($container);
```

Done! 🎉

---

## Additional Notes

- **Constructor injection** works with either approach (no changes needed)
- **Method injection** requires one of these fixes
- This fix enables method injection **globally** for all routes
- Both `Request`/`Response` and container dependencies work together

---

## Troubleshooting

### Still getting errors?

1. **Check composer**: `composer require php-di/slim-bridge`
2. **Clear cache**: Delete any cached container files
3. **Check imports**: Make sure you import `DI\Bridge\Slim\Bridge`
4. **Restart server**: Stop and restart PHP built-in server

### Bridge not found?

Install it:
```bash
composer require php-di/slim-bridge
```

---

## See Also

- `METHOD_INJECTION_GUIDE.md` - Complete guide to method injection
- `INJECTION_COMPARISON.md` - Constructor vs method injection
- PHP-DI Slim Bridge docs: https://php-di.org/doc/frameworks/slim.html
