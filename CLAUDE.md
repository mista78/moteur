# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

French medical professional sick leave benefits calculator ("Indemnités Journalières" - IJ) for CARMF (Caisse Autonome de Retraite des Médecins de France). The system calculates daily benefits based on:
- Contribution class (A/B/C) and professional status (M/RSPM/CCPL)
- Age brackets (<62, 62-69, 70+) with different rate periods
- Affiliation quarters and pathology anterior status
- Complex 27-rate system with historical rate tables (2022-2025)
- 90-day threshold for initial benefits, 15-day threshold for relapses (rechute)

## Development Commands

### Running Tests

**Run all tests** (~255+ tests: unit + integration):
```bash
php run_all_tests.php
```

**Individual service tests**:
```bash
php Tests/RateServiceTest.php              # 13 tests
php Tests/DateServiceTest.php              # 17 tests
php Tests/TauxDeterminationServiceTest.php # 16 tests
php Tests/AmountCalculationServiceTest.php # Tests for amount calculations
php Tests/RechuteTest.php                  # 10 rechute-specific tests
```

**Integration tests**:
```bash
php test_mocks.php           # 18+ integration tests with real scenarios
php test_rechute_integration.php  # Rechute integration tests
php test_decompte.php        # Decompte (non-paid days) tests
```

**Debug specific scenarios**:
```bash
php debug_mock9.php   # Age 70 transition
php debug_mock20.php  # Period 2 calculations (62-69 age)
php debug_mock23.php  # Complex multi-period scenarios
php debug_mock2.php   # Multiple stoppages with rechute
```

### Development Server

**PHP built-in server** (recommended for standalone):
```bash
php -S localhost:8000
```
Access at: `http://localhost:8000`

**CakePHP server** (for CakePHP integration):
```bash
bin/cake server
```
Access at: `http://localhost:8765/indemnite-journaliere`

## Core Architecture

The project uses a **service-oriented architecture** following SOLID principles, with two parallel implementations:

### 1. Standalone PHP Implementation (Primary)
- **Entry point**: `IJCalculator.php` (690 lines)
- **Services**: Modular services in `/Services/` directory (~1,800 lines)
- **API**: RESTful endpoints via `api.php`
- **Frontend**: Web interface (`index.html` + `app.js` + `calendar_functions.js`)
- **Tests**: Unit tests in `/Tests/`, integration tests in root directory

### 2. CakePHP 5 Integration (Optional)
- **Location**: `src/` directory
- **Purpose**: MVC structure with database persistence
- **Controllers**: `src/Controller/` - API endpoints
- **Models**: `src/Model/` - Database entities (Calculations, Arrets)
- **Migrations**: `config/Migrations/` - Database schema

### Service Layer (SOLID Architecture)

All services implement interfaces and are located in `/Services/`:

**RateService** (`RateServiceInterface.php`) - 156 lines:
- `getDailyRate()`: Calculate daily rate from CSV based on status, class, age, tier
- `getRateForYear()` / `getRateForDate()`: Retrieve rate data for specific periods
- Handles tier determination (1/2/3 for periods) and option multipliers (CCPL/RSPM)

**DateService** (`DateCalculationInterface.php`) - 685 lines:
- `calculateAge()`: Age calculation at specific date
- `calculateTrimesters()`: Affiliation quarters with Q1-Q4 rounding rules
- `mergeProlongations()`: Merge consecutive work stoppages
- `calculateDateEffet()`: Rights opening dates (90-day rule, 15-day for rechute)
- `calculatePayableDays()`: Payable days per period considering attestation dates
- `calculateDecompteDays()`: Non-paid days before date-effet (décompte feature)
- `isRechute()`: Detect relapse conditions (< 1 year, non-consecutive, rights opened)

**TauxDeterminationService** (`TauxDeterminationInterface.php`) - 112 lines:
- `determineTauxNumber()`: Rate number (1-9) from age/quarters/pathology decision tree
- `determineClasse()`: Contribution class (A/B/C) from N-2 revenue

**AmountCalculationService** (`AmountCalculationInterface.php`) - 630 lines:
- `calculateTotalAmount()`: Main orchestration of entire calculation pipeline
- Integrates all services, handles multi-period calculations, returns detailed breakdown

### Critical Business Rules

**Rechute (Relapse) Detection** - `DateService::isRechute()` (line 204):
- **CRITICAL**: An arret is only a rechute if rights were already opened (previous date-effet exists)
- If 90-day threshold not reached, subsequent arrets accumulate days (NOT relapses)
- Rechute criteria (ALL must be met):
  1. Previous arret has date-effet (rights opened)
  2. NOT consecutive (gap between stoppages)
  3. Starts within 1 year after previous arret ended
