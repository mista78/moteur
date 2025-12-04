# Rate System Migration: CSV to Database

This document describes the migration of the rate system from CSV files to database storage.

## Overview

The rate data (27-rate system for classes A, B, C) has been migrated from CSV storage (`data/taux.csv`) to database storage in the `ij_taux` table. The system maintains backward compatibility with automatic CSV fallback if database connection fails.

## Changes Made

### 1. New Eloquent Model: `IjTaux`

**File**: `src/Models/IjTaux.php`

Eloquent model for the `ij_taux` table with methods:
- `getRateForYear(int $year)` - Get rate for specific year
- `getRateForDate(string $date)` - Get rate for specific date
- `getAllRatesOrdered()` - Get all rates ordered by date

**Casts**:
- `date_start` and `date_end` as date objects
- All taux columns (`taux_a1`, `taux_a2`, etc.) as floats

### 2. Updated RateRepository

**File**: `src/Repositories/RateRepository.php`

**Changes**:
- Primary method now loads from database (`ij_taux` table)
- Automatic fallback to CSV if database fails
- Returns same data structure for compatibility
- CSV loading moved to private `loadRatesFromCsv()` method

### 3. Migration Script

**File**: `migrate_rates_to_db.php`

Script to migrate data from CSV to database:
- Reads `data/taux.csv`
- Inserts/updates records in `ij_taux` table
- Handles duplicate date ranges (updates existing)
- Provides detailed migration summary

**Usage**: `php migrate_rates_to_db.php`

### 4. Test Script

**File**: `test_rates_db.php`

Comprehensive test script that verifies:
- Database connection
- Table existence
- Rate count in database
- Query by year functionality
- Query by date functionality
- RateRepository integration
- Data structure compatibility

**Usage**: `php test_rates_db.php`

### 5. Documentation Updates

**File**: `CLAUDE.md`

Updated documentation with:
- IjTaux model in architecture diagram
- New "Rate Management (Database)" section
- Database table schema
- Migration commands
- Usage examples
- Updated Quick Start guide
- Updated Common Issues section
- Updated Migration Notes

## Database Schema

```sql
CREATE TABLE `ij_taux` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `taux_a1` float NOT NULL,
  `taux_a2` float NOT NULL,
  `taux_a3` float NOT NULL,
  `taux_b1` float NOT NULL,
  `taux_b2` float NOT NULL,
  `taux_b3` float NOT NULL,
  `taux_c1` float NOT NULL,
  `taux_c2` float NOT NULL,
  `taux_c3` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
```

## Migration Steps

1. **Create the table**:
   ```bash
   # Execute the CREATE TABLE statement above in your MySQL database
   ```

2. **Migrate data from CSV**:
   ```bash
   php migrate_rates_to_db.php
   ```

3. **Verify migration**:
   ```bash
   php test_rates_db.php
   ```

4. **Test application**:
   ```bash
   cd public && php -S localhost:8000
   curl http://localhost:8000/api/calculations -X POST -H "Content-Type: application/json" -d @data/mocks/mock1.json
   ```

## Backward Compatibility

The system maintains full backward compatibility:

- **RateRepository** automatically falls back to CSV if database fails
- **RateService** unchanged - still receives same data structure
- **Existing tests** continue to work without modification
- **CSV file** still supported and used as fallback

## Benefits

1. **Performance**: Database queries are faster than parsing CSV files
2. **Scalability**: Better handling of large historical rate datasets
3. **Consistency**: Transactional integrity for rate updates
4. **Flexibility**: Easier to query and filter rates by date ranges
5. **Integration**: Seamless integration with other Eloquent models
6. **Maintainability**: Standard ORM patterns for rate management

## Testing

All existing tests continue to pass:
```bash
php run_all_tests.php
php test/test_mocks.php
```

No changes required to existing test files as the data structure returned by RateRepository remains identical.

## Troubleshooting

### Rate data not found
- Ensure `ij_taux` table exists
- Run migration: `php migrate_rates_to_db.php`
- Verify data: `php test_rates_db.php`

### Database connection failed
- System automatically falls back to CSV (`data/taux.csv`)
- Check `.env` database configuration
- Test connection: `php test_rates_db.php`

### Migration errors
- Check database credentials in `.env`
- Ensure `data/taux.csv` exists and is readable
- Verify table schema matches exactly

## Next Steps

Optional enhancements for future consideration:

1. **Admin UI**: Web interface for managing rates
2. **Rate History**: Track rate changes with timestamps
3. **Rate Validation**: Constraints and business rules
4. **Audit Trail**: Log all rate modifications
5. **API Endpoints**: RESTful endpoints for rate CRUD operations
6. **Migration Automation**: Artisan-style migrations for rate updates

## Files Created/Modified

**New Files**:
- `src/Models/IjTaux.php` - Eloquent model
- `migrate_rates_to_db.php` - Migration script
- `test_rates_db.php` - Test script
- `RATE_DB_MIGRATION.md` - This documentation

**Modified Files**:
- `src/Repositories/RateRepository.php` - Database integration
- `CLAUDE.md` - Updated documentation

**Unchanged Files**:
- `src/Services/RateService.php` - No changes required
- All test files - Fully compatible
- All other services - No impact

## Conclusion

The rate system has been successfully migrated from CSV to database storage while maintaining full backward compatibility. The system now benefits from improved performance, scalability, and maintainability while preserving all existing functionality.
