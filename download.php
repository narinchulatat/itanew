<?php
session_start();
include './db.php';

// ฟังก์ชันสำหรับการถอดรหัส Base32
function base32_decode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = str_pad($data, ceil(strlen($data) / 8) * 8, '=', STR_PAD_RIGHT);
    $base64 = '';
    for ($i = 0; $i < strlen($data); $i += 8) {
        $chunk = substr($data, $i, 8);
        $bits = '';
        for ($j = 0; $j < 8; $j++) {
            $bits .= str_pad(decbin(strpos($alphabet, $chunk[$j])), 5, '0', STR_PAD_LEFT);
        }
        for ($j = 0; $j < strlen($bits) / 8; $j++) {
            $base64 .= chr(bindec(substr($bits, $j * 8, 8)));
        }
    }
    return rtrim($base64, "\0");
}

// รับชื่อไฟล์จากพารามิเตอร์ URL
$encoded_file_name = isset($_GET['file']) ? $_GET['file'] : '';
$file_name = base32_decode($encoded_file_name);

// ตรวจสอบว่าไฟล์มีอยู่ในโฟลเดอร์ 'uploads'
$file_path = 'uploads/' . $file_name;

if (file_exists($file_path)) {
    // ตั้งค่าหัวข้อ HTTP สำหรับการดาวน์โหลดไฟล์
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    flush(); // ล้างระบบบัฟเฟอร์
    readfile($file_path);
    exit;
} else {
    echo 'ไฟล์ไม่พบ';
}
?>
