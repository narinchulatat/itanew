<?php
// Database setup script to ensure proper table structure
echo "Database Setup Script for CRUD Operation Fixes\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Function to log database errors
function logDatabaseError($error, $context = '') {
    $logFile = __DIR__ . '/logs/database_errors.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$context}: {$error}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Test database connection
try {
    $host = 'localhost';
    $db = 'namyuenh_newita';
    $user = 'root';
    $pass = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "✓ Database connection successful\n\n";
    
    // Check if home_display_config table has required columns
    echo "Checking table structure...\n";
    
    $stmt = $pdo->query("DESCRIBE home_display_config");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['is_default', 'active_quarter', 'default_year', 'default_quarter'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "✓ All required columns exist in home_display_config table\n";
    } else {
        echo "Adding missing columns to home_display_config table...\n";
        
        // Add missing columns
        foreach ($missingColumns as $column) {
            try {
                switch ($column) {
                    case 'is_default':
                        $pdo->exec("ALTER TABLE home_display_config ADD COLUMN is_default BOOLEAN DEFAULT FALSE");
                        echo "  ✓ Added column: is_default\n";
                        break;
                    case 'active_quarter':
                        $pdo->exec("ALTER TABLE home_display_config ADD COLUMN active_quarter INT DEFAULT 1");
                        echo "  ✓ Added column: active_quarter\n";
                        break;
                    case 'default_year':
                        $pdo->exec("ALTER TABLE home_display_config ADD COLUMN default_year INT NULL");
                        echo "  ✓ Added column: default_year\n";
                        break;
                    case 'default_quarter':
                        $pdo->exec("ALTER TABLE home_display_config ADD COLUMN default_quarter INT NULL");
                        echo "  ✓ Added column: default_quarter\n";
                        break;
                }
            } catch (Exception $e) {
                echo "  ✗ Error adding column $column: " . $e->getMessage() . "\n";
                logDatabaseError($e->getMessage(), "Adding column $column");
            }
        }
    }
    
    // Check table data
    echo "\nChecking table data...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM years");
    $yearCount = $stmt->fetch()['count'];
    echo "  Years table: {$yearCount} records\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM home_display_config");
    $configCount = $stmt->fetch()['count'];
    echo "  Home display config table: {$configCount} records\n";
    
    // Test basic operations
    echo "\nTesting basic operations...\n";
    
    // Test insert to years table
    try {
        $pdo->beginTransaction();
        
        $testYear = 2999; // Test year that shouldn't exist
        $stmt = $pdo->prepare("INSERT INTO years (year) VALUES (?)");
        $stmt->execute([$testYear]);
        
        $stmt = $pdo->prepare("DELETE FROM years WHERE year = ?");
        $stmt->execute([$testYear]);
        
        $pdo->commit();
        echo "  ✓ INSERT/DELETE operations work correctly\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "  ✗ Error testing operations: " . $e->getMessage() . "\n";
        logDatabaseError($e->getMessage(), "Testing operations");
    }
    
    echo "\n✓ Database setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    
    // Show helpful error messages
    if (strpos($e->getMessage(), 'No such file or directory') !== false) {
        echo "\nSuggestion: Start MySQL server\n";
        echo "  - On XAMPP: Start MySQL from control panel\n";
        echo "  - On Linux: sudo service mysql start\n";
        echo "  - On macOS: sudo brew services start mysql\n";
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "\nSuggestion: Check database credentials in db.php\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "\nSuggestion: Create database 'namyuenh_newita' or import from SQL file\n";
    }
    
    logDatabaseError($e->getMessage(), "Database setup");
}

echo "\n" . str_repeat("=", 50) . "\n";