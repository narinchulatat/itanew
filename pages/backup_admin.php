<?php
/**
 * Backup Administration Interface
 * 
 * Web interface for managing backups - integrated with existing admin system
 * Provides secure access to backup, restore, and cleanup functions
 */

// Check session authentication
if (session_status() === PHP_SESSION_NONE) {
    ob_start();
    session_start();
}
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

require_once(__DIR__ . '/../backup/backup_config.php');
require_once(__DIR__ . '/../backup/backup_manager.php');
require_once(__DIR__ . '/../backup/restore.php');
require_once(__DIR__ . '/../backup/cleanup.php');

$backupManager = new BackupManager();
$backupRestore = new BackupRestore();
$backupCleanup = new BackupCleanup();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_db_backup':
            $result = $backupManager->createDatabaseBackup();
            if ($result['success']) {
                $message = "Database backup created successfully: " . $result['filename'];
            } else {
                $error = "Database backup failed: " . $result['error'];
            }
            break;
            
        case 'create_files_backup':
            $result = $backupManager->createFilesBackup();
            if ($result['success']) {
                $message = "Files backup created successfully: " . $result['filename'];
            } else {
                $error = "Files backup failed: " . $result['error'];
            }
            break;
            
        case 'create_full_backup':
            $result = $backupManager->createFullBackup();
            if ($result['success']) {
                $message = "Full backup created successfully";
            } else {
                $error = "Full backup failed - check individual backup status";
            }
            break;
            
        case 'delete_backup':
            $filename = $_POST['filename'] ?? '';
            if ($filename) {
                $result = $backupManager->deleteBackup($filename);
                if ($result['success']) {
                    $message = "Backup deleted successfully";
                } else {
                    $error = "Failed to delete backup: " . $result['error'];
                }
            }
            break;
            
        case 'restore_database':
            $filename = $_POST['filename'] ?? '';
            if ($filename) {
                // Create restore point first
                $restorePoint = $backupRestore->createRestorePoint('Before restore from ' . $filename);
                
                $result = $backupRestore->restoreDatabase($filename);
                if ($result['success']) {
                    $message = "Database restored successfully from: " . $filename;
                    if ($restorePoint['success']) {
                        $message .= " (Restore point created: " . $restorePoint['filename'] . ")";
                    }
                } else {
                    $error = "Database restore failed: " . $result['error'];
                }
            }
            break;
            
        case 'restore_files':
            $filename = $_POST['filename'] ?? '';
            if ($filename) {
                $result = $backupRestore->restoreFiles($filename);
                if ($result['success']) {
                    $message = "Files restored successfully from: " . $filename;
                } else {
                    $error = "Files restore failed: " . $result['error'];
                }
            }
            break;
            
        case 'cleanup':
            $result = $backupCleanup->runCleanup();
            if ($result['success']) {
                $message = "Cleanup completed: deleted {$result['deleted_count']} files, freed " . formatBytes($result['deleted_size']);
            } else {
                $error = "Cleanup failed";
            }
            break;
    }
}

