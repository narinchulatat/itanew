<?php
// Test script to verify the migration and functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Database Migration and URL Structure Fix...\n\n";

// Test 1: Database connection
echo "1. Testing database connection...\n";
try {
    $host = 'localhost';
    $db = 'namyuenh_newita';
    $user = 'root';
    $pass = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please ensure MySQL is running and the database exists\n";
    exit(1);
}

// Test 2: Check if migration columns exist
echo "\n2. Checking if migration columns exist...\n";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM home_display_config LIKE 'is_default'");
    $isDefaultExists = $stmt->fetch() !== false;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM home_display_config LIKE 'default_year'");
    $defaultYearExists = $stmt->fetch() !== false;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM home_display_config LIKE 'default_quarter'");
    $defaultQuarterExists = $stmt->fetch() !== false;
    
    if ($isDefaultExists && $defaultYearExists && $defaultQuarterExists) {
        echo "✓ All migration columns exist\n";
    } else {
        echo "✗ Migration columns missing. Run migration script first:\n";
        echo "Missing columns: ";
        if (!$isDefaultExists) echo "is_default ";
        if (!$defaultYearExists) echo "default_year ";
        if (!$defaultQuarterExists) echo "default_quarter ";
        echo "\n";
        echo "Run: mysql -u root -p namyuenh_newita < migration_home_display_config.sql\n";
        exit(1);
    }
} catch (PDOException $e) {
    echo "✗ Error checking columns: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check for default configuration
echo "\n3. Checking default configuration...\n";
try {
    $stmt = $pdo->query("SELECT * FROM home_display_config WHERE is_default = 1");
    $defaultConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($defaultConfig) {
        echo "✓ Default configuration found:\n";
        echo "  - Year ID: " . $defaultConfig['year'] . "\n";
        echo "  - Quarter: " . $defaultConfig['quarter'] . "\n";
        echo "  - Default Year: " . $defaultConfig['default_year'] . "\n";
        echo "  - Default Quarter: " . $defaultConfig['default_quarter'] . "\n";
    } else {
        echo "✗ No default configuration found\n";
        echo "Creating default configuration...\n";
        
        // Get year ID for 2568
        $stmt = $pdo->prepare("SELECT id FROM years WHERE year = 2568");
        $stmt->execute();
        $yearRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($yearRow) {
            $yearId = $yearRow['id'];
            $stmt = $pdo->prepare("INSERT INTO home_display_config (year, quarter, source_year, source_quarter, is_default, default_year, default_quarter) VALUES (?, 3, ?, 3, 1, 2568, 3)");
            $stmt->execute([$yearId, $yearId]);
            echo "✓ Default configuration created\n";
        } else {
            echo "✗ Year 2568 not found in years table\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ Error checking default configuration: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Test URL redirect logic simulation
echo "\n4. Testing URL redirect logic...\n";

// Simulate different URL scenarios
$testCases = [
    ['url' => 'index.php?page=home', 'expected' => 'index.php?page=home&year=2568&quarter=3'],
    ['url' => 'index.php?page=home&year=2567', 'expected' => 'index.php?page=home&year=2567&quarter=3'],
    ['url' => 'index.php?page=home&quarter=2', 'expected' => 'index.php?page=home&year=2568&quarter=2'],
    ['url' => 'index.php?page=home&year=2567&quarter=2', 'expected' => 'index.php?page=home&year=2567&quarter=2'],
];

foreach ($testCases as $i => $testCase) {
    echo "Test case " . ($i + 1) . ": " . $testCase['url'] . "\n";
    
    // Parse the URL
    $urlParts = parse_url($testCase['url']);
    parse_str($urlParts['query'] ?? '', $params);
    
    $hasYear = isset($params['year']) && !empty($params['year']);
    $hasQuarter = isset($params['quarter']) && !empty($params['quarter']);
    
    if (!$hasYear || !$hasQuarter) {
        $currentYear = $hasYear ? intval($params['year']) : 2568;
        $currentQuarter = $hasQuarter ? intval($params['quarter']) : 3;
        
        $redirectUrl = "index.php?page=home&year={$currentYear}&quarter={$currentQuarter}";
        
        if ($redirectUrl === $testCase['expected']) {
            echo "  ✓ Redirect logic correct: " . $redirectUrl . "\n";
        } else {
            echo "  ✗ Redirect logic incorrect. Expected: " . $testCase['expected'] . ", Got: " . $redirectUrl . "\n";
        }
    } else {
        echo "  ✓ No redirect needed\n";
    }
}

echo "\n5. Testing years data...\n";
try {
    $stmt = $pdo->query("SELECT * FROM years ORDER BY year DESC");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($years)) {
        echo "✓ Years data found:\n";
        foreach ($years as $year) {
            echo "  - ID: " . $year['id'] . ", Year: " . $year['year'] . "\n";
        }
    } else {
        echo "✗ No years data found\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking years data: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "All tests completed. If all tests passed, the implementation should work correctly.\n";
echo "Next steps:\n";
echo "1. Run the migration script if needed\n";
echo "2. Test the actual pages in a web browser\n";
echo "3. Verify URL redirects work as expected\n";
echo "4. Test the manage_home_display.php admin interface\n";
?>