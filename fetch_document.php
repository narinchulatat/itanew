<?php
include 'db.php';
header('Content-Type: application/json');

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("
        SELECT 
            d.id, 
            d.title, 
            d.content, 
            d.category_id, 
            d.subcategory_id, 
            d.access_rights,
            d.file_name,
            c.year,
            c.quarter,
            c.name as category_name,
            s.name as subcategory_name
        FROM documents d 
        LEFT JOIN categories c ON d.category_id = c.id 
        LEFT JOIN subcategories s ON d.subcategory_id = s.id
        WHERE d.id = ?
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