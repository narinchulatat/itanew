<?php
if (session_status() === PHP_SESSION_NONE) {
    ob_start();
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>
