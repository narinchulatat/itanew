<?php
/**
 * Backup System Configuration
 * 
 * This file contains all configuration settings for the backup system
 * It works independently and doesn't affect the existing project
 */

// Database Configuration (from main project)
define('DB_HOST', 'localhost');
define('DB_NAME', 'namyuenh_newita');
define('DB_USER', 'root');
define('DB_PASS', '');

// Backup Configuration
define('BACKUP_DIR', __DIR__ . '/files/');
define('BACKUP_RETENTION_DAYS', 7);
define('BACKUP_LOG_FILE', __DIR__ . '/backup.log');

// Backup Settings
define('BACKUP_DB_PREFIX', 'db_backup_');
define('BACKUP_FILES_PREFIX', 'files_backup_');
define('BACKUP_FULL_PREFIX', 'full_backup_');

// Web Files Backup Settings
define('WEB_ROOT', dirname(__DIR__)); // Parent directory of backup folder
define('EXCLUDE_DIRS', [
    'backup',
    '.git',
    'node_modules',
    'tmp',
    'temp',
    'cache'
]);

define('EXCLUDE_FILES', [
    '.DS_Store',
    'Thumbs.db',
    '*.log',
    '*.tmp'
]);


// Security Settings
define('BACKUP_REQUIRE_AUTH', true);
define('BACKUP_ADMIN_ONLY', true);

// Logging Settings
define('BACKUP_LOG_ENABLED', true);
define('BACKUP_LOG_MAX_SIZE', 10485760); // 10MB

