<?php
/**
 * Backup Manager Class
 * 
 * Handles database and file backups with compression and cleanup
 * Works independently without affecting the main project
 */

require_once 'backup_config.php';

class BackupManager {
    
    private $db;
    
    public function __construct() {
        $this->db = getBackupDbConnection();
    }
    
    /**
     * Create database backup
     */
    public function createDatabaseBackup() {
        try {
            ensureBackupDir();
            
            $filename = generateBackupFilename(BACKUP_DB_PREFIX);
            $filepath = BACKUP_DIR . $filename;
            
            // Create mysqldump command
            $command = sprintf(
                '%s --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s | %s > %s',
                MYSQLDUMP_PATH,
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                GZIP_PATH,
                escapeshellarg($filepath)
            );
            
            // Execute backup
            $output = [];
            $return_var = 0;
            exec($command . ' 2>&1', $output, $return_var);
            
            if ($return_var === 0 && file_exists($filepath)) {
                $size = filesize($filepath);
                logBackupMessage("Database backup created successfully: $filename (Size: " . formatBytes($size) . ")");
                return [
                    'success' => true,
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => $size,
                    'type' => 'database'
                ];
            } else {
                $error = implode("\n", $output);
                logBackupMessage("Database backup failed: $error", 'ERROR');
                return [
                    'success' => false,
                    'error' => $error
                ];
            }
            
        } catch (Exception $e) {
            logBackupMessage("Database backup exception: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create web files backup
     */
    public function createFilesBackup() {
        try {
            ensureBackupDir();
            
            $filename = generateBackupFilename(BACKUP_FILES_PREFIX, '.tar.gz');
            $filepath = BACKUP_DIR . $filename;
            
            // Create temporary file list
            $tempList = tempnam(sys_get_temp_dir(), 'backup_files_');
            $this->createFilesList($tempList);
            
            // Create tar command
            $command = sprintf(
                'cd %s && tar -czf %s -T %s',
                escapeshellarg(WEB_ROOT),
                escapeshellarg($filepath),
                escapeshellarg($tempList)
            );
            
            // Execute backup
            $output = [];
            $return_var = 0;
            exec($command . ' 2>&1', $output, $return_var);
            
            // Clean up temp file
            unlink($tempList);
            
            if ($return_var === 0 && file_exists($filepath)) {
                $size = filesize($filepath);
                logBackupMessage("Files backup created successfully: $filename (Size: " . formatBytes($size) . ")");
                return [
                    'success' => true,
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => $size,
                    'type' => 'files'
                ];
            } else {
                $error = implode("\n", $output);
                logBackupMessage("Files backup failed: $error", 'ERROR');
                return [
                    'success' => false,
                    'error' => $error
                ];
            }
            
        } catch (Exception $e) {
            logBackupMessage("Files backup exception: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create full backup (database + files)
     */
    public function createFullBackup() {
        $results = [];
        
        // Create database backup
        $dbResult = $this->createDatabaseBackup();
        $results['database'] = $dbResult;
        
        // Create files backup
        $filesResult = $this->createFilesBackup();
        $results['files'] = $filesResult;
        
        // Log overall result
        if ($dbResult['success'] && $filesResult['success']) {
            logBackupMessage("Full backup completed successfully");
            $results['success'] = true;
        } else {
            logBackupMessage("Full backup completed with errors", 'WARNING');
            $results['success'] = false;
        }
        
        return $results;
    }
    
    /**
     * Get list of existing backups
     */
    public function getBackupList() {
        $backups = [];
        
        if (!file_exists(BACKUP_DIR)) {
            return $backups;
        }
        
        $files = scandir(BACKUP_DIR);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filepath = BACKUP_DIR . $file;
            if (!is_file($filepath)) continue;
            
            $backup = [
                'filename' => $file,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'created' => filemtime($filepath),
                'age_days' => floor((time() - filemtime($filepath)) / 86400)
            ];
            
            // Determine backup type
            if (strpos($file, BACKUP_DB_PREFIX) === 0) {
                $backup['type'] = 'database';
            } elseif (strpos($file, BACKUP_FILES_PREFIX) === 0) {
                $backup['type'] = 'files';
            } else {
                $backup['type'] = 'unknown';
            }
            
            $backups[] = $backup;
        }
        
        // Sort by creation time (newest first)
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }
    
    /**
     * Delete a backup file
     */
    public function deleteBackup($filename) {
        $filepath = BACKUP_DIR . $filename;
        
        if (!file_exists($filepath)) {
            return [
                'success' => false,
                'error' => 'Backup file not found'
            ];
        }
        
        if (unlink($filepath)) {
            logBackupMessage("Backup deleted: $filename");
            return [
                'success' => true,
                'message' => 'Backup deleted successfully'
            ];
        } else {
            logBackupMessage("Failed to delete backup: $filename", 'ERROR');
            return [
                'success' => false,
                'error' => 'Failed to delete backup file'
            ];
        }
    }
    
    /**
     * Create file list for backup (excluding specified directories and files)
     */
    private function createFilesList($listFile) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(WEB_ROOT),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $files = [];
        foreach ($iterator as $file) {
            $relativePath = str_replace(WEB_ROOT . '/', '', $file->getPathname());
            
            // Skip if it's a directory
            if ($file->isDir()) continue;
            
            // Skip excluded directories
            $skip = false;
            foreach (EXCLUDE_DIRS as $excludeDir) {
                if (strpos($relativePath, $excludeDir . '/') === 0) {
                    $skip = true;
                    break;
                }
            }
            
            if ($skip) continue;
            
            // Skip excluded files
            foreach (EXCLUDE_FILES as $excludeFile) {
                if (fnmatch($excludeFile, basename($relativePath))) {
                    $skip = true;
                    break;
                }
            }
            
            if ($skip) continue;
            
            $files[] = $relativePath;
        }
        
        file_put_contents($listFile, implode("\n", $files));
    }
    
    /**
     * Get backup system status
     */
    public function getSystemStatus() {
        $status = [
            'config_ok' => true,
            'errors' => [],
            'disk_space' => disk_free_space(BACKUP_DIR),
            'backup_count' => count($this->getBackupList())
        ];
        
        // Check configuration
        $configErrors = checkBackupConfig();
        if (!empty($configErrors)) {
            $status['config_ok'] = false;
            $status['errors'] = $configErrors;
        }
        
        return $status;
    }
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

?>