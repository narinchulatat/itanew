<?php
session_start();
include('../db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_id = intval($_POST['year_id'] ?? 0);
    $quarter = intval($_POST['quarter'] ?? 0);
    
    if ($year_id <= 0 || $quarter < 1 || $quarter > 4) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid year or quarter']);
        exit;
    }
    
    try {
        // Get categories for the specified year and quarter
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE year = ? AND quarter = ? ORDER BY name");
        $stmt->execute([$year_id, $quarter]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['categories' => $categories]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>