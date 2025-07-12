<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : 'Guest';

// ดึงข้อมูลรูปโปรไฟล์จากฐานข้อมูล
$userImage = 'img/default.png'; // กำหนดค่าเริ่มต้น
$pendingDocuments = 0; // กำหนดค่าเริ่มต้นของการแจ้งเตือนเอกสารที่ยังไม่ได้อนุมัติ

if ($isLoggedIn) {
    include './db.php';
    $stmt = $pdo->prepare("SELECT profile_image FROM profile WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    if ($profile && !empty($profile['profile_image'])) {
        $userImage = 'uploads/' . $profile['profile_image'];
    }
    
    // ดึงจำนวนเอกสารที่ยังไม่ได้อนุมัติและมีการเข้าถึงเป็น public
    $stmt = $pdo->query("SELECT COUNT(*) FROM documents WHERE approved = 0 AND access_rights = 'public'");
    $pendingDocuments = $stmt->fetchColumn();
    
    // ตรวจสอบว่ามีการแสดง popup แล้วหรือยัง
    if (!isset($_SESSION['popup_shown']) && $pendingDocuments > 0) {
        $_SESSION['popup_shown'] = true; // ตั้งค่าให้ไม่แสดง popup อีกครั้งหลังจากนี้
    } else {
        $pendingDocuments = 0; // รีเซ็ตค่าถ้าจะแสดง popup แค่ครั้งเดียว
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
<!-- Top Header -->
<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        <!-- Logo -->
        <div class="flex items-center">
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-600 hover:text-gray-900 mr-3">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <a href="index.php?page=home" class="flex items-center">
                <img src="img/moph.png" alt="Logo" class="h-8 w-8 mr-3">
                <div>
                    <span class="text-sm font-semibold text-blue-600">ITA</span>
                    <span class="text-sm text-gray-600 ml-1">โรงพยาบาลน้ำยืน</span>
                </div>
            </a>
        </div>

        <!-- User Menu -->
        <div class="flex items-center space-x-4">
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                    <img src="<?= $userImage ?>" class="w-8 h-8 rounded-full object-cover" alt="User Image">
                    <span class="hidden md:block font-medium"><?= htmlspecialchars($username) ?></span>
                    <?php if ($pendingDocuments > 0): ?>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-bold bg-red-500 text-white rounded-full">
                            <?= $pendingDocuments ?>
                        </span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down text-sm"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div x-show="open" @click.away="open = false" x-transition 
                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50">
                    <div class="py-1">
                        <div class="px-4 py-3 border-b border-gray-200 bg-blue-50">
                            <div class="flex items-center">
                                <img src="<?= $userImage ?>" class="w-10 h-10 rounded-full object-cover mr-3" alt="User Image">
                                <div>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($username) ?></p>
                                    <p class="text-sm text-gray-600">ผู้ใช้งาน</p>
                                </div>
                            </div>
                        </div>
                        <a href="index.php?page=manage_profiles" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-user mr-2"></i>โปรไฟล์
                        </a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- jQuery -->
<!-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> -->
<!-- Bootstrap 3.3.7 -->
<!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script> -->
<!-- AdminLTE App -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/js/adminlte.min.js"></script> -->

<?php if ($pendingDocuments > 0): ?>
    <script>
        Swal.fire({
            title: "เอกสารยังไม่ได้อนุมัติ!",
            text: "มีเอกสารที่ยังไม่ได้อนุมัติจำนวน <?= $pendingDocuments ?> รายการ",
            icon: "warning",
            confirmButtonText: "รับทราบ",
            confirmButtonColor: "#3085d6"
        });
    </script>
<?php endif; ?>

</body>
</html>
