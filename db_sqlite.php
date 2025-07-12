<?php
try {
    $pdo = new PDO('sqlite:/tmp/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create basic tables for testing
    $pdo->exec('CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        year INTEGER,
        quarter INTEGER
    )');
    
    $pdo->exec('CREATE TABLE IF NOT EXISTS subcategories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        category_id INTEGER
    )');
    
    $pdo->exec('CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        content TEXT,
        status TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME,
        category_id INTEGER,
        subcategory_id INTEGER,
        access_rights TEXT,
        approved INTEGER DEFAULT 0,
        file_name TEXT,
        file_upload DATETIME,
        file_label TEXT,
        uploaded_by INTEGER
    )');
    
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT,
        role_id INTEGER
    )');
    
    $pdo->exec('CREATE TABLE IF NOT EXISTS home_display_config (
        year INTEGER,
        quarter INTEGER
    )');
    
    // Insert sample data
    $pdo->exec("INSERT OR IGNORE INTO categories (id, name, year, quarter) VALUES 
        (1, 'หมวดหมู่ทดสอบ 1 - ชื่อยาวมากเพื่อทดสอบการแสดงผลใน dropdown', 2024, 1),
        (2, 'หมวดหมู่ทดสอบ 2', 2024, 1)");
    
    $pdo->exec("INSERT OR IGNORE INTO subcategories (id, name, category_id) VALUES 
        (1, 'หมวดหมู่ย่อย 1 - ชื่อยาวมากเพื่อทดสอบการแสดงผลใน dropdown ที่อาจจะยาวมาก', 1),
        (2, 'หมวดหมู่ย่อย 2', 1),
        (3, 'หมวดหมู่ย่อย 3', 2)");
        
    $pdo->exec("INSERT OR IGNORE INTO home_display_config (year, quarter) VALUES (2024, 1)");
    
} catch (PDOException $e) {
    die("Error: Could not connect. " . $e->getMessage());
}