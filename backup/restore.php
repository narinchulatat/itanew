<?php
/**
 * Backup Restore System
 * 
 * Handles restoration of database and file backups
 * Works independently with proper security checks
 */

require_once 'backup_config.php';
require_once 'backup_manager.php';

class BackupRestore {
    
    private $db;
    private $backupManager;
    
    public function __construct() {
        $this->db = getBackupDbConnection();
        $this->backupManager = new BackupManager();
    }
    
    /**
     * Restore database from backup
     */
    public function restoreDatabase($backupFilename) {
        try {
            $backupPath = BACKUP_DIR . $backupFilename;
            
            if (!file_exists($backupPath)) {
                return [
                    'success' => false,
                    'error' => 'Backup file not found'
                ];
            }
            
            // Check if it's a database backup
            if (strpos($backupFilename, BACKUP_DB_PREFIX) !== 0) {
                return [
                    'success' => false,
                    'error' => 'Invalid database backup file'
                ];
            }
            
            logBackupMessage("Starting database restore from: $backupFilename");
            
            // Create temporary uncompressed file
            $tempFile = tempnam(sys_get_temp_dir(), 'restore_');
            
            // Decompress backup
            $command = sprintf(
                '%s -dc %s > %s',
                GUNZIP_PATH,
                escapeShellArgCrossPlatform($backupPath),
                escapeShellArgCrossPlatform($tempFile)
            );
            
            $output = [];
            $return_var = 0;
            exec($command . ' 2>&1', $output, $return_var);
            
            if ($return_var !== 0) {
                unlink($tempFile);
                $error = implode("\n", $output);
                logBackupMessage("Failed to decompress backup: $error", 'ERROR');
                return [
                    'success' => false,
                    'error' => 'Failed to decompress backup: ' . $error
                ];
            }
            
            // Restore database
            $command = sprintf(
                '%s --host=%s --user=%s --password=%s %s < %s',
                MYSQL_PATH,
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                escapeShellArgCrossPlatform($tempFile)
            );
            
            $output = [];
            $return_var = 0;
            exec($command . ' 2>&1', $output, $return_var);
            
            // Clean up temp file
            unlink($tempFile);
            
            if ($return_var === 0) {
                logBackupMessage("Database restore completed successfully from: $backupFilename");
                return [
                    'success' => true,
                    'message' => 'Database restored successfully'
                ];
            } else {
                $error = implode("\n", $output);
                logBackupMessage("Database restore failed: $error", 'ERROR');
                return [
                    'success' => false,
                    'error' => 'Database restore failed: ' . $error
                ];
            }
            
        } catch (Exception $e) {
            logBackupMessage("Database restore exception: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore files from backup
     */
    public function restoreFiles($backupFilename, $targetDir = null) {
        try {
            $backupPath = BACKUP_DIR . $backupFilename;
            
            if (!file_exists($backupPath)) {
                return [
                    'success' => false,
                    'error' => 'Backup file not found'
                ];
            }
            
            // Check if it's a files backup
            if (strpos($backupFilename, BACKUP_FILES_PREFIX) !== 0) {
                return [
                    'success' => false,
                    'error' => 'Invalid files backup file'
                ];
            }
            
            $targetDir = $targetDir ?: WEB_ROOT;
            
            logBackupMessage("Starting files restore from: $backupFilename to: $targetDir");
            
            // Determine format and restore accordingly
            if (substr($backupFilename, -4) === '.zip') {
                return $this->restoreFilesFromZip($backupPath, $targetDir, $backupFilename);
            } elseif (substr($backupFilename, -7) === '.tar.gz') {
                return $this->restoreFilesFromTarGz($backupPath, $targetDir, $backupFilename);
            } else {
                return [
                    'success' => false,
                    'error' => 'Unsupported backup format'
                ];
            }
            
        } catch (Exception $e) {
            logBackupMessage("Files restore exception: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore files from ZIP archive
     */
    private function restoreFilesFromZip($backupPath, $targetDir, $backupFilename) {
        if (!class_exists('ZipArchive')) {
            return [
                'success' => false,
                'error' => 'ZipArchive extension not available'
            ];
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($backupPath);
        
        if ($result !== TRUE) {
            return [
                'success' => false,
                'error' => "Cannot open zip file (Error code: $result)"
            ];
        }
        
        // Extract files
        $extractResult = $zip->extractTo($targetDir);
        $zip->close();
        
        if ($extractResult) {
            logBackupMessage("Files restore completed successfully from: $backupFilename");
            return [
                'success' => true,
                'message' => 'Files restored successfully from ZIP archive'
            ];
        } else {
            logBackupMessage("ZIP files restore failed", 'ERROR');
            return [
                'success' => false,
                'error' => 'Failed to extract ZIP archive'
            ];
        }
    }
    
    /**
     * Restore files from TAR.GZ archive
     */
    private function restoreFilesFromTarGz($backupPath, $targetDir, $backupFilename) {
        // Create restore command
        $command = sprintf(
            'cd %s && %s -xzf %s',
            escapeShellArgCrossPlatform($targetDir),
            TAR_PATH,
            escapeShellArgCrossPlatform($backupPath)
        );
        
        $output = [];
        $return_var = 0;
        exec($command . ' 2>&1', $output, $return_var);
        
        if ($return_var === 0) {
            logBackupMessage("Files restore completed successfully from: $backupFilename");
            return [
                'success' => true,
                'message' => 'Files restored successfully from TAR.GZ archive'
            ];
        } else {
            $error = implode("\n", $output);
            logBackupMessage("TAR.GZ files restore failed: $error", 'ERROR');
            return [
                'success' => false,
                'error' => 'Files restore failed: ' . $error
            ];
        }
    }
    
    /**
     * Get backup file information
     */
    public function getBackupInfo($backupFilename) {
        $backupPath = BACKUP_DIR . $backupFilename;
        
        if (!file_exists($backupPath)) {
            return [
                'success' => false,
                'error' => 'Backup file not found'
            ];
        }
        
        $info = [
            'filename' => $backupFilename,
            'size' => filesize($backupPath),
            'created' => filemtime($backupPath),
            'path' => $backupPath
        ];
        
        // Determine type and format
        if (strpos($backupFilename, BACKUP_DB_PREFIX) === 0) {
            $info['type'] = 'database';
            $info['format'] = 'sql.gz';
            $info['can_restore'] = true;
        } elseif (strpos($backupFilename, BACKUP_FILES_PREFIX) === 0) {
            $info['type'] = 'files';
            $info['can_restore'] = true;
            
            // Determine format based on extension
            if (substr($backupFilename, -4) === '.zip') {
                $info['format'] = 'zip';
            } elseif (substr($backupFilename, -7) === '.tar.gz') {
                $info['format'] = 'tar.gz';
            } else {
                $info['format'] = 'unknown';
                $info['can_restore'] = false;
            }
        } elseif (strpos($backupFilename, 'restore_point_') === 0) {
            $info['type'] = 'restore_point';
            $info['format'] = 'sql.gz';
            $info['can_restore'] = true;
        } else {
            $info['type'] = 'unknown';
            $info['format'] = 'unknown';
            $info['can_restore'] = false;
        }
        
        return [
            'success' => true,
            'info' => $info
        ];
    }
    
    /**
     * Verify backup integrity
     */
    public function verifyBackup($backupFilename) {
        $backupPath = BACKUP_DIR . $backupFilename;
        
        if (!file_exists($backupPath)) {
            return [
                'success' => false,
                'error' => 'Backup file not found'
            ];
        }
        
        $info = $this->getBackupInfo($backupFilename);
        if (!$info['success']) {
            return $info;
        }
        
        $type = $info['info']['type'];
        $format = $info['info']['format'] ?? 'unknown';
        $isValid = false;
        $details = [];
        
        if ($type === 'database') {
            // Test gzip integrity
            $command = sprintf('%s -t %s', GZIP_PATH, escapeShellArgCrossPlatform($backupPath));
            $output = [];
            $return_var = 0;
            exec($command . ' 2>&1', $output, $return_var);
            
            if ($return_var === 0) {
                $isValid = true;
                $details[] = 'Gzip compression is valid';
                
                // Try to peek into SQL content
                $command = sprintf('%s -dc %s | head -20', GUNZIP_PATH, escapeShellArgCrossPlatform($backupPath));
                exec($command . ' 2>&1', $output, $return_var);
                
                if ($return_var === 0 && !empty($output)) {
                    $hasSQL = false;
                    foreach ($output as $line) {
                        if (strpos($line, 'CREATE TABLE') !== false || 
                            strpos($line, 'INSERT INTO') !== false ||
                            strpos($line, 'DROP TABLE') !== false) {
                            $hasSQL = true;
                            break;
                        }
                    }
                    
                    if ($hasSQL) {
                        $details[] = 'Contains valid SQL statements';
                    } else {
                        $details[] = 'Warning: May not contain valid SQL content';
                    }
                }
            } else {
                $details[] = 'Gzip compression is corrupted';
            }
            
        } elseif ($type === 'files') {
            if ($format === 'zip') {
                // Test ZIP integrity
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    $result = $zip->open($backupPath, ZipArchive::CHECKCONS);
                    
                    if ($result === TRUE) {
                        $isValid = true;
                        $details[] = 'ZIP archive is valid';
                        $details[] = "Contains {$zip->numFiles} files";
                        $zip->close();
                    } else {
                        $details[] = "ZIP archive is corrupted (Error code: $result)";
                    }
                } else {
                    $details[] = 'Cannot verify ZIP - ZipArchive not available';
                }
            } elseif ($format === 'tar.gz') {
                // Test tar.gz integrity
                $command = sprintf('%s -tzf %s > /dev/null', TAR_PATH, escapeShellArgCrossPlatform($backupPath));
                $output = [];
                $return_var = 0;
                exec($command . ' 2>&1', $output, $return_var);
                
                if ($return_var === 0) {
                    $isValid = true;
                    $details[] = 'TAR.GZ archive is valid';
                    
                    // Count files in archive
                    $command = sprintf('%s -tzf %s | wc -l', TAR_PATH, escapeShellArgCrossPlatform($backupPath));
                    $fileCount = (int) trim(shell_exec($command));
                    $details[] = "Contains $fileCount files";
                } else {
                    $details[] = 'TAR.GZ archive is corrupted';
                }
            } else {
                $details[] = 'Unknown file format - cannot verify';
            }
        }
        
        return [
            'success' => true,
            'valid' => $isValid,
            'details' => $details,
            'format' => $format
        ];
    }
    
    /**
     * Create restore point before restoration
     */
    public function createRestorePoint($description = '') {
        $description = $description ?: 'Pre-restore backup ' . date('Y-m-d H:i:s');
        
        logBackupMessage("Creating restore point: $description");
        
        // Create quick backup
        $dbBackup = $this->backupManager->createDatabaseBackup();
        
        if ($dbBackup['success']) {
            // Rename to indicate it's a restore point
            $originalPath = $dbBackup['filepath'];
            $restorePointPath = str_replace(BACKUP_DB_PREFIX, 'restore_point_', $originalPath);
            
            if (rename($originalPath, $restorePointPath)) {
                logBackupMessage("Restore point created: " . basename($restorePointPath));
                return [
                    'success' => true,
                    'filename' => basename($restorePointPath),
                    'description' => $description
                ];
            }
        }
        
        return [
            'success' => false,
            'error' => 'Failed to create restore point'
        ];
    }
}

?>