// Get data for display
$backups = $backupManager->getBackupList();
$systemStatus = $backupManager->getSystemStatus();
$cleanupPreview = $backupCleanup->getCleanupPreview();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Management - ITA System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .backup-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9fafb;
        }
        .backup-type-db { border-left: 4px solid #3b82f6; }
        .backup-type-files { border-left: 4px solid #10b981; }
        .backup-type-unknown { border-left: 4px solid #6b7280; }
        
        .status-ok { color: #10b981; }
        .status-warning { color: #f59e0b; }
        .status-error { color: #ef4444; }
        
        .backup-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .backup-info {
            font-size: 0.9em;
            color: #6b7280;
        }
        
        .log-viewer {
            background: #f8f9fa;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            font-size: 0.85em;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-full mx-auto px-4 py-6">
        <div class="w-full">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-backup"></i> Backup Management</h1>
                <a href="home.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Admin
                </a>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 relative">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 relative">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>
                
            <!-- System Status -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="bg-gray-50 px-6 py-4 border-b rounded-t-lg">
                    <h5 class="text-lg font-semibold text-gray-800"><i class="fas fa-info-circle"></i> System Status</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                        <div class="text-center">
                            <div class="<?php echo $systemStatus['config_ok'] ? 'status-ok' : 'status-error'; ?>">
                                <i class="fas fa-<?php echo $systemStatus['config_ok'] ? 'check-circle' : 'exclamation-triangle'; ?> text-4xl"></i>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <?php echo $systemStatus['config_ok'] ? 'System OK' : 'Configuration Error'; ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="status-ok">
                                <i class="fas fa-desktop text-4xl"></i>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                OS: <?php echo $systemStatus['os_family']; ?><br>
                                Format: <?php echo $systemStatus['backup_format']; ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="status-ok">
                                <i class="fas fa-hdd text-4xl"></i>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                Free Space: <?php echo formatBytes($systemStatus['disk_space']); ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="status-ok">
                                <i class="fas fa-archive text-4xl"></i>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                Total Backups: <?php echo $systemStatus['backup_count']; ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="<?php echo $cleanupPreview['count'] > 0 ? 'status-warning' : 'status-ok'; ?>">
                                <i class="fas fa-broom text-4xl"></i>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                Old Files: <?php echo $cleanupPreview['count']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!$systemStatus['config_ok']): ?>
                        <div class="mt-6">
                            <h6 class="text-red-600 font-semibold">Configuration Errors:</h6>
                            <ul class="text-red-600 mt-2 list-disc list-inside">
                                <?php foreach ($systemStatus['errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
                
            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="bg-gray-50 px-6 py-4 border-b rounded-t-lg">
                    <h5 class="text-lg font-semibold text-gray-800"><i class="fas fa-play"></i> Quick Actions</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <form method="POST" class="inline-block w-full">
                                <input type="hidden" name="action" value="create_db_backup">
                                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-4 px-6 rounded-lg text-center">
                                    <i class="fas fa-database text-2xl block mb-2"></i>
                                    Create Database Backup
                                </button>
                            </form>
                        </div>
                        <div>
                            <form method="POST" class="inline-block w-full">
                                <input type="hidden" name="action" value="create_files_backup">
                                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-4 px-6 rounded-lg text-center">
                                    <i class="fas fa-folder text-2xl block mb-2"></i>
                                    Create Files Backup
                                </button>
                            </form>
                        </div>
                        <div>
                            <form method="POST" class="inline-block w-full">
                                <input type="hidden" name="action" value="create_full_backup">
                                <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-600 text-white font-medium py-4 px-6 rounded-lg text-center">
                                    <i class="fas fa-server text-2xl block mb-2"></i>
                                    Create Full Backup
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if ($cleanupPreview['count'] > 0): ?>
                        <div class="mt-6">
                            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <span>
                                            <strong>Cleanup Available:</strong>
                                            <?php echo $cleanupPreview['count']; ?> old backup files can be deleted to free 
                                            <?php echo formatBytes($cleanupPreview['total_size']); ?> of space.
                                        </span>
                                    </div>
                                    <form method="POST" class="inline-block ml-3">
                                        <input type="hidden" name="action" value="cleanup">
                                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-1 px-3 rounded text-sm">
                                            <i class="fas fa-broom mr-1"></i> Run Cleanup
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
                
            <!-- Backup List -->
            <div class="bg-white shadow rounded-lg">
                <div class="bg-gray-50 px-6 py-4 border-b rounded-t-lg">
                    <h5 class="text-lg font-semibold text-gray-800"><i class="fas fa-list"></i> Backup Files</h5>
                </div>
                <div class="p-6">
                    <?php if (empty($backups)): ?>
                        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                            <i class="fas fa-info-circle mr-2"></i> No backup files found. Create your first backup using the actions above.
                        </div>
                    <?php else: ?>
                        <?php foreach ($backups as $backup): ?>
                            <div class="backup-card backup-type-<?php echo $backup['type']; ?>">
                                <div class="flex flex-wrap justify-between items-start">
                                    <div class="flex-grow min-w-0">
                                        <h6 class="mb-2 font-medium text-gray-800">
                                            <i class="fas fa-<?php echo $backup['type'] === 'database' ? 'database' : 'folder'; ?> mr-2"></i>
                                            <?php echo htmlspecialchars($backup['filename']); ?>
                                        </h6>
                                        <div class="backup-info flex flex-wrap gap-4 text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $backup['type'] === 'database' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                                <?php echo ucfirst($backup['type']); ?>
                                            </span>
                                            <?php if (isset($backup['format'])): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <?php echo strtoupper($backup['format']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="flex items-center">
                                                <i class="fas fa-weight-hanging mr-1"></i> <?php echo formatBytes($backup['size']); ?>
                                            </span>
                                            <span class="flex items-center">
                                                <i class="fas fa-calendar mr-1"></i> <?php echo date('Y-m-d H:i:s', $backup['created']); ?>
                                            </span>
                                            <span class="flex items-center">
                                                <i class="fas fa-clock mr-1"></i> <?php echo $backup['age_days']; ?> days old
                                            </span>
                                            <?php if ($backup['age_days'] > BACKUP_RETENTION_DAYS): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> Old
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="backup-actions flex-shrink-0 mt-2 lg:mt-0">
                                        <?php if ($backup['type'] === 'database'): ?>
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="action" value="restore_database">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-1 px-3 rounded text-sm mr-1" 
                                                        onclick="return confirm('Are you sure you want to restore the database? This will overwrite current data. A restore point will be created automatically.')">
                                                    <i class="fas fa-undo mr-1"></i> Restore
                                                </button>
                                            </form>
                                        <?php elseif ($backup['type'] === 'files'): ?>
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="action" value="restore_files">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-1 px-3 rounded text-sm mr-1" 
                                                        onclick="return confirm('Are you sure you want to restore files? This will overwrite current files.')">
                                                    <i class="fas fa-undo mr-1"></i> Restore
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="../backup/files/<?php echo htmlspecialchars($backup['filename']); ?>" 
                                           class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-3 rounded text-sm mr-1" download>
                                            <i class="fas fa-download mr-1"></i> Download
                                        </a>
                                        
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="action" value="delete_backup">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-medium py-1 px-3 rounded text-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this backup?')">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
                
            <!-- Usage Instructions -->
            <div class="bg-white shadow rounded-lg mt-6">
                <div class="bg-gray-50 px-6 py-4 border-b rounded-t-lg">
                    <h5 class="text-lg font-semibold text-gray-800"><i class="fas fa-question-circle"></i> Usage Instructions</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h6 class="font-semibold text-gray-800 mb-3">Backup Types:</h6>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li><strong>Database Backup:</strong> Creates a compressed backup of the MySQL database</li>
                                <li><strong>Files Backup:</strong> Creates a compressed backup of all web files</li>
                                <li><strong>Full Backup:</strong> Creates both database and files backups</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mb-3 mt-6">Cross-Platform Support:</h6>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li><strong>Windows:</strong> Uses ZIP format for file backups</li>
                                <li><strong>Linux/Unix:</strong> Uses TAR.GZ format for file backups</li>
                                <li><strong>Database:</strong> Uses SQL.GZ format on all platforms</li>
                                <li><strong>Auto-detection:</strong> System automatically detects OS and uses appropriate format</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mb-3 mt-6">Automated Backups:</h6>
                            <p class="text-gray-700 mb-3">Set up cron jobs for automated backups:</p>
                            <div class="bg-gray-800 text-gray-100 p-4 rounded text-sm font-mono overflow-x-auto">
                                <div class="mb-2"># Daily database backup at 2 AM</div>
                                <div class="mb-2">0 2 * * * /usr/bin/php <?php echo realpath('../backup/auto_backup.php'); ?> database</div>
                                <div class="mb-2"></div>
                                <div class="mb-2"># Weekly full backup on Sunday at 1 AM</div>
                                <div class="mb-2">0 1 * * 0 /usr/bin/php <?php echo realpath('../backup/auto_backup.php'); ?> full</div>
                                <div class="mb-2"></div>
                                <div class="mb-2"># Daily cleanup at 3 AM</div>
                                <div>0 3 * * * /usr/bin/php <?php echo realpath('../backup/cleanup.php'); ?></div>
                            </div>
                        </div>
                        <div>
                            <h6 class="font-semibold text-gray-800 mb-3">Important Notes:</h6>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li>Database backups are compressed with gzip on all platforms</li>
                                <li>File backups use ZIP on Windows, TAR.GZ on Linux/Unix</li>
                                <li>System automatically detects OS and uses appropriate tools</li>
                                <li>Cross-platform restore: ZIP and TAR.GZ formats both supported</li>
                                <li>Old backups (> <?php echo BACKUP_RETENTION_DAYS; ?> days) are automatically deleted</li>
                                <li>Database restores create automatic restore points</li>
                                <li>All operations are logged for audit purposes</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mb-3 mt-6">Manual Commands:</h6>
                            <p class="text-gray-700 mb-3">You can also run backups manually from command line:</p>
                            <div class="bg-gray-800 text-gray-100 p-4 rounded text-sm font-mono overflow-x-auto">
                                <div class="mb-2"># Create database backup</div>
                                <div class="mb-2">php <?php echo realpath('../backup/auto_backup.php'); ?> database</div>
                                <div class="mb-2"></div>
                                <div class="mb-2"># Create files backup</div>
                                <div class="mb-2">php <?php echo realpath('../backup/auto_backup.php'); ?> files</div>
                                <div class="mb-2"></div>
                                <div class="mb-2"># Create full backup</div>
                                <div class="mb-2">php <?php echo realpath('../backup/auto_backup.php'); ?> full</div>
                                <div class="mb-2"></div>
                                <div class="mb-2"># Run cleanup</div>
                                <div>php <?php echo realpath('../backup/cleanup.php'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>