<?php
include 'db.php';
header('Content-Type: application/json');
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("SELECT id, category_id, name FROM subcategories WHERE id = ?");
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
