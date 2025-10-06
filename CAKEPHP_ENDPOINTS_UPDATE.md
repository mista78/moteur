# CakePHP Endpoints Update - JavaScript Integration

## Summary

Updated the JavaScript frontend (`webroot/js/ij-calculator.js`) to use CakePHP routing endpoints instead of `api.php` query parameters. Mock files are now loaded directly from the `/mocks/` folder in webroot.

## Changes Made

### 1. JavaScript File Updates

**File**: `webroot/js/ij-calculator.js`

#### Updated Constants
```javascript
// OLD
const API_URL = 'api.php';

// NEW
const BASE_URL = '/indemnite-journaliere';
const MOCKS_URL = '/mocks';
```

#### Updated API Endpoints

| Function | Old Endpoint | New Endpoint |
|----------|-------------|--------------|
| `calculateAll()` | `api.php?endpoint=calculate` | `/indemnite-journaliere/api-calculate.json` |
| `calculateDateEffet()` | `api.php?endpoint=date-effet` | `/indemnite-journaliere/api-date-effet.json` |
| `calculateEndPayment()` | `api.php?endpoint=end-payment` | `/indemnite-journaliere/api-end-payment.json` |
| `loadMockList()` | `api.php?endpoint=list-mocks` | `/indemnite-journaliere/api-list-mocks.json` |
| `loadMockData()` | `api.php?endpoint=load-mock&file=X` | `/mocks/X.json` (direct file) |

### 2. Controller Updates

**File**: `src/Controller/IndemniteJournaliereController.php`

Added three new API endpoint methods:

#### `apiDateEffet()` - Line 114
```php
/**
 * API endpoint to calculate date d'effet for arrêts
 * Endpoint: POST /indemnite-journaliere/api-date-effet.json
 */
public function apiDateEffet(): void
{
    // Calculates dates d'effet for work stoppage periods
    $result = $calculator->calculateDateEffet($arrets, $birthDate, $previousCumulDays);
}
```

#### `apiEndPayment()` - Line 154
```php
/**
 * API endpoint to calculate end payment dates
 * Endpoint: POST /indemnite-journaliere/api-end-payment.json
 */
public function apiEndPayment(): void
{
    // Calculates end of payment dates for three periods
    $result = $calculator->calculateEndPaymentDates($arrets, $previousCumulDays, $birthDate, $currentDate);
}
```

#### `apiListMocks()` - Line 195
```php
/**
 * API endpoint to list available mock files
 * Endpoint: GET /indemnite-journaliere/api-list-mocks.json
 */
public function apiListMocks(): void
{
    // Scans webroot/mocks/ and returns list of JSON files
    $mocksDir = ROOT . DS . 'webroot' . DS . 'mocks';
    // Returns array of filenames
}
```

### 3. Mock Files Organization

**Directory Created**: `webroot/mocks/`

All mock JSON files have been copied to this directory:
- `mock.json`
- `mock2.json` through `mock13.json`

Mock files are now directly accessible via HTTP at `/mocks/filename.json`

## API Endpoints Reference

### Complete Calculation
- **URL**: `POST /indemnite-journaliere/api-calculate.json`
- **Description**: Full IJ calculation with all results
- **Request Body**:
```json
{
  "statut": "M",
  "classe": "B",
  "option": "1",
  "birth_date": "1989-09-26",
  "current_date": "2024-09-09",
  "attestation_date": "2024-08-31",
  "last_payment_date": null,
  "affiliation_date": null,
  "nb_trimestres": 8,
  "previous_cumul_days": 0,
  "patho_anterior": false,
  "prorata": 1,
  "forced_rate": null,
  "pass_value": 47000,
  "arrets": [...]
}
```
- **Response**:
```json
{
  "success": true,
  "data": {
    "age": 35,
    "nb_jours": 30,
    "montant": 1234.56,
    "total_cumul_days": 30,
    "arrets": [...],
    "payment_details": [...],
    "end_payment_dates": {...}
  }
}
```

### Calculate Date d'Effet
- **URL**: `POST /indemnite-journaliere/api-date-effet.json`
- **Description**: Calculate effective dates for arrêts (90-day rule)
- **Request Body**:
```json
{
  "arrets": [...],
  "birth_date": "1989-09-26",
  "previous_cumul_days": 0
}
```
- **Response**:
```json
{
  "success": true,
  "data": [
    {
      "arret-from-line": "2024-01-01",
      "arret-to-line": "2024-01-15",
      "date-effet": "2024-01-01",
      "rechute-line": "0"
    }
  ]
}
```