- Rechute uses **15-day threshold** (vs 91-day for new pathology)
- Penalties reduced to 15 days (vs 31 days) for DT/GPM on rechute

**90-Day Threshold Rule**:
- Initial pathology: Benefits begin at day 91 (90 days décompte)
- Cumulative calculation across all arrêts for same pathology
- Days before date-effet are "décompte" (counted but not paid)

**Age-Based Period System**:
- **< 62 years**: Taux 1-3 (single rate throughout)
- **62-69 years**: Three periods with different rates
  - Period 1 (days 1-365): Full rate
  - Period 2 (days 366-730): Rate -25% (taux 7-9)
  - Period 3 (days 731-1095): Reduced senior rate (taux 4-6)
- **≥ 70 years**: Maximum 365 days, reduced rates (taux 4-6)

**27-Rate System** (9 taux × 3 classes):
- Taux 1-3: <62 years (full, -1/3 reduction, -2/3 reduction)
- Taux 4-6: ≥70 years (senior reduced, -1/3, -2/3)
- Taux 7-9: 62-69 years Period 2 (full-25%, -1/3, -2/3)
- Classes A/B/C multiply base rate by contribution level
- See RATE_RULES.md for complete decision tree

**Pathology Anterior Reductions**:
- < 8 quarters: NO benefits (ineligible)
- 8-15 quarters: -1/3 reduction (taux +1)
- 16-23 quarters: -2/3 reduction (taux +2)
- ≥ 24 quarters: Full rate (no reduction)

**Trimester Calculation** - `DateService::calculateTrimesters()` (line 31):
- Quarters: Q1 (Jan-Mar), Q2 (Apr-Jun), Q3 (Jul-Sep), Q4 (Oct-Dec)
- **Partial quarters count as complete** (rounding rule)
- Example: 2019-01-15 to 2024-04-11 = (5 years × 4) + (Q2 - Q1) + 1 = 22 quarters
- Trimesters calculated from affiliation date to **first arret date** (NOT payment start)
- See TRIMESTER_CALCULATION_FIX.md for detailed examples

**Décompte Feature** (Non-Paid Days):
- "Décompte" = days counted toward threshold but not paid
- Days before date-effet are décompte (90 for new pathology, 14 for rechute)
- Displayed separately in web interface with yellow highlighting
- Implementation: `DateService::calculateDecompteDays()` (line 628)

## Data Structures

### Input Format (POST to api.php or mock.json)

```json
{
  "statut": "M",                        // M=Médecin, RSPM, CCPL
  "classe": "A",                        // A/B/C contribution class
  "option": 100,                        // Option multiplier (25, 50, 75, 100)
  "birth_date": "1960-01-15",
  "current_date": "2024-01-15",         // Calculation date
  "attestation_date": "2024-01-31",     // Last medical attestation
  "last_payment_date": null,
  "affiliation_date": "2019-01-15",     // CARMF affiliation start
  "nb_trimestres": 22,                  // Auto-calculated if affiliation_date provided
  "previous_cumul_days": 0,             // Cumulative days from prior calculations
  "patho_anterior": false,              // Pathology existed before affiliation
  "prorata": 1,                         // Prorata multiplier (usually 1)
  "pass_value": 47000,                  // PASS (Plafond Annuel SS) for year
  "arrets": [
    {
      "arret-from-line": "2023-09-04",
      "arret-to-line": "2023-11-10",
      "rechute-line": 0,                // 0=new pathology, 1=rechute (auto-detected)
      "dt-line": 1,                     // 1=late declaration penalty applies
      "gpm-member-line": 1,             // 1=GPM account update penalty
      "declaration-date-line": "2023-09-19"
    }
  ]
}
```

### Rate CSV Format (taux.csv)

Historical rates by year, class, and period:

```csv
id;date_start;date_end;taux_a1;taux_a2;taux_a3;taux_b1;taux_b2;taux_b3;taux_c1;taux_c2;taux_c3
1;2024-01-01;2024-12-31;75.06;38.3;56.3;112.59;57.45;84.45;150.12;76.6;112.59
```

Columns:
- `taux_X1`: Period 1 rate (days 1-365)
- `taux_X2`: Period 2 rate (days 366-730) - for age 62-69 only
- `taux_X3`: Period 3 rate (days 731-1095) - for age 62-69 only
- X = a/b/c for classes A/B/C

### Output Format (API Response)

```json
{
  "success": true,
  "data": {
    "nb_jours": 59,
    "montant": 4428.54,
    "age": 64,
    "total_cumul_days": 59,
    "end_payment_dates": {
      "end_period_1": "2024-12-02",
      "end_period_2": "2025-11-02",
      "end_period_3": "2026-10-02"
    },
    "payment_details": [
      {
        "arret_index": 0,
        "arret_from": "2023-09-04",
        "arret_to": "2023-11-10",
        "date-effet": "2023-12-03",
        "nb_jours": 59,
        "decompte_days": 90,
        "daily_breakdown": [
          {
            "date": "2023-12-03",
            "rate": 75.06,
            "amount": 75.06,
            "taux": 1,
            "period": 1
          }
        ]
      }
    ]
  }
}
```

