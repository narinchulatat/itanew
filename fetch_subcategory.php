<?php
include 'db.php';
header('Content-Type: application/json');
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("
        SELECT 
            s.id, 
            s.category_id, 
            s.name,
            c.year,
            c.quarter,
            c.name as category_name
        FROM subcategories s 
        JOIN categories c ON s.category_id = c.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode($row);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
