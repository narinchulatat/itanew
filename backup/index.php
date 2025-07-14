<?php
/**
 * Backup Directory Index
 * 
 * Prevents direct access to backup files for security
 */

// Check if user is admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Access denied');
}

// Redirect to backup admin page
header('Location: ../pages/backup_admin.php');
exit();
?>