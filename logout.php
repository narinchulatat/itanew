<?php
include './db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // อัปเดตเวลา logout_time ในตาราง login_logs สำหรับผู้ใช้คนนี้
    $stmt = $pdo->prepare("UPDATE login_logs SET logout_time = NOW() WHERE user_id = ? ORDER BY login_time DESC LIMIT 1");
    $stmt->execute([$user_id]);
}

// ทำลาย session
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออกจากระบบ</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-white min-h-screen flex items-center justify-center">
    <script>
        Swal.fire({
            title: 'ออกจากระบบสำเร็จ',
            text: 'ขอบคุณที่ใช้งานระบบ กรุณาเข้าสู่ระบบใหม่หากต้องการใช้งานต่อ',
            icon: 'success',
            showConfirmButton: false,
            timer: 1800,
            background: '#f0f6ff',
            customClass: {
                title: 'text-blue-700 font-bold',
                popup: 'rounded-xl shadow-lg',
                content: 'text-gray-700'
            }
        }).then(function() {
            window.location.href = 'index.php';
        });
    </script>
    <div class="hidden md:block text-center mt-8">
        <h1 class="text-2xl font-bold text-blue-700 mb-2">ออกจากระบบสำเร็จ</h1>
        <p class="text-gray-600">ขอบคุณที่ใช้งานระบบ กรุณาเข้าสู่ระบบใหม่หากต้องการใช้งานต่อ</p>
    </div>
</body>

</html>