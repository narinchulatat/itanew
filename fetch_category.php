<?php
include './db.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    // ดึงข้อมูล category โดยคืน year เป็น id
    $stmt = $pdo->prepare("SELECT id, name, year, quarter FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($category);
} else {
    echo json_encode(false);
}
?>
