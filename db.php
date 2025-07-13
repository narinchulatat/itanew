<?php
$host = 'localhost';
$db = 'namyuenh_newita';
$user = 'root';
$pass = '';

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Function to log database errors
function logDatabaseError($error, $context = '') {
    $logFile = __DIR__ . '/logs/database_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$context}: {$error}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

try {
    // Try to connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Test the connection
    $pdo->query("SELECT 1");
    
} catch (PDOException $e) {
    // Log the error
    logDatabaseError($e->getMessage(), 'Database Connection');
    
    // Check if it's a connection error
    if (strpos($e->getMessage(), 'No such file or directory') !== false || 
        strpos($e->getMessage(), 'Connection refused') !== false) {
        // MySQL server is not running
        die("ผิดพลาด! เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: เซิร์ฟเวอร์ฐานข้อมูลไม่สามารถเชื่อมต่อได้");
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        // Authentication error
        die("ผิดพลาด! เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง");
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        // Database doesn't exist
        die("ผิดพลาด! เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ไม่พบฐานข้อมูล");
    } else {
        // General database error
        die("ผิดพลาด! เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
    }
}