// Cross-Platform OS Detection
define('IS_WINDOWS', PHP_OS_FAMILY === 'Windows');
define('IS_UNIX', PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin');

// Cross-Platform Path Settings
define('BACKUP_PATH_SEPARATOR', IS_WINDOWS ? '\\' : '/');
define('BACKUP_FILE_EXTENSION', IS_WINDOWS ? '.zip' : '.tar.gz');

// Cross-Platform Command Paths
if (IS_WINDOWS) {
    // Windows tool paths - these may need adjustment based on environment
    define('MYSQLDUMP_PATH', 'mysqldump.exe');
    define('MYSQL_PATH', 'mysql.exe');
    define('GZIP_PATH', 'gzip.exe');
    define('GUNZIP_PATH', 'gunzip.exe');
    define('TAR_PATH', 'tar.exe');
    define('UNZIP_PATH', 'unzip.exe');
} else {
    // Unix/Linux tool paths
    define('MYSQLDUMP_PATH', 'mysqldump');
    define('MYSQL_PATH', 'mysql');
    define('GZIP_PATH', 'gzip');
    define('GUNZIP_PATH', 'gunzip');
    define('TAR_PATH', 'tar');
    define('UNZIP_PATH', 'unzip');
}

/**
 * Get database connection for backup operations
 */
function getBackupDbConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        logBackupMessage("Database connection failed: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Log backup messages
 */
function logBackupMessage($message, $level = 'INFO') {
    if (!BACKUP_LOG_ENABLED) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    
    // Check log file size and rotate if needed
    if (file_exists(BACKUP_LOG_FILE) && filesize(BACKUP_LOG_FILE) > BACKUP_LOG_MAX_SIZE) {
        rename(BACKUP_LOG_FILE, BACKUP_LOG_FILE . '.old');
    }
    
    file_put_contents(BACKUP_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Create backup directory if it doesn't exist
 */
function ensureBackupDir() {
    if (!file_exists(BACKUP_DIR)) {
        if (!mkdir(BACKUP_DIR, 0755, true)) {
            logBackupMessage("Failed to create backup directory: " . BACKUP_DIR, 'ERROR');
            return false;
        }
        logBackupMessage("Created backup directory: " . BACKUP_DIR);
    }
    return true;
}

/**
 * Generate backup filename with timestamp
 */
function generateBackupFilename($prefix, $extension = null) {
    $extension = $extension ?: BACKUP_FILE_EXTENSION;
    return $prefix . date('Y-m-d_H-i-s') . $extension;
}

/**
 * Normalize path for cross-platform compatibility
 */
function normalizePath($path) {
    // Convert all slashes to current OS separator
    $path = str_replace(['/', '\\'], BACKUP_PATH_SEPARATOR, $path);
    
    // Remove duplicate separators
    $path = preg_replace('#' . preg_quote(BACKUP_PATH_SEPARATOR) . '+#', BACKUP_PATH_SEPARATOR, $path);
    
    // Trim trailing separator
    $path = rtrim($path, BACKUP_PATH_SEPARATOR);
    
    return $path;
}

/**
 * Get relative path from web root
 */
function getRelativePath($fullPath) {
    $webRoot = normalizePath(WEB_ROOT);
    $fullPath = normalizePath($fullPath);
    
    // Handle Windows drive letters
    if (IS_WINDOWS) {
        $webRoot = strtolower($webRoot);
        $fullPath = strtolower($fullPath);
    }
    
    if (strpos($fullPath, $webRoot) === 0) {
        return substr($fullPath, strlen($webRoot) + 1);
    }
    
    return $fullPath;
}

/**
 * Escape shell argument for cross-platform compatibility
 */
function escapeShellArgCrossPlatform($arg) {
    if (IS_WINDOWS) {
        // Windows-specific escaping - handle spaces and special characters
        $arg = str_replace('"', '""', $arg);
        if (strpos($arg, ' ') !== false || strpos($arg, '&') !== false || strpos($arg, '|') !== false) {
            return '"' . $arg . '"';
        }
        return $arg;
    } else {
        // Unix-specific escaping
        return escapeshellarg($arg);
    }
}

/**
 * Check if a path is a Windows absolute path
 */
function isWindowsAbsolutePath($path) {
    return IS_WINDOWS && preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
}

/**
 * Convert Windows path to Unix-style for consistency
 */
function convertToUnixPath($path) {
    return str_replace('\\', '/', $path);
}

/**
 * Check if required backup tools are available
 */
function checkBackupTools() {
    $tools = [];
    
    // Check mysqldump
    $output = [];
    $return_var = null;
    exec(MYSQLDUMP_PATH . ' --version 2>&1', $output, $return_var);
    $tools['mysqldump'] = ($return_var === 0);
    
    // Check mysql
    exec(MYSQL_PATH . ' --version 2>&1', $output, $return_var);
    $tools['mysql'] = ($return_var === 0);
    
    if (IS_WINDOWS) {
        // Check ZipArchive availability
        $tools['zip'] = class_exists('ZipArchive');
        $tools['compression'] = $tools['zip'] ? 'ZipArchive' : 'none';
    } else {
        // Check tar and gzip
        exec(TAR_PATH . ' --version 2>&1', $output, $return_var);
        $tools['tar'] = ($return_var === 0);
        
        exec(GZIP_PATH . ' --version 2>&1', $output, $return_var);
        $tools['gzip'] = ($return_var === 0);
        
        $tools['compression'] = ($tools['tar'] && $tools['gzip']) ? 'tar.gz' : 'none';
    }
    
    return $tools;
}

/**
 * Check if backup system is properly configured
 */
function checkBackupConfig() {
    $errors = [];
    
    // Check if backup directory is writable
    if (!is_writable(dirname(BACKUP_DIR))) {
        $errors[] = "Backup directory is not writable: " . dirname(BACKUP_DIR);
    }
    
    // Check backup tools
    $tools = checkBackupTools();
    
    if (!$tools['mysqldump']) {
        $errors[] = "mysqldump command not found or not working";
    }
    
    if (!$tools['mysql']) {
        $errors[] = "mysql command not found or not working";
    }
    
    if ($tools['compression'] === 'none') {
        if (IS_WINDOWS) {
            $errors[] = "ZipArchive extension not available";
        } else {
            $errors[] = "tar and/or gzip commands not found or not working";
        }
    }
    
    // Check database connection
    if (!getBackupDbConnection()) {
        $errors[] = "Cannot connect to database";
    }
    
    return $errors;
}

// Initialize backup system
if (!defined('BACKUP_INIT_SKIP')) {
    ensureBackupDir();
    logBackupMessage("Backup system configuration loaded");
}
?>