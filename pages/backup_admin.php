<?php
/**
 * Backup Administration Interface
 * 
 * Web interface for managing backups - integrated with existing admin system
 * Provides secure access to backup, restore, and cleanup functions
 */

require_once '../session_admin_check.php';
require_once '../backup/backup_config.php';
require_once '../backup/backup_manager.php';
require_once '../backup/restore.php';
require_once '../backup/cleanup.php';

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .backup-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        .backup-type-db { border-left: 4px solid #007bff; }
        .backup-type-files { border-left: 4px solid #28a745; }
        .backup-type-unknown { border-left: 4px solid #6c757d; }
        
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        
        .backup-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .backup-info {
            font-size: 0.9em;
            color: #666;
        }
        
        .log-viewer {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            font-size: 0.85em;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-backup"></i> Backup Management</h1>
                    <a href="home.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Admin
                    </a>
                </div>
                
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- System Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="<?php echo $systemStatus['config_ok'] ? 'status-ok' : 'status-error'; ?>">
                                        <i class="fas fa-<?php echo $systemStatus['config_ok'] ? 'check-circle' : 'exclamation-triangle'; ?> fa-2x"></i>
                                    </div>
                                    <div class="mt-2">
                                        <?php echo $systemStatus['config_ok'] ? 'System OK' : 'Configuration Error'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="status-ok">
                                        <i class="fas fa-hdd fa-2x"></i>
                                    </div>
                                    <div class="mt-2">
                                        Free Space: <?php echo formatBytes($systemStatus['disk_space']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="status-ok">
                                        <i class="fas fa-archive fa-2x"></i>
                                    </div>
                                    <div class="mt-2">
                                        Total Backups: <?php echo $systemStatus['backup_count']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="<?php echo $cleanupPreview['count'] > 0 ? 'status-warning' : 'status-ok'; ?>">
                                        <i class="fas fa-broom fa-2x"></i>
                                    </div>
                                    <div class="mt-2">
                                        Old Files: <?php echo $cleanupPreview['count']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!$systemStatus['config_ok']): ?>
                            <div class="mt-3">
                                <h6 class="text-danger">Configuration Errors:</h6>
                                <ul class="text-danger">
                                    <?php foreach ($systemStatus['errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-play"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="create_db_backup">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-database"></i><br>
                                        Create Database Backup
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="create_files_backup">
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-folder"></i><br>
                                        Create Files Backup
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="create_full_backup">
                                    <button type="submit" class="btn btn-info btn-lg w-100">
                                        <i class="fas fa-server"></i><br>
                                        Create Full Backup
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if ($cleanupPreview['count'] > 0): ?>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Cleanup Available:</strong>
                                        <?php echo $cleanupPreview['count']; ?> old backup files can be deleted to free 
                                        <?php echo formatBytes($cleanupPreview['total_size']); ?> of space.
                                        
                                        <form method="POST" class="d-inline ms-3">
                                            <input type="hidden" name="action" value="cleanup">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-broom"></i> Run Cleanup
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Backup List -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Backup Files</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($backups)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No backup files found. Create your first backup using the actions above.
                            </div>
                        <?php else: ?>
                            <?php foreach ($backups as $backup): ?>
                                <div class="backup-card backup-type-<?php echo $backup['type']; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <i class="fas fa-<?php echo $backup['type'] === 'database' ? 'database' : 'folder'; ?>"></i>
                                                <?php echo htmlspecialchars($backup['filename']); ?>
                                            </h6>
                                            <div class="backup-info">
                                                <span class="badge bg-<?php echo $backup['type'] === 'database' ? 'primary' : 'success'; ?> me-2">
                                                    <?php echo ucfirst($backup['type']); ?>
                                                </span>
                                                <span class="me-3">
                                                    <i class="fas fa-weight-hanging"></i> <?php echo formatBytes($backup['size']); ?>
                                                </span>
                                                <span class="me-3">
                                                    <i class="fas fa-calendar"></i> <?php echo date('Y-m-d H:i:s', $backup['created']); ?>
                                                </span>
                                                <span class="me-3">
                                                    <i class="fas fa-clock"></i> <?php echo $backup['age_days']; ?> days old
                                                </span>
                                                <?php if ($backup['age_days'] > BACKUP_RETENTION_DAYS): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Old
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="backup-actions">
                                            <?php if ($backup['type'] === 'database'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="restore_database">
                                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" 
                                                            onclick="return confirm('Are you sure you want to restore the database? This will overwrite current data. A restore point will be created automatically.')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </button>
                                                </form>
                                            <?php elseif ($backup['type'] === 'files'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="restore_files">
                                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" 
                                                            onclick="return confirm('Are you sure you want to restore files? This will overwrite current files.')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <a href="../backup/files/<?php echo htmlspecialchars($backup['filename']); ?>" 
                                               class="btn btn-info btn-sm" download>
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_backup">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this backup?')">
                                                    <i class="fas fa-trash"></i> Delete
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
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-question-circle"></i> Usage Instructions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Backup Types:</h6>
                                <ul>
                                    <li><strong>Database Backup:</strong> Creates a compressed backup of the MySQL database</li>
                                    <li><strong>Files Backup:</strong> Creates a compressed backup of all web files</li>
                                    <li><strong>Full Backup:</strong> Creates both database and files backups</li>
                                </ul>
                                
                                <h6>Automated Backups:</h6>
                                <p>Set up cron jobs for automated backups:</p>
                                <pre><code># Daily database backup at 2 AM
0 2 * * * /usr/bin/php <?php echo realpath('../backup/auto_backup.php'); ?> database

# Weekly full backup on Sunday at 1 AM
0 1 * * 0 /usr/bin/php <?php echo realpath('../backup/auto_backup.php'); ?> full

# Daily cleanup at 3 AM
0 3 * * * /usr/bin/php <?php echo realpath('../backup/cleanup.php'); ?></code></pre>
                            </div>
                            <div class="col-md-6">
                                <h6>Important Notes:</h6>
                                <ul>
                                    <li>Backups are automatically compressed with gzip</li>
                                    <li>Old backups (> <?php echo BACKUP_RETENTION_DAYS; ?> days) are automatically deleted</li>
                                    <li>Database restores create automatic restore points</li>
                                    <li>All operations are logged for audit purposes</li>
                                </ul>
                                
                                <h6>Manual Commands:</h6>
                                <p>You can also run backups manually from command line:</p>
                                <pre><code># Create database backup
php <?php echo realpath('../backup/auto_backup.php'); ?> database

# Create files backup
php <?php echo realpath('../backup/auto_backup.php'); ?> files

# Create full backup
php <?php echo realpath('../backup/auto_backup.php'); ?> full

# Run cleanup
php <?php echo realpath('../backup/cleanup.php'); ?></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>