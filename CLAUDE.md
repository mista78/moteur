# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

French medical professional sick leave benefits calculator ("Indemnités Journalières" - IJ) for CARMF (Caisse Autonome de Retraite des Médecins de France). The system handles complex calculations based on:
- Contribution class (A/B/C)
- Professional status (M/RSPM/CCPL)
- Age brackets (<62, 62-69, 70+)
- Affiliation quarters
- Prior pathology status
- Historical rate tables (2022-2025)

## Development Commands

### Running Tests

**Run all tests** (46 unit tests + 18 integration tests):
```bash
php run_all_tests.php
```

**Individual service tests**:
```bash
php Tests/RateServiceTest.php
php Tests/DateServiceTest.php
php Tests/TauxDeterminationServiceTest.php
php Tests/AmountCalculationServiceTest.php
```

**Integration tests only**:
```bash
php test_mocks.php
```

**Debug specific mock scenarios**:
```bash
php debug_mock9.php   # Age 70 transition testing
php debug_mock20.php  # Period 2 calculations
php debug_mock23.php  # Complex scenarios
```

### Development Server

**PHP built-in server**:
```bash
php -S localhost:8000
```

Access at: `http://localhost:8000`

**CakePHP server** (if using CakePHP integration):
```bash
bin/cake server
```

Access at: `http://localhost:8765/indemnite-journaliere`

### CakePHP Commands (if applicable)

**Database migrations**:
```bash
bin/cake migrations migrate
bin/cake migrations rollback
bin/cake migrations status
```

**Cache clearing**:
```bash
bin/cake cache clear_all
```

## Core Architecture

### Dual Implementation

The project has two parallel implementations:

1. **Standalone PHP** (`IJCalculator.php` + Services):
   - Monolithic calculator with service-oriented refactoring
   - Direct usage via `new IJCalculator('taux.csv')`
   - Used by web interface (`index.html` + `api.php`)

2. **CakePHP 5 Integration** (`src/` directory):
   - Full MVC structure with Models, Controllers, Services
   - Database persistence for calculations and work stoppages
   - RESTful API endpoints

### Web Interface Behavior

**Automatic Rechute (Relapse) Handling**:
- In the web interface, ALL arrets (work stoppages) after the first one are automatically treated as rechute (relapse)
- The first arret is the initial work stoppage
- Any subsequent arret added to the calculation is considered a relapse by default
- This behavior simplifies data entry for the common case where multiple stoppages represent relapses

### Service Layer (SOLID Architecture)

Located in `/Services/` directory with interface-based design:

**RateService** (`RateServiceInterface.php`):
- `getDailyRate()`: Calculate daily rate based on status, class, age, tier
- `getRateForYear()`: Get rate data for specific year
- `getRateForDate()`: Get rate data for specific date
- Handles tier determination (1, 2, 3) and option multipliers

**DateService** (`DateCalculationInterface.php`):
- `calculateAge()`: Age at specific date
- `calculateTrimesters()`: Affiliation quarters (Q1-Q4 based)
- `mergeProlongations()`: Merge consecutive work stoppage periods
- `calculateDateEffet()`: Rights opening dates (90-day rule)
- `calculatePayableDays()`: Payable days calculation per period
- `getTrimesterFromDate()`: Quarter number extraction

**TauxDeterminationService** (`TauxDeterminationInterface.php`):
- `determineTauxNumber()`: Rate number (1-9) based on age/quarters/pathology
- `determineClasse()`: Contribution class (A/B/C) from revenue

**AmountCalculationService** (`AmountCalculationInterface.php`):
- `calculateTotalAmount()`: Main calculation orchestration
- Integrates all services for complete benefit calculation

### Key Business Rules

**Rechute (Relapse) handling**:
- **Interface behavior**: All arrets after the first are automatically treated as rechute
- First arret: Initial work stoppage (rechute-line = 0)
- Subsequent arrets: Automatically marked as rechute (rechute-line = 1)
- Rechute affects day counting: resumes from 1st day if relapse, otherwise from 15th day