### Calculate End Payment Dates
- **URL**: `POST /indemnite-journaliere/api-end-payment.json`
- **Description**: Calculate end dates for three payment periods (365/730/1095 days)
- **Request Body**:
```json
{
  "arrets": [...],
  "birth_date": "1989-09-26",
  "previous_cumul_days": 0,
  "current_date": "2024-09-09"
}
```
- **Response**:
```json
{
  "success": true,
  "data": {
    "end_period_1": "2025-01-01",
    "end_period_2": "2026-01-01",
    "end_period_3": "2027-01-01"
  }
}
```

### List Mock Files
- **URL**: `GET /indemnite-journaliere/api-list-mocks.json`
- **Description**: List all available mock JSON files
- **Response**:
```json
{
  "success": true,
  "data": [
    "mock.json",
    "mock2.json",
    "mock3.json",
    ...
  ]
}
```

### Load Mock Data (Direct File Access)
- **URL**: `GET /mocks/{filename}.json`
- **Description**: Direct access to mock JSON files
- **Example**: `GET /mocks/mock2.json`
- **Response**: Raw JSON array of arrêts
```json
[
  {
    "arret-from-line": "2024-01-01",
    "arret-to-line": "2024-01-15",
    "rechute-line": "0",
    ...
  }
]
```

## CakePHP Routing

CakePHP uses convention-based routing. The following URLs are automatically mapped:

- `/indemnite-journaliere/` → `IndemniteJournaliereController::index()`
- `/indemnite-journaliere/api-calculate.json` → `IndemniteJournaliereController::apiCalculate()`
- `/indemnite-journaliere/api-date-effet.json` → `IndemniteJournaliereController::apiDateEffet()`
- `/indemnite-journaliere/api-end-payment.json` → `IndemniteJournaliereController::apiEndPayment()`
- `/indemnite-journaliere/api-list-mocks.json` → `IndemniteJournaliereController::apiListMocks()`

Methods with camelCase names are automatically converted to dash-separated URLs.

## Testing

### Start CakePHP Server
```bash
cd /home/mista/work/ij
bin/cake server
```

### Access the Application
- **Web Interface**: http://localhost:8765/indemnite-journaliere
- **API Test**:
```bash
curl -X POST http://localhost:8765/indemnite-journaliere/api-calculate.json \
  -H "Content-Type: application/json" \
  -d '{"arrets":[],"classe":"B","statut":"M","birth_date":"1989-09-26","current_date":"2024-09-09"}'
```

### Test Mock Loading
1. Open http://localhost:8765/indemnite-journaliere
2. Mock buttons should appear automatically
3. Click any "Mock X" button to load test data
4. Verify arrêts are populated in the form

## File Structure

```
/home/mista/work/ij/
├── webroot/
│   ├── mocks/                          # Mock JSON files
│   │   ├── mock.json
│   │   ├── mock2.json
│   │   └── ... (mock3 through mock13)
│   ├── css/
│   │   └── ij-calculator.css
│   └── js/
│       └── ij-calculator.js            # Updated with CakePHP endpoints
├── src/
│   └── Controller/
│       └── IndemniteJournaliereController.php  # Updated with new methods
└── templates/
    ├── layout/
    │   └── ij.php
    └── IndemniteJournaliere/
        └── index.php
```

## Advantages of CakePHP Routing

1. **RESTful URLs**: Clean, semantic URLs following REST conventions
2. **Type Safety**: JSON view class with automatic serialization
3. **Error Handling**: Consistent error responses with HTTP status codes
4. **Convention**: No manual routing configuration needed
5. **Extensibility**: Easy to add new endpoints following the same pattern

## Compatibility

- ✅ **100% compatible** with the previous `api.php` implementation
- ✅ Same request/response format
- ✅ Same functionality
- ✅ Mocks load from organized directory structure

## Migration from api.php

The old `api.php` file is no longer used. All functionality has been migrated to CakePHP controller methods:

| Old api.php | New CakePHP Method |
|-------------|-------------------|
| `?endpoint=calculate` | `apiCalculate()` |
| `?endpoint=date-effet` | `apiDateEffet()` |
| `?endpoint=end-payment` | `apiEndPayment()` |
| `?endpoint=list-mocks` | `apiListMocks()` |
| `?endpoint=load-mock&file=X` | Direct file access `/mocks/X.json` |

## Next Steps (Optional)

1. **Add CSRF Protection**: Enable CSRF middleware for POST endpoints
2. **Rate Limiting**: Add rate limiting for API endpoints
3. **Caching**: Cache mock list to improve performance
4. **Validation**: Add request validation using CakePHP validators
5. **API Documentation**: Generate OpenAPI/Swagger documentation
6. **Unit Tests**: Write controller tests for all endpoints

## Conclusion

The JavaScript frontend now uses CakePHP routing for all API calls. The integration is complete and follows CakePHP best practices for API development.

All mock files are organized in `webroot/mocks/` and can be directly accessed via HTTP, simplifying the mock loading process.
