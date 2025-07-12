<?php
session_start();
include('../db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_config') {
        $year = intval($_POST['year'] ?? 0);
        $quarter = intval($_POST['quarter'] ?? 0);
        
        if ($year <= 0 || $quarter < 1 || $quarter > 4) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid year or quarter']);
            exit;
        }
        
        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both insert and update
            $stmt = $pdo->prepare("
                INSERT INTO manage_documents_config (user_id, year, quarter) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quarter = VALUES(quarter), updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$user_id, $year, $quarter]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        
    } elseif ($action === 'save_full_config') {
        $year = intval($_POST['year'] ?? 0);
        $quarter = intval($_POST['quarter'] ?? 0);
        $main_category_id = !empty($_POST['main_category_id']) ? intval($_POST['main_category_id']) : null;
        $sub_category_id = !empty($_POST['sub_category_id']) ? intval($_POST['sub_category_id']) : null;
        $is_active = intval($_POST['is_active'] ?? 1);
        
        if ($year <= 0 || $quarter < 1 || $quarter > 4) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid year or quarter']);
            exit;
        }
        
        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE for MySQL or INSERT OR REPLACE for SQLite
            $stmt = $pdo->prepare("
                INSERT INTO manage_documents_config (user_id, year, quarter, main_category_id, sub_category_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    year = VALUES(year),
                    quarter = VALUES(quarter), 
                    main_category_id = VALUES(main_category_id),
                    sub_category_id = VALUES(sub_category_id),
                    is_active = VALUES(is_active),
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$user_id, $year, $quarter, $main_category_id, $sub_category_id, $is_active]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            // Fallback for systems that don't support ON DUPLICATE KEY UPDATE
            try {
                // First try to update existing record
                $updateStmt = $pdo->prepare("
                    UPDATE manage_documents_config 
                    SET year = ?, quarter = ?, main_category_id = ?, sub_category_id = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = ?
                ");
                $updateStmt->execute([$year, $quarter, $main_category_id, $sub_category_id, $is_active, $user_id]);
                
                if ($updateStmt->rowCount() == 0) {
                    // No existing record, insert new one
                    $insertStmt = $pdo->prepare("
                        INSERT INTO manage_documents_config (user_id, year, quarter, main_category_id, sub_category_id, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([$user_id, $year, $quarter, $main_category_id, $sub_category_id, $is_active]);
                }
                
                echo json_encode(['success' => true]);
            } catch (Exception $fallbackError) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $fallbackError->getMessage()]);
            }
        }
        
    } elseif ($action === 'delete_config') {
        try {
            $stmt = $pdo->prepare("DELETE FROM manage_documents_config WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        
    } elseif ($action === 'get_categories') {
        $year = intval($_POST['year'] ?? 0);
        $quarter = intval($_POST['quarter'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE year = ? AND quarter = ? ORDER BY name");
            $stmt->execute([$year, $quarter]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['categories' => $categories]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        
    } elseif ($action === 'get_subcategories') {
        $category_id = intval($_POST['category_id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name");
            $stmt->execute([$category_id]);
            $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['subcategories' => $subcategories]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_config') {
        try {
            // Get user's current configuration
            $stmt = $pdo->prepare("SELECT * FROM manage_documents_config WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get all available years
            $stmt = $pdo->query("SELECT id, year FROM years ORDER BY year DESC");
            $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'config' => $config,
                'years' => $years
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
?>