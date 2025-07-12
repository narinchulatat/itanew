<?php
session_start(); // เริ่มต้น session
include './db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // ตั้งค่า session สำหรับการเข้าสู่ระบบ
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];

        // บันทึกข้อมูลการเข้าสู่ระบบในตาราง login_logs
        $ip_address = $_SERVER['REMOTE_ADDR']; // เก็บ IP Address ของผู้ใช้ที่ล็อกอิน
        $log_stmt = $pdo->prepare("INSERT INTO login_logs (user_id, login_time, ip_address) VALUES (?, NOW(), ?)");
        $log_stmt->execute([$user['id'], $ip_address]);

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'เข้าสู่ระบบสำเร็จ',
                    html: '<div class=\'text-lg font-bold text-blue-700\'>ยินดีต้อนรับคุณ <span class=\'text-blue-600\'>" . htmlspecialchars($user['fullname'] ?? $user['username']) . "</span></div><div class=\'mt-2 text-gray-700\'>เข้าสู่ระบบเรียบร้อย กรุณารอสักครู่...</div>',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 2000,
                    background: '#f0f6ff',
                    customClass: {
                        title: 'font-bold',
                        popup: 'rounded-xl shadow-lg',
                        htmlContainer: 'text-center'
                    }
                }).then(function() {
                    window.location.href = 'index.php';
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
                    icon: 'error',
                    confirmButtonText: 'ลองอีกครั้ง'
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ITA โรงพยาบาลน้ำยืน</title>
    <!-- Google Font: Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sarabun': ['Sarabun', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sarabun">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo and Title -->
            <div class="text-center">
                <div class="flex justify-center">
                    <img src="img/moph.png" alt="Logo" class="h-16 w-16 mb-4">
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">เข้าสู่ระบบ</h2>
                <p class="text-gray-600">ระบบจัดเก็บเอกสาร ITA โรงพยาบาลน้ำยืน</p>
            </div>

            <!-- Login Form -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <form action="login.php" method="POST" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">ชื่อผู้ใช้</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="username" name="username" required
                                   class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="กรุณากรอกชื่อผู้ใช้">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่าน</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" required
                                   class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="กรุณากรอกรหัสผ่าน">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 font-medium">
                            เข้าสู่ระบบ
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-gray-500">
                <p>&copy; 2024 โรงพยาบาลน้ำยืน - พัฒนาโดย นายนรินทร์ จุลทัศน์</p>
            </div>
        </div>
    </div>
</body>

</html>