## API Endpoints (api.php)

All endpoints accept JSON POST data unless noted:

**Full Calculation** (most commonly used):
```bash
POST /api.php?endpoint=calculate
```
Returns complete calculation with amounts, dates, daily breakdown

**Calculate Date Effet** (90-day rule):
```bash
POST /api.php?endpoint=date-effet
```
Returns rights opening dates for each arret

**Calculate End Payment Dates**:
```bash
POST /api.php?endpoint=end-payment
```
Returns period 1/2/3 end dates based on age

**Calculate Revenue (PASS-based)**:
```bash
POST /api.php?endpoint=revenu
```
Determines revenue from class and PASS value

**Load Mock Data** (test data):
```bash
GET /api.php?endpoint=load-mock
```
Returns mock.json for testing interface

## Test Data & Mock Scenarios

28+ integration test scenarios in root directory and `webroot/mocks/`:

**Key test scenarios**:
- `mock.json`: Basic single arret (750.60€)
- `mock2.json`: Multiple stoppages with rechute (17318.92€)
- `mock7.json`: CCPL status with pathology anterior (74331.79€)
- `mock9.json`: Age 70 transition case (53467.98€)
- `mock10.json`: Age 62-69 Period 2 calculations (51744.25€)
- `mock20.json`: Complex multi-period scenario
- `mock28.json`: Recent comprehensive test

**Test coverage**: ~255+ total tests
- Unit tests: 56 (Rate, Date, Taux, Amount services, Rechute)
- Integration tests: 18+ (test_mocks.php)
- Specialized: Decompte, trimester, rechute scenarios

## Web Interface Features

**Tabs System** (index.html + app.js):
- **📊 Résumé**: Traditional summary view with results table
- **📅 Calendrier**: Interactive monthly calendar showing daily payments

**Calendar View** (calendar_functions.js):
- Monthly navigation with prev/next buttons
- Visual indicators: 🏥 (arret start), green (paid day), yellow (décompte)
- Daily breakdown tooltips with rate/amount/period info
- Auto-initializes to first payment month

**Results Display**:
- Age, trimestres, total amount, cumulative days
- Period end dates (for age 62-69: periods 1/2/3)
- Detailed arret table with décompte column (yellow highlighted)
- Rechute badges and source display in arret list
- Explanatory info boxes for décompte and calculation rules

## File Organization

```
/
├── IJCalculator.php           # Main calculator (~690 lines)
├── Services/                  # SOLID service layer (~1,800 lines)
│   ├── RateService.php / RateServiceInterface.php
│   ├── DateService.php / DateCalculationInterface.php
│   ├── TauxDeterminationService.php / TauxDeterminationInterface.php
│   └── AmountCalculationService.php / AmountCalculationInterface.php
├── Tests/                     # Unit tests (~56 tests)
│   ├── RateServiceTest.php (13 tests)
│   ├── DateServiceTest.php (17 tests)
│   ├── TauxDeterminationServiceTest.php (16 tests)
│   ├── AmountCalculationServiceTest.php
│   └── RechuteTest.php (10 tests)
├── api.php                    # REST API endpoints
├── index.html                 # Web interface (HTML structure)
├── app.js                     # Frontend logic & results display
├── calendar_functions.js      # Calendar view functionality
├── taux.csv                   # Historical rate tables
├── test_mocks.php             # Integration tests (18+ scenarios)
├── run_all_tests.php          # Test runner (all tests)
├── jest-php.php               # Jest-style test framework
├── debug_mock*.php            # Debug scripts for specific scenarios
├── mock*.json                 # Test scenarios (root + webroot/mocks/)
├── src/                       # CakePHP 5 integration (optional)
│   ├── Controller/            # API controllers
│   ├── Model/                 # Database entities
│   └── Service/               # CakePHP service wrappers
└── config/                    # CakePHP config + migrations
```

## Common Development Workflows

### Adding a New Test Scenario

1. **Create mock JSON** in root or `webroot/mocks/`:
   ```bash
   cp mock.json mock29.json
   # Edit mock29.json with new scenario
   ```

2. **Add to integration tests** in `test_mocks.php`:
   ```php
   'mock29' => [
       'description' => 'New scenario description',
       'expected_montant' => 1234.56,
       'expected_nb_jours' => 100
   ]
   ```

3. **Run tests**:
   ```bash
   php test_mocks.php
   ```

