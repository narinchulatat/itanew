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
    $category_id = intval($_POST['category_id'] ?? 0);
    
    if ($category_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid category ID']);
        exit;
    }
    
    try {
        // Get subcategories for the specified category
        $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name");
        $stmt->execute([$category_id]);
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['subcategories' => $subcategories]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>