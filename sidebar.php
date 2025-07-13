<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : 'Guest';
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;
// ดึงข้อมูลรูปโปรไฟล์จากฐานข้อมูล
$userImage = 'img/default.png'; // กำหนดค่าเริ่มต้น
if ($isLoggedIn) {
    include './db.php';
    $stmt = $pdo->prepare("SELECT profile_image FROM profile WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    if ($profile && !empty($profile['profile_image'])) {
        $userImage = 'uploads/' . $profile['profile_image'];
    }
}
?>

<!-- Sidebar -->
<aside class="bg-gray-900 text-white w-64 min-h-screen fixed lg:relative lg:translate-x-0 transform -translate-x-full transition-transform duration-300 ease-in-out z-30"
       :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
    
    <!-- Sidebar Header -->
    <div class="p-6 border-b border-gray-700">
        <div class="flex items-center">
            <img src="<?= $userImage ?>" class="w-10 h-10 rounded-full object-cover mr-3" alt="User Image">
            <div>
                <p class="font-medium text-white"><?= htmlspecialchars($username) ?></p>
                <p class="text-sm text-gray-400 flex items-center">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                    Online
                </p>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="p-4 border-b border-gray-700">
        <form class="relative">
            <input type="text" name="q" class="w-full bg-gray-800 text-white rounded-lg px-4 py-2 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="ค้นหา...">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </form>
    </div>

    <!-- Navigation Menu -->
    <nav class="p-4">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-4">
            เมนูหลัก
        </div>
        <ul class="space-y-2">
            <li>
                <a href="index.php?page=home" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'home') ? 'bg-blue-600 text-white' : ''; ?>">
                    <i class="fas fa-home mr-3"></i>
                    <span>หน้าหลัก</span>
                </a>
            </li>
            <!-- Menu สำหรับผู้ใช้ทั่วไป (role_id = 3) -->
            <?php if ($isLoggedIn && $role_id == '3'): ?>
                <li>
                    <a href="index.php?page=manage_documents" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_documents') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-upload mr-3"></i>
                        <span>อัพโหลดเอกสาร</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=manage_documents_config" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_documents_config') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-cog mr-3"></i>
                        <span>ตั้งค่าจัดการเอกสาร</span>
                    </a>
                </li>
            <?php endif; ?>
            <!-- Menu สำหรับ Admin (role_id = 1) -->
            <?php if ($isLoggedIn && $role_id == '1'): ?>
                <li>
                    <a href="index.php?page=manage_documents" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_documents') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-upload mr-3"></i>
                        <span>อัพโหลดเอกสาร</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=manage_documents_config" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_documents_config') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-cog mr-3"></i>
                        <span>ตั้งค่าจัดการเอกสาร</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=approve_documents" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'approve_documents') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-check mr-3"></i>
                        <span>อนุมัติเอกสาร</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=manage_categories" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_categories') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-list mr-3"></i>
                        <span>หมวดหมู่หลัก</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=manage_subcategories" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_subcategories') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-list-alt mr-3"></i>
                        <span>หมวดหมู่ย่อย</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=manage_years" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_years') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-calendar-alt mr-3"></i>
                        <span>กำหนดปี (พ.ศ.)</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=manage_users" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_users') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span>จัดการผู้ใช้</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=simple_home_display" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'simple_home_display') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-cog mr-3"></i>
                        <span>ตั้งค่าการแสดงผลหน้าแรก</span>
                    </a>
                </li>
            <?php endif; ?>
            <!-- Menu สำหรับ Super Admin (role_id = 2) -->
            <?php if ($isLoggedIn && $role_id == '2'): ?>
                <li>
                    <a href="index.php?page=approve_documents" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'approve_documents') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-check mr-3"></i>
                        <span>อนุมัติเอกสาร</span>
                    </a>
                </li>
            <?php endif; ?>
            <!-- Menu โพรไฟล์ สำหรับผู้ใช้ที่ล็อกอิน -->
            <?php if ($isLoggedIn): ?>
                <li>
                    <a href="index.php?page=manage_profiles" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'manage_profiles') ? 'bg-blue-600 text-white' : ''; ?>">
                        <i class="fas fa-user mr-3"></i>
                        <span>โพรไฟล์ของฉัน</span>
                    </a>
                </li>
            <?php endif; ?>
            <!-- เพิ่มลิงก์ login/logout -->
            <?php if (!$isLoggedIn): ?>
                <li>
                    <a href="login.php" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-sign-in-alt mr-3"></i>
                        <span>เข้าสู่ระบบ</span>
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="logout.php" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </li>
            <?php endif; ?>
            <!-- เมนูเกี่ยวกับอยู่ล่างสุด -->
            <li>
                <a href="index.php?page=about" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors duration-200 <?= ($page == 'about') ? 'bg-blue-600 text-white' : ''; ?>">
                    <i class="fas fa-info-circle mr-3"></i>
                    <span>เกี่ยวกับ</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- Sidebar overlay for mobile -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" 
     class="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
</div>
