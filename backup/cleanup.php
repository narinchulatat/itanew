<?php
/**
 * Backup Cleanup Script
 * 
 * Automatically removes backup files older than specified retention period
 * Can be run manually or via cron job
 */

require_once 'backup_config.php';
require_once 'backup_manager.php';

class BackupCleanup {
    
    private $backupManager;
    
    public function __construct() {
        $this->backupManager = new BackupManager();
    }
    
    /**
     * Clean up old backup files
     */
    public function cleanupOldBackups() {
        logBackupMessage("Starting backup cleanup process");
        
        $backups = $this->backupManager->getBackupList();
        $deletedCount = 0;
        $deletedSize = 0;
        $errors = [];
        
        foreach ($backups as $backup) {
            if ($backup['age_days'] > BACKUP_RETENTION_DAYS) {
                $result = $this->backupManager->deleteBackup($backup['filename']);
                
                if ($result['success']) {
                    $deletedCount++;
                    $deletedSize += $backup['size'];
                    logBackupMessage("Deleted old backup: {$backup['filename']} (Age: {$backup['age_days']} days)");
                } else {
                    $errors[] = "Failed to delete {$backup['filename']}: " . $result['error'];
                    logBackupMessage("Failed to delete old backup: {$backup['filename']} - " . $result['error'], 'ERROR');
                }
            }
        }
        
        $message = "Cleanup completed. Deleted $deletedCount files, freed " . formatBytes($deletedSize);
        logBackupMessage($message);
        
        if (!empty($errors)) {
            logBackupMessage("Cleanup errors: " . implode(', ', $errors), 'WARNING');
        }
        
        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'deleted_size' => $deletedSize,
            'errors' => $errors
        ];
    }
    
    /**
     * Get cleanup preview (what would be deleted)
     */
    public function getCleanupPreview() {
        $backups = $this->backupManager->getBackupList();
        $toDelete = [];
        $totalSize = 0;
        
        foreach ($backups as $backup) {
            if ($backup['age_days'] > BACKUP_RETENTION_DAYS) {
                $toDelete[] = $backup;
                $totalSize += $backup['size'];
            }
        }
        
        return [
            'files' => $toDelete,
            'count' => count($toDelete),
            'total_size' => $totalSize
        ];
    }
    
    /**
     * Run cleanup with detailed reporting
     */
    public function runCleanup($dryRun = false) {
        $startTime = time();
        
        if ($dryRun) {
            logBackupMessage("Running cleanup in dry-run mode");
            $preview = $this->getCleanupPreview();
            return [
                'success' => true,
                'dry_run' => true,
                'would_delete' => $preview['count'],
                'would_free' => $preview['total_size'],
                'files' => $preview['files']
            ];
        }
        
        $result = $this->cleanupOldBackups();
        $result['duration'] = time() - $startTime;
        
        return $result;
    }
}

// If run directly from command line
if (php_sapi_name() === 'cli') {
    $cleanup = new BackupCleanup();
    
    // Check for dry-run argument
    $dryRun = in_array('--dry-run', $argv) || in_array('-n', $argv);
    
    echo "ITA Backup Cleanup Script\n";
    echo "========================\n\n";
    
    if ($dryRun) {
        echo "DRY RUN MODE - No files will be deleted\n\n";
    }
    
    $result = $cleanup->runCleanup($dryRun);
    
    if ($result['success']) {
        if ($dryRun) {
            echo "Would delete {$result['would_delete']} files\n";
            echo "Would free " . formatBytes($result['would_free']) . "\n\n";
            
            if (!empty($result['files'])) {
                echo "Files that would be deleted:\n";
                foreach ($result['files'] as $file) {
                    echo "  - {$file['filename']} ({$file['age_days']} days old, " . formatBytes($file['size']) . ")\n";
                }
            }
        } else {
            echo "Cleanup completed successfully\n";
            echo "Deleted {$result['deleted_count']} files\n";
            echo "Freed " . formatBytes($result['deleted_size']) . "\n";
            
            if (!empty($result['errors'])) {
                echo "\nErrors:\n";
                foreach ($result['errors'] as $error) {
                    echo "  - $error\n";
                }
            }
        }
    } else {
        echo "Cleanup failed\n";
    }
    
    echo "\n";
}

?>