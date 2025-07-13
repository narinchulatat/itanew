# URL Structure Fix for Home.php

## Overview

This fix addresses the URL structure issue in home.php to properly display both year and quarter parameters in the URL format `index.php?page=home&year=2568&quarter=3` instead of the previous `index.php?page=home&quarter=3`.

## Changes Made

### 1. Database Schema Updates

**File: `migration_home_display_config.sql`**
- Added `is_default` BOOLEAN column to identify default configuration
- Added `default_year` INT column for default year setting
- Added `default_quarter` INT column for default quarter setting
- Creates default configuration for year 2568, quarter 3 if none exists
- Ensures only one default configuration exists

### 2. Home.php Updates

**File: `pages/home.php`**
- **Redirect Logic**: Added comprehensive redirect logic that checks for missing year/quarter parameters and redirects to the proper URL format
- **URL Generation**: Updated JavaScript to include both year and quarter parameters in URL updates
- **Form Updates**: Added hidden page parameter to ensure form submissions work correctly
- **Default Configuration**: Enhanced default configuration handling to use database settings

Key changes:
- Lines 72-104: New redirect logic for missing parameters
- Line 337: Added hidden page parameter to form
- Line 529-531: Updated JavaScript URL manipulation to include year

### 3. Admin Interface Updates

**File: `pages/manage_home_display.php`**
- **Database Operations**: Updated INSERT/UPDATE statements to handle new columns
- **Form Fields**: Added UI elements for default_year and default_quarter configuration
- **JavaScript**: Updated form handling functions to manage new fields
- **Default Settings**: Enhanced default configuration management

Key changes:
- Lines 36-38: Added default_year and default_quarter parameter handling
- Lines 58-59: Updated INSERT statement
- Lines 76-77: Updated UPDATE statement
- Lines 333-371: New UI for default configuration in add modal
- Lines 432-470: New UI for default configuration in edit modal

### 4. Test Scripts

**File: `test_logic.php`**
- Comprehensive test suite for URL redirect logic
- File structure validation
- JavaScript functionality verification
- Form parameter checks

**File: `test_migration.php`**
- Database migration verification
- Default configuration testing
- Database connectivity checks

## Installation Instructions

### 1. Database Migration

Run the migration script to add the new columns:

```bash
mysql -u root -p namyuenh_newita < migration_home_display_config.sql
```

### 2. Verify Installation

Run the test scripts to verify everything is working:

```bash
php test_logic.php
php test_migration.php  # Requires database connection
```

## Functionality

### URL Redirect Behavior

| Input URL | Redirect URL |
|-----------|-------------|
| `index.php?page=home` | `index.php?page=home&year=2568&quarter=3` |
| `index.php?page=home&year=2567` | `index.php?page=home&year=2567&quarter=3` |
| `index.php?page=home&quarter=2` | `index.php?page=home&year=2568&quarter=2` |
| `index.php?page=home&year=2567&quarter=2` | No redirect (URL is complete) |

### Default Configuration

- Default year: 2568
- Default quarter: 3
- Can be customized through the admin interface (`manage_home_display.php`)
- Only one default configuration allowed at a time

### Admin Interface Features

- **Add Configuration**: Create new display configurations with default settings
- **Edit Configuration**: Modify existing configurations including default year/quarter
- **Default Settings**: Set any configuration as the default for new visitors
- **Visual Indicators**: Clear indication of which configurations are set as default

## Files Modified

1. `pages/home.php` - Main home page with redirect logic
2. `pages/manage_home_display.php` - Admin interface for managing configurations
3. `migration_home_display_config.sql` - Database schema updates
4. `test_logic.php` - Logic testing script
5. `test_migration.php` - Database migration testing script

## Technical Details

### Database Schema

```sql
ALTER TABLE home_display_config 
ADD COLUMN is_default BOOLEAN DEFAULT FALSE,
ADD COLUMN default_year INT NULL,
ADD COLUMN default_quarter INT NULL;
```

### JavaScript URL Handling

```javascript
// Update URL with both year and quarter
const url = new URL(window.location);
url.searchParams.set('year', currentYear);
url.searchParams.set('quarter', quarter);
window.history.replaceState({}, '', url);
```

### PHP Redirect Logic

```php
// Check if year or quarter is missing
if (!$hasYear || !$hasQuarter) {
    $currentYear = $hasYear ? intval($_GET['year']) : $defaultYear;
    $currentQuarter = $hasQuarter ? intval($_GET['quarter']) : $defaultQuarter;
    
    $redirectUrl = "index.php?page=home&year={$currentYear}&quarter={$currentQuarter}";
    header("Location: $redirectUrl");
    exit;
}
```

## Testing

### Automated Tests

Run the test scripts to verify functionality:

```bash
# Test URL logic (no database required)
php test_logic.php

# Test database migration (requires database)
php test_migration.php
```

### Manual Testing

1. **URL Redirects**: 
   - Visit `index.php?page=home` and verify redirect to `index.php?page=home&year=2568&quarter=3`
   - Test various combinations of missing parameters

2. **Admin Interface**:
   - Access `pages/manage_home_display.php`
   - Create new configurations with default settings
   - Edit existing configurations
   - Verify default configuration management

3. **Tab Navigation**:
   - Test quarter tab switching maintains proper URL format
   - Verify JavaScript updates URL correctly

## Troubleshooting

### Common Issues

1. **Database Connection Error**: 
   - Verify MySQL is running
   - Check database credentials in `db.php`
   - Ensure database `namyuenh_newita` exists

2. **Migration Columns Missing**:
   - Run migration script: `mysql -u root -p namyuenh_newita < migration_home_display_config.sql`
   - Verify columns exist with: `DESCRIBE home_display_config`

3. **Redirect Loop**:
   - Check for conflicting URL parameters
   - Verify default configuration exists in database
   - Clear browser cache/cookies

### Debug Steps

1. Check PHP error logs for any syntax errors
2. Verify database schema matches expected structure
3. Test URL redirect logic with test scripts
4. Check browser developer tools for JavaScript errors

## Future Enhancements

1. **Multiple Default Configurations**: Support for different defaults per user role
2. **URL Validation**: Enhanced parameter validation and sanitization
3. **Caching**: Implement configuration caching for better performance
4. **API Integration**: REST API endpoints for configuration management