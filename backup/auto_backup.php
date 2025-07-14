<?php
/**
 * Automated Backup Script
 * 
 * This script can be run via cron job to perform automated backups
 * It handles both database and files backups with cleanup
 */

require_once 'backup_config.php';
require_once 'backup_manager.php';
require_once 'cleanup.php';

class AutoBackup {
    
    private $backupManager;
    private $cleanup;
    
    public function __construct() {
        $this->backupManager = new BackupManager();
        $this->cleanup = new BackupCleanup();
    }
    
    /**
     * Run automated backup process
     */
    public function runAutoBackup($type = 'full') {
        $startTime = time();
        
        logBackupMessage("Starting automated backup process (type: $type)");
        
        // Check system status first
        $status = $this->backupManager->getSystemStatus();
        if (!$status['config_ok']) {
            logBackupMessage("System configuration check failed: " . implode(', ', $status['errors']), 'ERROR');
            return [
                'success' => false,
                'error' => 'System configuration check failed',
                'details' => $status['errors']
            ];
        }
        
        $results = [
            'success' => true,
            'start_time' => $startTime,
            'type' => $type,
            'backups' => [],
            'cleanup' => null
        ];
        
        // Perform backup based on type
        switch ($type) {
            case 'database':
                $result = $this->backupManager->createDatabaseBackup();
                $results['backups']['database'] = $result;
                if (!$result['success']) {
                    $results['success'] = false;
                }
                break;
                
            case 'files':
                $result = $this->backupManager->createFilesBackup();
                $results['backups']['files'] = $result;
                if (!$result['success']) {
                    $results['success'] = false;
                }
                break;
                
            case 'full':
            default:
                $fullResult = $this->backupManager->createFullBackup();
                $results['backups'] = $fullResult;
                if (!$fullResult['success']) {
                    $results['success'] = false;
                }
                break;
        }
        
        // Run cleanup after backup
        try {
            $cleanupResult = $this->cleanup->runCleanup();
            $results['cleanup'] = $cleanupResult;
            
            if (!$cleanupResult['success']) {
                logBackupMessage("Cleanup failed during auto backup", 'WARNING');
            }
        } catch (Exception $e) {
            logBackupMessage("Cleanup exception during auto backup: " . $e->getMessage(), 'ERROR');
            $results['cleanup'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        $results['end_time'] = time();
        $results['duration'] = $results['end_time'] - $results['start_time'];
        
        $this->logBackupSummary($results);
        
        return $results;
    }
    
    /**
     * Log backup summary
     */
    private function logBackupSummary($results) {
        $duration = $results['duration'];
        $type = $results['type'];
        
        if ($results['success']) {
            logBackupMessage("Automated backup completed successfully (type: $type, duration: {$duration}s)");
        } else {
            logBackupMessage("Automated backup completed with errors (type: $type, duration: {$duration}s)", 'WARNING');
        }
        
        // Log individual backup results
        if (isset($results['backups']['database'])) {
            $db = $results['backups']['database'];
            if ($db['success']) {
                logBackupMessage("Database backup: {$db['filename']} (" . formatBytes($db['size']) . ")");
            } else {
                logBackupMessage("Database backup failed: " . $db['error'], 'ERROR');
            }
        }
        
        if (isset($results['backups']['files'])) {
            $files = $results['backups']['files'];
            if ($files['success']) {
                logBackupMessage("Files backup: {$files['filename']} (" . formatBytes($files['size']) . ")");
            } else {
                logBackupMessage("Files backup failed: " . $files['error'], 'ERROR');
            }
        }
        
        // Log cleanup results
        if ($results['cleanup']) {
            $cleanup = $results['cleanup'];
            if ($cleanup['success']) {
                logBackupMessage("Cleanup: deleted {$cleanup['deleted_count']} files, freed " . formatBytes($cleanup['deleted_size']));
            } else {
                logBackupMessage("Cleanup failed", 'WARNING');
            }
        }
    }
    
    /**
     * Get backup schedule recommendations
     */
    public function getScheduleRecommendations() {
        return [
            'daily_database' => '0 2 * * *',  // Daily at 2 AM
            'weekly_full' => '0 1 * * 0',     // Weekly on Sunday at 1 AM
            'cleanup' => '0 3 * * *'          // Daily at 3 AM
        ];
    }
    
    /**
     * Generate cron job commands
     */
    public function generateCronCommands() {
        $phpPath = '/usr/bin/php';
        $scriptPath = __FILE__;
        
        return [
            'daily_database' => "0 2 * * * $phpPath $scriptPath database",
            'weekly_full' => "0 1 * * 0 $phpPath $scriptPath full",
            'cleanup_only' => "0 3 * * * $phpPath " . __DIR__ . "/cleanup.php"
        ];
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $autoBackup = new AutoBackup();
    
    // Parse command line arguments
    $type = isset($argv[1]) ? $argv[1] : 'full';
    $validTypes = ['full', 'database', 'files'];
    
    if (!in_array($type, $validTypes)) {
        echo "Usage: php auto_backup.php [type]\n";
        echo "Types: " . implode(', ', $validTypes) . "\n";
        echo "Default: full\n\n";
        echo "Examples:\n";
        echo "  php auto_backup.php full      - Full backup (database + files)\n";
        echo "  php auto_backup.php database  - Database backup only\n";
        echo "  php auto_backup.php files     - Files backup only\n\n";
        echo "Recommended cron jobs:\n";
        $cronCommands = $autoBackup->generateCronCommands();
        foreach ($cronCommands as $name => $command) {
            echo "  # $name\n";
            echo "  $command\n\n";
        }
        exit(1);
    }
    
    echo "ITA Automated Backup Script\n";
    echo "===========================\n\n";
    echo "Starting backup process (type: $type)...\n";
    
    $result = $autoBackup->runAutoBackup($type);
    
    if ($result['success']) {
        echo "Backup completed successfully!\n";
        echo "Duration: {$result['duration']} seconds\n\n";
        
        if (isset($result['backups']['database']) && $result['backups']['database']['success']) {
            $db = $result['backups']['database'];
            echo "Database backup: {$db['filename']} (" . formatBytes($db['size']) . ")\n";
        }
        
        if (isset($result['backups']['files']) && $result['backups']['files']['success']) {
            $files = $result['backups']['files'];
            echo "Files backup: {$files['filename']} (" . formatBytes($files['size']) . ")\n";
        }
        
        if ($result['cleanup'] && $result['cleanup']['success']) {
            echo "Cleanup: deleted {$result['cleanup']['deleted_count']} files, freed " . formatBytes($result['cleanup']['deleted_size']) . "\n";
        }
        
        exit(0);
    } else {
        echo "Backup failed!\n";
        echo "Check the backup log for details.\n";
        exit(1);
    }
}

?>