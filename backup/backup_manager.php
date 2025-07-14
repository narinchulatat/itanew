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
            
            $filename = generateBackupFilename(BACKUP_DB_PREFIX, '.sql.gz');
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
                escapeShellArgCrossPlatform($filepath)
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
            
            $filename = generateBackupFilename(BACKUP_FILES_PREFIX);
            $filepath = BACKUP_DIR . $filename;
            
            if (IS_WINDOWS) {
                return $this->createFilesBackupWindows($filename, $filepath);
            } else {
                return $this->createFilesBackupUnix($filename, $filepath);
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
     * Create files backup using Windows ZipArchive
     */
    private function createFilesBackupWindows($filename, $filepath) {
        if (!class_exists('ZipArchive')) {
            logBackupMessage("ZipArchive extension not available", 'ERROR');
            return [
                'success' => false,
                'error' => 'ZipArchive extension not available'
            ];
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) {
            logBackupMessage("Cannot create zip file: $filepath (Error code: $result)", 'ERROR');
            return [
                'success' => false,
                'error' => "Cannot create zip file (Error code: $result)"
            ];
        }
        
        $webRoot = normalizePath(WEB_ROOT);
        $fileCount = 0;
        
        // Use RecursiveIteratorIterator to traverse directories
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($webRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = normalizePath($file->getPathname());
                $relativePath = getRelativePath($filePath);
                
                // Skip excluded directories and files
                if ($this->shouldExcludeFile($relativePath)) {
                    continue;
                }
                
                // Add file to zip using Unix-style paths for consistency
                $zipPath = str_replace('\\', '/', $relativePath);
                $zip->addFile($filePath, $zipPath);
                $fileCount++;
            }
        }
        
        $zip->close();
        
        if (file_exists($filepath) && filesize($filepath) > 0) {
            $size = filesize($filepath);
            logBackupMessage("Files backup created successfully: $filename ($fileCount files, Size: " . formatBytes($size) . ")");
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $size,
                'type' => 'files',
                'file_count' => $fileCount
            ];
        } else {
            logBackupMessage("Windows files backup failed: zip file not created or empty", 'ERROR');
            return [
                'success' => false,
                'error' => 'Zip file not created or empty'
            ];
        }
    }
    
    /**
     * Create files backup using Unix tar/gzip
     */
    private function createFilesBackupUnix($filename, $filepath) {
        // Create temporary file list
        $tempList = tempnam(sys_get_temp_dir(), 'backup_files_');
        $this->createFilesList($tempList);
        
        // Create tar command
        $command = sprintf(
            'cd %s && %s -czf %s -T %s',
            escapeShellArgCrossPlatform(WEB_ROOT),
            TAR_PATH,
            escapeShellArgCrossPlatform($filepath),
            escapeShellArgCrossPlatform($tempList)
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
            logBackupMessage("Unix files backup failed: $error", 'ERROR');
            return [
                'success' => false,
                'error' => $error
            ];
        }
    }
    
    /**
     * Check if a file should be excluded from backup
     */
    private function shouldExcludeFile($relativePath) {
        // Skip excluded directories
        foreach (EXCLUDE_DIRS as $excludeDir) {
            if (strpos($relativePath, $excludeDir . BACKUP_PATH_SEPARATOR) === 0 || 
                strpos($relativePath, $excludeDir . '/') === 0) {
                return true;
            }
        }
        
        // Skip excluded files
        foreach (EXCLUDE_FILES as $excludeFile) {
            if (fnmatch($excludeFile, basename($relativePath))) {
                return true;
            }
        }
        
        return false;
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
            
            // Determine backup type and format
            if (strpos($file, BACKUP_DB_PREFIX) === 0) {
                $backup['type'] = 'database';
                $backup['format'] = 'sql.gz';
            } elseif (strpos($file, BACKUP_FILES_PREFIX) === 0) {
                $backup['type'] = 'files';
                // Determine format based on file extension
                if (substr($file, -4) === '.zip') {
                    $backup['format'] = 'zip';
                } elseif (substr($file, -7) === '.tar.gz') {
                    $backup['format'] = 'tar.gz';
                } else {
                    $backup['format'] = 'unknown';
                }
            } elseif (strpos($file, 'restore_point_') === 0) {
                $backup['type'] = 'restore_point';
                $backup['format'] = 'sql.gz';
            } else {
                $backup['type'] = 'unknown';
                $backup['format'] = 'unknown';
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
        $webRoot = normalizePath(WEB_ROOT);
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($webRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $files = [];
        foreach ($iterator as $file) {
            // Skip if it's a directory
            if ($file->isDir()) continue;
            
            $relativePath = getRelativePath($file->getPathname());
            
            // Skip excluded files
            if ($this->shouldExcludeFile($relativePath)) {
                continue;
            }
            
            // For tar, use Unix-style paths
            $files[] = str_replace('\\', '/', $relativePath);
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
            'backup_count' => count($this->getBackupList()),
            'os_family' => PHP_OS_FAMILY,
            'is_windows' => IS_WINDOWS,
            'backup_format' => IS_WINDOWS ? 'ZIP' : 'TAR.GZ'
        ];
        
        // Check configuration
        $configErrors = checkBackupConfig();
        if (!empty($configErrors)) {
            $status['config_ok'] = false;
            $status['errors'] = $configErrors;
        }
        
        // Get tool availability
        $status['tools'] = checkBackupTools();
        
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