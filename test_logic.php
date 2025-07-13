#!/usr/bin/env php
<?php
// Simple test to verify the URL redirect logic without database connection
echo "Testing URL redirect logic (no database required)...\n\n";

// Test cases for URL redirect logic
$testCases = [
    [
        'description' => 'No year or quarter parameters',
        'params' => ['page' => 'home'],
        'expected_redirect' => true,
        'expected_url' => 'index.php?page=home&year=2568&quarter=3'
    ],
    [
        'description' => 'Only year parameter',
        'params' => ['page' => 'home', 'year' => '2567'],
        'expected_redirect' => true,
        'expected_url' => 'index.php?page=home&year=2567&quarter=3'
    ],
    [
        'description' => 'Only quarter parameter',
        'params' => ['page' => 'home', 'quarter' => '2'],
        'expected_redirect' => true,
        'expected_url' => 'index.php?page=home&year=2568&quarter=2'
    ],
    [
        'description' => 'Both year and quarter parameters',
        'params' => ['page' => 'home', 'year' => '2567', 'quarter' => '2'],
        'expected_redirect' => false,
        'expected_url' => null
    ],
    [
        'description' => 'Empty year parameter',
        'params' => ['page' => 'home', 'year' => '', 'quarter' => '2'],
        'expected_redirect' => true,
        'expected_url' => 'index.php?page=home&year=2568&quarter=2'
    ],
    [
        'description' => 'Empty quarter parameter',
        'params' => ['page' => 'home', 'year' => '2567', 'quarter' => ''],
        'expected_redirect' => true,
        'expected_url' => 'index.php?page=home&year=2567&quarter=3'
    ],
];

$defaultYear = 2568;
$defaultQuarter = 3;

foreach ($testCases as $i => $testCase) {
    echo "Test " . ($i + 1) . ": " . $testCase['description'] . "\n";
    
    // Simulate the logic from home.php
    $hasYear = isset($testCase['params']['year']) && !empty($testCase['params']['year']);
    $hasQuarter = isset($testCase['params']['quarter']) && !empty($testCase['params']['quarter']);
    
    if (!$hasYear || !$hasQuarter) {
        $currentYear = $hasYear ? intval($testCase['params']['year']) : $defaultYear;
        $currentQuarter = $hasQuarter ? intval($testCase['params']['quarter']) : $defaultQuarter;
        
        $redirectUrl = "index.php?page=home&year={$currentYear}&quarter={$currentQuarter}";
        
        if ($testCase['expected_redirect']) {
            if ($redirectUrl === $testCase['expected_url']) {
                echo "  ✓ PASS: Redirect URL correct: " . $redirectUrl . "\n";
            } else {
                echo "  ✗ FAIL: Expected: " . $testCase['expected_url'] . ", Got: " . $redirectUrl . "\n";
            }
        } else {
            echo "  ✗ FAIL: Unexpected redirect generated\n";
        }
    } else {
        if (!$testCase['expected_redirect']) {
            echo "  ✓ PASS: No redirect needed\n";
        } else {
            echo "  ✗ FAIL: Expected redirect but none generated\n";
        }
    }
    echo "\n";
}

echo "=== File Structure Check ===\n";
$filesToCheck = [
    'pages/home.php',
    'pages/manage_home_display.php',
    'ajax/get_quarter_data.php',
    'migration_home_display_config.sql'
];

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

echo "\n=== JavaScript URL Update Check ===\n";
$homePhpContent = file_get_contents('pages/home.php');
if (strpos($homePhpContent, 'url.searchParams.set(\'year\', currentYear)') !== false) {
    echo "✓ JavaScript includes year parameter update\n";
} else {
    echo "✗ JavaScript missing year parameter update\n";
}

if (strpos($homePhpContent, 'url.searchParams.set(\'quarter\', quarter)') !== false) {
    echo "✓ JavaScript includes quarter parameter update\n";
} else {
    echo "✗ JavaScript missing quarter parameter update\n";
}

echo "\n=== Form Check ===\n";
if (strpos($homePhpContent, '<input type="hidden" name="page" value="home">') !== false) {
    echo "✓ Form includes hidden page parameter\n";
} else {
    echo "✗ Form missing hidden page parameter\n";
}

echo "\nAll tests completed!\n";
?>