**90-day threshold**:
- Benefits begin after 90 cumulative days of work stoppage
- Different relapse rules (1st day vs 15th day)

**Age-based periods** (62-69 years):
- Period 1 (days 1-365): Full rate
- Period 2 (days 366-730): Rate minus 25% (taux 7-9)
- Period 3 (days 731-1095): Reduced senior rate (taux 4-6)

**70+ age limit**:
- Maximum 365 days per affiliation
- Uses senior reduced rates (taux 4-6)

**27-rate system**:
- 9 base rates (3 age brackets × 3 pathology levels)
- Taux 1-3: <62 years (full, -1/3, -2/3)
- Taux 4-6: ≥70 years (reduced, -1/3, -2/3)
- Taux 7-9: 62-69 years after 1 year (full-25%, -1/3, -2/3)

**Pathology anterior reductions**:
- <8 quarters: No benefits
- 8-15 quarters: -1/3 reduction (taux +1)
- 16-23 quarters: -2/3 reduction (taux +2)
- ≥24 quarters: Full rate

**Trimester calculation rule** (CRITICAL):
- Quarters are Q1 (Jan-Mar), Q2 (Apr-Jun), Q3 (Jul-Sep), Q4 (Oct-Dec)
- If affiliation date falls within a quarter, that quarter counts as **complete**
- Example: 2019-01-15 (mid-Q1) to 2024-04-11 (Q2) = 22 quarters
- Calculation: (5 years × 4) + (Q2 - Q1) + 1 = 22
- Implementation: `DateService::calculateTrimesters()` (Services/DateService.php:31)

## Data Structures

### Input Format (mock.json / API)

```json
{
  "statut": "M",
  "classe": "A",
  "option": 100,
  "birth_date": "1960-01-15",
  "current_date": "2024-01-15",
  "attestation_date": "2024-01-31",
  "last_payment_date": null,
  "affiliation_date": "2019-01-15",
  "nb_trimestres": 22,
  "previous_cumul_days": 0,
  "patho_anterior": false,
  "prorata": 1,
  "pass_value": 47000,
  "arrets": [
    {
      "arret-from-line": "2023-09-04",
      "arret-to-line": "2023-11-10",
      "rechute-line": 0,
      "dt-line": 1,
      "gpm-member-line": 1,
      "declaration-date-line": "2023-09-19"
    }
  ]
}
```

### Rate CSV Format (taux.csv)

```csv
id;date_start;date_end;taux_a1;taux_a2;taux_a3;taux_b1;taux_b2;taux_b3;taux_c1;taux_c2;taux_c3
1;2024-01-01;2024-12-31;75.06;38.3;56.3;112.59;57.45;84.45;150.12;76.6;112.59
```

Columns:
- `taux_X1`: Period 1 rate (0-365 days)
- `taux_X2`: Period 2 rate (366-730 days)
- `taux_X3`: Period 3 rate (731-1095 days)
- X = a/b/c for classes A/B/C

## API Endpoints

### Standalone API (api.php)

**Calculate date effet**:
```bash
POST /api.php?endpoint=date-effet
```

**Calculate end payment dates**:
```bash
POST /api.php?endpoint=end-payment
```

**Full calculation**:
```bash
POST /api.php?endpoint=calculate
```

**Calculate revenue (PASS)**:
```bash
POST /api.php?endpoint=revenu
```

**Load mock data**:
```bash
GET /api.php?endpoint=load-mock
```

### CakePHP API (if integrated)

**API calculate**:
```bash
POST /indemnite-journaliere/api-calculate.json
```

## Test Data

18 integration test scenarios in root directory (`mock.json`, `mock2.json`, etc.) and `webroot/mocks/`:

