<?php
include('./db.php');

if (isset($_GET['category_id'])) {
    $category_id = $_GET['category_id'];

    // Fetch subcategories based on category_id
    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output subcategories as options
    foreach ($subcategories as $subcategory) {
        echo '<option value="' . $subcategory['id'] . '">' . htmlspecialchars($subcategory['name']) . '</option>';
    }
}
?>
