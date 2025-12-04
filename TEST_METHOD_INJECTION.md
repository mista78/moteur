# Test Method Injection - Live Examples

## 🚀 Quick Start

### 1. Start the server
```bash
cd public && php -S localhost:8000
```

### 2. Test the endpoints

---

## 📋 Test Endpoints

### Example 1: Calculate with Method Injection

**Endpoint**: `POST /api/demo/calculate`

**Test Command**:
```bash
curl -X POST http://localhost:8000/api/demo/calculate \
  -H "Content-Type: application/json" \
  -d '{
    "statut": "M",
    "classe": "A",
    "option": 100,
    "birth_date": "1989-09-26",
    "current_date": "2024-09-09",
    "attestation_date": "2024-01-31",
    "affiliation_date": "2019-01-15",
    "nb_trimestres": 22,
    "arrets": [{
      "arret-from-line": "2024-01-01",
      "arret-to-line": "2024-01-31",
      "rechute-line": 0,
      "dt-line": 1,
      "gpm-member-line": 1
    }]
  }'
```

**What this demonstrates**:
- ✅ IJCalculator injected into method (not constructor)
- ✅ LoggerInterface also injected into method
- ✅ Both dependencies available only in this method

---

### Example 2: Get Rates with Method Injection

**Endpoint**: `GET /api/demo/rates`

**Test Command**:
```bash
curl http://localhost:8000/api/demo/rates
```

**What this demonstrates**:
- ✅ RateRepository injected into method
- ✅ Different method, different dependency
- ✅ No constructor needed!

---

### Example 3: Get Rate by Year (Route Parameter + Injection)

**Endpoint**: `GET /api/demo/rate/{year}`

**Test Commands**:
```bash
# Get rate for 2024
curl http://localhost:8000/api/demo/rate/2024

# Get rate for 2023
curl http://localhost:8000/api/demo/rate/2023

# Get rate for 2022
curl http://localhost:8000/api/demo/rate/2022
```

**What this demonstrates**:
- ✅ Route parameter `{year}` extracted automatically
- ✅ LoggerInterface injected from container
- ✅ Combining route params with DI

---

### Example 4: Advanced (Multiple Dependencies)

**Endpoint**: `POST /api/demo/advanced`

**Test Command**:
```bash
curl -X POST http://localhost:8000/api/demo/advanced \
  -H "Content-Type: application/json" \
  -d '{
    "statut": "M",
    "classe": "B",
    "option": 100,
    "birth_date": "1985-05-15",
    "current_date": "2024-09-09",
    "attestation_date": "2024-01-31",
    "affiliation_date": "2015-01-15",
    "nb_trimestres": 38,
    "arrets": [{
      "arret-from-line": "2024-01-01",
      "arret-to-line": "2024-01-15",
      "rechute-line": 0,
      "dt-line": 1,
      "gpm-member-line": 1
    }]
  }'
```

**What this demonstrates**:
- ✅ THREE dependencies in ONE method
- ✅ IJCalculator + RateRepository + LoggerInterface
- ✅ All injected automatically by PHP-DI

---

## 🔍 What to Look For in Responses

Each endpoint returns a response like:
```json
{
  "success": true,
  "data": {
    "message": "This result was calculated using METHOD INJECTION! 🎯",
    "injection_type": "method",
    "result": { ... }
  }
}
```

The `message` field confirms that method injection is working!

---

## 📝 Compare: Constructor vs Method Injection

### Traditional Controller (Constructor Injection)
```php
class TraditionalController
{
    // Dependencies declared in constructor
    public function __construct(
        private IJCalculator $calculator,
        private LoggerInterface $logger
    ) {}

    // All methods have access to these dependencies
    public function method1() { $this->calculator->... }
    public function method2() { $this->calculator->... }
}
```

### Method Injection Controller
```php
class MethodInjectionController
{
    // NO constructor needed!

    // Each method gets only what it needs
    public function method1(Request $r, Response $res, IJCalculator $calc) {
        return $calc->calculateAmount(...);
    }

    // Different method, different dependency
    public function method2(Request $r, Response $res, RateRepo $repo) {
        return $repo->loadRates();
    }
}
```

---

## 🎯 Key Differences

| Feature | Constructor Injection | Method Injection |
|---------|----------------------|------------------|
| **Scope** | All methods | Specific method only |
| **Dependencies** | Shared | Per-method |
| **Constructor** | Required | Not needed |
| **Flexibility** | Less | More |
| **Best for** | Shared services | Action-specific needs |

---

## 🔥 Pro Tips

1. **Mix both patterns**: Use constructor for shared deps, method for specific ones
2. **Route parameters**: They work seamlessly with method injection
3. **No configuration**: PHP-DI handles everything automatically
4. **Type hints required**: Dependencies must be type-hinted
5. **Testing**: Method injection makes unit testing easier

---

## 📚 See Also

- `METHOD_INJECTION_GUIDE.md` - Complete guide with all patterns
- `DEPENDENCY_INJECTION_GUIDE.md` - Constructor injection guide
- `DI_QUICK_REFERENCE.md` - Quick reference cheat sheet

---

## ✅ Verify It Works

After testing all endpoints, check the logs:
```bash
tail -f logs/app.log
```

You should see log entries from the injected LoggerInterface! 📝

---

## 🎊 Success!

If all curl commands return JSON responses with `"success": true`, then **method injection is working perfectly**! 🚀

No constructor needed, dependencies injected exactly where you need them!