Key scenarios:
- `mock.json`: Basic calculation (750.60€)
- `mock2.json`: Multiple stoppages (17318.92€)
- `mock7.json`: CCPL with pathology anterior (74331.79€)
- `mock9.json`: Age 70 transition (53467.98€)
- `mock10.json`: Period 2 intermediate (51744.25€)

## File Organization

```
/
├── IJCalculator.php           # Main calculator (refactored with services)
├── Services/                  # Service layer (SOLID principles)
│   ├── RateService.php
│   ├── DateService.php
│   ├── TauxDeterminationService.php
│   ├── AmountCalculationService.php
│   └── *Interface.php         # Interface contracts
├── Tests/                     # Unit tests (46 tests)
│   ├── RateServiceTest.php
│   ├── DateServiceTest.php
│   ├── TauxDeterminationServiceTest.php
│   └── AmountCalculationServiceTest.php
├── src/                       # CakePHP 5 integration
│   ├── Controller/
│   ├── Model/
│   ├── Service/
│   └── Form/
├── config/                    # CakePHP config + migrations
├── api.php                    # Standalone REST API
├── index.html                 # Web interface
├── app.js                     # Frontend logic
├── taux.csv                   # Rate tables
├── test_mocks.php             # Integration tests (18 tests)
├── run_all_tests.php          # Test runner
└── mock*.json                 # Test scenarios
```

## Key Implementation Notes

### Class Determination (CLASSE_DETERMINATION.md)

Automatic class determination based on N-2 revenue:
- Class A: < 1 PASS (< 47,000€)
- Class B: 1-3 PASS (47,000€ - 141,000€)
- Class C: > 3 PASS (> 141,000€)

Method: `IJCalculator::determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice)`

### Rate Determination Logic

The `determineTauxNumber()` method (TauxDeterminationService.php) implements the decision tree:
1. Check eligibility (≥8 quarters)
2. Apply historical rate if exists
3. Determine age bracket
4. Calculate pathology anterior reduction
5. Return taux number (1-9)

### Date Effet Calculation

The 90-day rule implementation (`DateService::calculateDateEffet()`):
- Accumulates days from all work stoppages
- Handles relapses (rechute)
- Applies DT and GPM adjustments (+31 days each)
- Returns payment start date or empty string if not payable

### Backward Compatibility

The refactoring maintains 100% backward compatibility:
- Original VBA logic preserved in comments
- All 18 integration tests pass without modification
- Services can be injected or use defaults

## Common Development Patterns

### Adding a New Test

1. Create mock JSON in root or `webroot/mocks/`
2. Add test case to `test_mocks.php` with expected values
3. Run: `php test_mocks.php`

### Modifying Rate Logic

1. Update service in `Services/` directory
2. Update corresponding interface if signature changes
3. Run unit tests: `php Tests/[Service]Test.php`
4. Run integration tests: `php test_mocks.php`
5. Update REFACTORING.md if architecture changes

### Adding New Business Rules

1. Identify appropriate service (Rate/Date/Taux/Amount)
2. Add method to interface
3. Implement in service
4. Add unit tests
5. Update integration tests if needed
6. Document in relevant .md file (RATE_RULES.md, CLASSE_DETERMINATION.md, etc.)

## Documentation References

- **REFACTORING.md**: SOLID principles and service architecture
- **RATE_RULES.md**: Complete 27-rate system explanation
- **CLASSE_DETERMINATION.md**: Revenue-based class determination
- **QUICKSTART.md**: CakePHP installation guide
- **README.md**: API documentation and usage examples
- **TESTING_SUMMARY.md**: Test coverage and strategy
- **TRIMESTER_CALCULATION_FIX.md**: Quarter calculation rules

## Original VBA Reference

The project was ported from VBA Excel automation (`code.vba`):
- Original entry point: `Sub IJ()` (line 20)
- VBA functions mapped to PHP services
- Excel cell references documented but not used in PHP version