### Modifying Business Logic

**When changing calculation rules**:

1. **Update appropriate service** in `Services/`:
   - Rate changes → `RateService.php`
   - Date/trimester logic → `DateService.php`
   - Taux determination → `TauxDeterminationService.php`
   - Orchestration → `AmountCalculationService.php`

2. **Update interface** if method signature changes

3. **Run unit tests first**:
   ```bash
   php Tests/[Service]Test.php
   ```

4. **Run full test suite**:
   ```bash
   php run_all_tests.php
   ```

5. **Verify integration tests** still pass:
   ```bash
   php test_mocks.php
   ```

### Debugging Calculation Issues

**Use debug scripts for specific scenarios**:
```bash
php debug_mock2.php    # Multiple stoppages
php debug_mock9.php    # Age transitions
php debug_mock20.php   # Period 2 calculations
```

**Add temporary debug output** in services:
```php
error_log("DEBUG: date-effet = " . $dateEffet);
var_dump($calculationData);  // Remove before commit
```

**Check daily breakdown** in web interface:
- Open browser console (F12)
- Click "📅 Calendrier" tab
- Hover over days to see tooltips
- Check `data.payment_details[].daily_breakdown` in console

### Testing Rechute Logic

**Key test files**:
```bash
php Tests/RechuteTest.php           # Unit tests
php test_rechute_integration.php    # Integration tests
php test_rechute_simple.php         # Basic verification
```

**Critical checks**:
- Previous arret has date-effet (rights opened)
- Gap between stoppages (not prolongation)
- Within 1 year of previous arret end
- 15-day threshold (not 91 days)

## Important Implementation Details

### Rate Determination Decision Tree

`TauxDeterminationService::determineTauxNumber()`:
1. Check eligibility (≥8 quarters, else return 0)
2. Apply historical rate if exists (for same pathology)
3. Determine age bracket (<62, 62-69, ≥70)
4. Calculate pathology anterior reduction based on quarters
5. Return taux number (1-9)

### Date Effet Calculation (90-Day Rule)

`DateService::calculateDateEffet()`:
- Accumulates days across all work stoppages
- **New pathology**: 90 days + DT penalty (31 days) + GPM penalty (31 days)
- **Rechute**: 15 days + DT penalty (15 days) + GPM penalty (15 days)
- Returns payment_start date or empty string if non-payable
- Critical: Only counts days towards threshold, rechute requires previous date-effet

### Trimester Rounding Rule

`DateService::calculateTrimesters()`:
- **Partial quarters always count as complete**
- Affiliation mid-quarter = complete quarter
- Current date mid-quarter = rounds up to next complete quarter
- Formula: `(years × 4) + (currentQ - affiliationQ) + 1`
- Example: 2019-01-15 to 2024-04-11 = 22 quarters (NOT 21)

### Web Interface Behavior

**Automatic rechute handling**:
- In web UI, all arrets after the first are marked with `rechute-line: 1`
- Backend auto-detects true rechute based on 3 criteria
- UI displays rechute badges and source indicators
- Calendar view shows visual timeline of all stoppages

## Documentation References

**Architecture & Design**:
- **REFACTORING.md**: SOLID principles, service layer architecture, 100% backward compatibility
- **README.md**: API documentation, endpoint usage, data formats

**Business Rules**:
- **RATE_RULES.md**: Complete 27-rate system explanation and decision tree
- **CLASSE_DETERMINATION.md**: Revenue-based class determination (PASS calculation)
- **TRIMESTER_CALCULATION_FIX.md**: Quarter rounding rules with examples
- **RECHUTE_IMPLEMENTATION_SUMMARY.md**: Relapse detection logic (3 criteria)

**Features**:
- **DECOMPTE_FEATURE.md**: Non-paid days tracking (décompte) with UI display
- **CALENDAR_VIEW_FEATURE.md**: Interactive calendar with daily breakdown
- **NO_ATTESTATION_FEATURE.md**: Handling missing attestation dates

**Testing & Development**:
- **TESTING_SUMMARY.md**: Test coverage strategy (255+ tests)
- **JEST-PHP-README.md**: Jest-style testing framework for PHP
- **QUICKSTART.md**: CakePHP installation and setup guide

**UI & Visualization**:
- **CALENDAR_RECHUTE_DISPLAY.md**: Calendar rechute indicators
- **INTERFACE_BADGES_VISUAL.md**: Badge system for arret status
- **ARRET_LIST_BADGES.md**: Visual indicators in arret list

## Historical Context

**VBA Origin** (`code.vba`):
- Original system was VBA Excel automation
- Entry point: `Sub IJ()` (line 20)
- VBA functions mapped to PHP services
- Excel cell references documented but not used in PHP
- All VBA business logic preserved in service layer
