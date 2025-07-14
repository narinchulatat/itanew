<?php
/**
 * Test page to demonstrate backup admin interface
 */

// Mock session for testing
session_start();
$_SESSION['role_id'] = 1; // Admin role

// Skip database connection for demo
define('BACKUP_INIT_SKIP', true);

// Include backup files
require_once(__DIR__ . '/backup/backup_config.php');
require_once(__DIR__ . '/backup/backup_manager.php');

$backupManager = new BackupManager();

// Get system status
$systemStatus = $backupManager->getSystemStatus();

// Get backup list
$backups = $backupManager->getBackupList();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cross-Platform Backup System Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-ok { color: #10b981; }
        .status-warning { color: #f59e0b; }
        .status-error { color: #ef4444; }
        .backup-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9fafb;
        }
        .backup-type-files { border-left: 4px solid #10b981; }
        .backup-type-unknown { border-left: 4px solid #6b7280; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-backup"></i> Cross-Platform Backup System Demo
            </h1>
            <p class="text-gray-600 mt-2">Demonstrating Windows & Linux/Unix compatibility</p>
        </div>
        
        <!-- System Status -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="bg-gray-50 px-6 py-4 border-b rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-info-circle"></i> System Status
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div class="text-center">
                        <div class="<?php echo $systemStatus['config_ok'] ? 'status-ok' : 'status-warning'; ?>">
                            <i class="fas fa-check-circle text-4xl"></i>
                        </div>
                        <div class="mt-2 text-sm text-gray-600">
                            Demo Mode
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
                            <i class="fas fa-tools text-4xl"></i>
                        </div>
                        <div class="mt-2 text-sm text-gray-600">
                            Tools: <?php echo $systemStatus['tools']['compression']; ?><br>
                            Available: <?php echo ($systemStatus['tools']['compression'] !== 'none' ? 'Yes' : 'No'); ?>
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
                            Backups: <?php echo $systemStatus['backup_count']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Feature Highlights -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="bg-gray-50 px-6 py-4 border-b rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-star"></i> Cross-Platform Features
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-3">
                            <i class="fab fa-windows text-blue-600"></i> Windows Support
                        </h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>Uses ZipArchive for file compression</li>
                            <li>Handles Windows drive letters correctly</li>
                            <li>Proper path escaping for Windows commands</li>
                            <li>Windows-specific error handling</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-3">
                            <i class="fab fa-linux text-orange-600"></i> Linux/Unix Support
                        </h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>Uses tar/gzip for optimal compression</li>
                            <li>Preserves Unix file permissions</li>
                            <li>Native command-line tool integration</li>
                            <li>Symlink and special file handling</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-magic"></i> Automatic OS Detection
                    </h3>
                    <p class="text-blue-700">
                        The system automatically detects the operating system and uses the appropriate backup method. 
                        No manual configuration needed - it just works on both Windows and Linux/Unix systems!
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Backup List -->
        <div class="bg-white shadow rounded-lg">
            <div class="bg-gray-50 px-6 py-4 border-b rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list"></i> Backup Files
                </h2>
            </div>
            <div class="p-6">
                <?php if (empty($backups)): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                        <i class="fas fa-info-circle mr-2"></i> No backup files found in demo mode.
                    </div>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <?php if ($backup['type'] !== 'unknown'): ?>
                            <div class="backup-card backup-type-<?php echo $backup['type']; ?>">
                                <div class="flex justify-between items-start">
                                    <div class="flex-grow">
                                        <h3 class="font-medium text-gray-800">
                                            <i class="fas fa-<?php echo $backup['type'] === 'database' ? 'database' : 'folder'; ?> mr-2"></i>
                                            <?php echo htmlspecialchars($backup['filename']); ?>
                                        </h3>
                                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 mt-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <?php echo strtoupper($backup['format']); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-weight-hanging mr-1"></i>
                                                <?php echo formatBytes($backup['size']); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?php echo date('Y-m-d H:i:s', $backup['created']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>