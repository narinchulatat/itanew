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

// MySQL Commands
define('MYSQLDUMP_PATH', 'mysqldump');
define('MYSQL_PATH', 'mysql');
define('GZIP_PATH', 'gzip');
define('GUNZIP_PATH', 'gunzip');

// Security Settings
define('BACKUP_REQUIRE_AUTH', true);
define('BACKUP_ADMIN_ONLY', true);

// Logging Settings
define('BACKUP_LOG_ENABLED', true);
define('BACKUP_LOG_MAX_SIZE', 10485760); // 10MB

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
function generateBackupFilename($prefix, $extension = '.sql.gz') {
    return $prefix . date('Y-m-d_H-i-s') . $extension;
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
    
    // Check if mysqldump is available
    $output = null;
    $return_var = null;
    exec(MYSQLDUMP_PATH . ' --version 2>&1', $output, $return_var);
    if ($return_var !== 0) {
        $errors[] = "mysqldump command not found or not working";
    }
    
    // Check if gzip is available
    exec(GZIP_PATH . ' --version 2>&1', $output, $return_var);
    if ($return_var !== 0) {
        $errors[] = "gzip command not found or not working";
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