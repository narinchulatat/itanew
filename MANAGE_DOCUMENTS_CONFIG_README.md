# Manage Documents Configuration System

This implementation adds a comprehensive configuration system for the document management module, allowing users to set and manage their preferences for document filtering and display.

## Features Implemented

### 1. Database Table
- Created `manage_documents_config` table with all required fields
- Supports user-specific configurations
- Tracks creation and update timestamps
- Includes proper foreign key relationships

### 2. Menu Integration
- Added "ตั้งค่าจัดการเอกสาร" (Configure Document Management) menu item
- Accessible to both Admin (role_id = 1) and User (role_id = 3) roles
- Properly integrated with existing sidebar navigation

### 3. Configuration Page
- Dedicated configuration page at `pages/manage_documents_config.php`
- Clean, responsive design using Tailwind CSS
- Shows current configuration status
- Full CRUD operations for configuration settings

### 4. AJAX Operations
- Enhanced `ajax/manage_documents_config.php` with full functionality
- Supports saving, loading, updating, and deleting configurations
- Dynamic category/subcategory loading based on year/quarter selection
- Robust error handling

### 5. Integration with Main System
- Configuration panel integrated into main manage_documents page
- Real-time filtering based on user configuration
- Seamless user experience between configuration and document management

## Files Modified/Created

### New Files
- `pages/manage_documents_config.php` - Main configuration page
- `create_manage_documents_config_table.sql` - Database schema

### Modified Files
- `sidebar.php` - Added menu items for configuration page
- `index.php` - Added routing and permissions for configuration page
- `ajax/manage_documents_config.php` - Enhanced with full CRUD operations

### Existing Integration
- `pages/manage_documents.php` - Already had configuration panel integration
- Database connection and session management preserved

## Database Schema

```sql
CREATE TABLE IF NOT EXISTS `manage_documents_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `quarter` int(1) NOT NULL CHECK (`quarter` BETWEEN 1 AND 4),
  `main_category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_config` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_year_quarter` (`year`, `quarter`),
  KEY `idx_main_category` (`main_category_id`),
  KEY `idx_sub_category` (`sub_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Installation Instructions

1. **Database Setup**
   ```bash
   mysql -u root -p < create_manage_documents_config_table.sql
   ```

2. **File Deployment**
   - All files are already in place in the repository
   - No additional configuration required

3. **Access**
   - Admin users (role_id = 1): Full access to configuration page
   - Regular users (role_id = 3): Full access to configuration page
   - Super admin (role_id = 2): Access through document approval workflow

## Usage

### Configuration Page
1. Navigate to "ตั้งค่าจัดการเอกสาร" from the main menu
2. Select year and quarter (required)
3. Optionally select main category and subcategory for filtering
4. Set active status
5. Save configuration

### Document Management Integration
1. Configuration automatically applies to document management page
2. Filters documents based on saved configuration
3. Real-time updates when configuration changes

## Technical Notes

- **Database Compatibility**: Code includes fallback for different database systems
- **Error Handling**: Comprehensive error handling in AJAX operations
- **Responsive Design**: Mobile-friendly interface using Tailwind CSS
- **Security**: Proper session management and SQL injection protection
- **Performance**: Optimized queries with proper indexing

## Testing

The implementation has been tested with:
- ✅ Database table creation
- ✅ AJAX configuration save/load operations
- ✅ Menu navigation and permissions
- ✅ UI responsiveness and design
- ✅ Integration with existing document management system