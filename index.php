<?php
// Start output buffering to prevent header warnings
ob_start();

$page = $_GET['page'] ?? 'home';

// Handle redirects before any HTML output
if ($page === 'home') {
    // Include database connection for home page redirect logic
    include './db.php';
    
    // กำหนดค่าเริ่มต้น
    $defaultYear = 2568;
    $defaultQuarter = 3;
    
    // ดึงการตั้งค่าเริ่มต้นจากฐานข้อมูล
    $stmt = $pdo->prepare("SELECT default_year, default_quarter FROM home_display_config WHERE is_default = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $defaultConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($defaultConfig) {
        $defaultYear = $defaultConfig['default_year'];
        $defaultQuarter = $defaultConfig['default_quarter'];
    }
    
    // ตรวจสอบการมีอยู่ของ year และ quarter ใน URL
    $hasYear = isset($_GET['year']) && !empty($_GET['year']);
    $hasQuarter = isset($_GET['quarter']) && !empty($_GET['quarter']);
    
    // ถ้าไม่มี year หรือ quarter ใน URL ให้ redirect ไปยัง URL ที่ถูกต้อง
    if (!$hasYear || !$hasQuarter) {
        $currentYear = $hasYear ? intval($_GET['year']) : $defaultYear;
        $currentQuarter = $hasQuarter ? intval($_GET['quarter']) : $defaultQuarter;
        
        $redirectUrl = "index.php?page=home&year={$currentYear}&quarter={$currentQuarter}";
        
        // เพิ่ม query parameters อื่นๆ ที่อาจมี
        $additionalParams = [];
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['page', 'year', 'quarter']) && !empty($value)) {
                $additionalParams[] = urlencode($key) . '=' . urlencode($value);
            }
        }
        
        if (!empty($additionalParams)) {
            $redirectUrl .= '&' . implode('&', $additionalParams);
        }
        
        // Clean output buffer and redirect
        ob_end_clean();
        header("Location: $redirectUrl");
        exit;
    }
}

// ตรวจสอบ session/role admin ก่อน include sidebar/page admin
$adminPages = ['manage_home_display','manage_years','manage_categories','manage_documents','manage_documents_config','manage_subcategories','manage_users','approve_documents'];
if (in_array($page, $adminPages)) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // เฉพาะ manage_documents และ manage_documents_config ให้ role_id == 1 หรือ 3 เข้าได้
    if (in_array($page, ['manage_documents', 'manage_documents_config'])) {
        if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], ['1','3'])) {
            ob_end_clean();
            header('Location: login.php');
            exit();
        }
    } 
    // หน้า approve_documents ให้ role_id == 1 หรือ 2 เข้าได้
    else if ($page === 'approve_documents') {
        if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], ['1','2'])) {
            ob_end_clean();
            header('Location: login.php');
            exit();
        }
    }
    // หน้า admin อื่น ๆ ให้เฉพาะ role_id == 1 เข้าได้
    else {
        if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != '1') {
            ob_end_clean();
            header('Location: login.php');
            exit();
        }
    }
}
// หน้าโปรไฟล์ให้เข้าได้ทุกคนที่ login แล้ว
if ($page === 'manage_profiles') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดเก็บเอกสาร (Integrity and Transparency Assessment: ITA) โรงพยาบาลน้ำยืน จังหวัดอุบลราชธานี (ปีงบประมาณ 2568)</title>

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

    <!-- jQuery 3 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.tailwindcss.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Custom Style -->
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 font-sarabun">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <?php include 'header.php'; ?>
            
            <!-- Content Wrapper -->
            <div class="flex-1 p-6">
                <!-- Content Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">
                        ระบบจัดเก็บเอกสาร (Integrity and Transparency Assessment: ITA) โรงพยาบาลน้ำยืน จังหวัดอุบลราชธานี (ปีงบประมาณ 2568)
                    </h1>
                </div>

                <!-- Main content -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <?php
                    $file = 'pages/' . $page . '.php';
                    if (file_exists($file)) {
                        include $file;
                    } else {
                        echo '<div class="text-center py-12">';
                        echo '<h1 class="text-4xl font-bold text-gray-400">404</h1>';
                        echo '<p class="text-gray-600">ไม่พบหน้าที่คุณกำลังมองหา</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 p-4">
                <div class="flex justify-between items-center text-sm text-gray-600">
                    <div>
                        <strong>Copyright &copy; 2024 นายนรินทร์ จุลทัศน์ ตําแหน่งนักวิชาการคอมพิวเตอร์ปฏิบัติการ โรงพยาบาลน้ำยืน Version 1.1</strong>
                    </div>
                    <div class="hidden md:block">
                        ITA โรงพยาบาลน้ำยืน
                    </div>
                </div>
            </footer>
        </div>
    </div>
</body>

</html>
<?php
// Flush output buffer if still active
if (ob_get_level()) {
    ob_end_flush();
}
?>