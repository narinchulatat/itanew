<?php
// Simple login simulation
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulate login for testing
    $_SESSION['role_id'] = 1;
    $_SESSION['user_id'] = 12;
    $_SESSION['username'] = 'narin';
    
    header('Location: index.php?page=manage_home_display');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Login</title>
</head>
<body>
    <h1>Test Login</h1>
    <form method="POST">
        <button type="submit">Login as Admin</button>
    </form>
</body>
</html>