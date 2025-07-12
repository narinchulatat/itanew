<?php
// เริ่ม output buffering และ session ก่อนอื่น
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// รวมไฟล์ database
require_once dirname(__DIR__) . '/db.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    ob_end_clean(); // ล้าง output buffer
    header('Location: ../login.php');
    exit();
}

// ดึงปีทั้งหมด
$years = $pdo->query('SELECT id, year FROM years ORDER BY year DESC')->fetchAll(PDO::FETCH_ASSOC);
$yearMap = [];
foreach ($years as $y) {
    $yearMap[$y['id']] = $y['year'];
}

// เพิ่ม/แก้ไข/ลบ config
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ล้าง output buffer ก่อนส่ง header
    ob_end_clean();
    
    $action = $_POST['action'] ?? '';
    $year = intval($_POST['year'] ?? 0);
    $quarter = intval($_POST['quarter'] ?? 0);
    $source_year = intval($_POST['source_year'] ?? 0);
    $source_quarter = intval($_POST['source_quarter'] ?? 0);
    $id = intval($_POST['id'] ?? 0);
    
    try {
        if ($action === 'add') {
            // ตรวจสอบว่ามีการตั้งค่าซ้ำหรือไม่
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM home_display_config WHERE year = ? AND quarter = ?');
            $checkStmt->execute([$year, $quarter]);
            if ($checkStmt->fetchColumn() > 0) {
                header('Location: manage_home_display.php?error=duplicate');
                exit();
            }
            
            $stmt = $pdo->prepare('INSERT INTO home_display_config (year, quarter, source_year, source_quarter) VALUES (?, ?, ?, ?)');
            $stmt->execute([$year, $quarter, $source_year, $source_quarter]);
            
        } elseif ($action === 'edit' && $id) {
            // ตรวจสอบว่ามีการตั้งค่าซ้ำหรือไม่ (ยกเว้นรายการที่กำลังแก้ไข)
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM home_display_config WHERE year = ? AND quarter = ? AND id != ?');
            $checkStmt->execute([$year, $quarter, $id]);
            if ($checkStmt->fetchColumn() > 0) {
                header('Location: manage_home_display.php?error=duplicate');
                exit();
            }
            
            $stmt = $pdo->prepare('UPDATE home_display_config SET year=?, quarter=?, source_year=?, source_quarter=? WHERE id=?');
            $stmt->execute([$year, $quarter, $source_year, $source_quarter, $id]);
            
        } elseif ($action === 'delete' && $id) {
            $stmt = $pdo->prepare('DELETE FROM home_display_config WHERE id=?');
            $stmt->execute([$id]);
        }
        
        header('Location: manage_home_display.php?success=1');
        exit();
        
    } catch (Exception $e) {
        header('Location: manage_home_display.php?error=database');
        exit();
    }
}

// ดึง config ทั้งหมด
$configs = $pdo->query('SELECT * FROM home_display_config ORDER BY year DESC, quarter ASC')->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มตามปี
$configsByYear = [];
foreach ($configs as $cfg) {
    $configsByYear[$cfg['year']][$cfg['quarter']] = $cfg;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าการแสดงผลหน้าแรก</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        .quarter-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .quarter-card { aspect-ratio: 1; }
        .select2-container--default .select2-selection--single { height: 42px; line-height: 42px; border-radius: 0.5rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { padding-left: 12px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">ตั้งค่าการแสดงผลหน้าแรก</h1>
                        <p class="text-white/80 text-sm">จัดการการแสดงผลข้อมูลตามปี และไตรมาส</p>
                    </div>
                </div>
                <button onclick="openModalAdd()" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>เพิ่มการตั้งค่า</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">ปีที่มีการตั้งค่า</p>
                        <p class="text-2xl font-bold text-indigo-600"><?= count($configsByYear) ?></p>
                    </div>
                    <div class="bg-indigo-100 rounded-full p-3">
                        <i class="fas fa-calendar-alt text-indigo-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">ไตรมาสที่ตั้งค่าแล้ว</p>
                        <p class="text-2xl font-bold text-green-600"><?= count($configs) ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-chart-bar text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">ปีล่าสุด</p>
                        <p class="text-2xl font-bold text-purple-600"><?= !empty($years) ? $years[0]['year'] : '-' ?></p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <i class="fas fa-clock text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Year Cards -->
        <div class="space-y-6">
            <?php foreach ($years as $year): ?>
                <div class="bg-white rounded-xl card-shadow overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-calendar-alt mr-3"></i>
                            ปี <?= htmlspecialchars($year['year']) ?>
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="quarter-grid">
                            <?php for ($q = 1; $q <= 4; $q++): ?>
                                <?php $config = $configsByYear[$year['id']][$q] ?? null; ?>
                                <div class="quarter-card bg-gray-50 rounded-lg p-4 border-2 border-dashed <?= $config ? 'border-green-300 bg-green-50' : 'border-gray-300' ?> hover:border-indigo-400 transition-all duration-200">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold <?= $config ? 'text-green-600' : 'text-gray-400' ?> mb-2">
                                            Q<?= $q ?>
                                        </div>
                                        <?php if ($config): ?>
                                            <div class="text-sm text-gray-600 mb-3">
                                                <div class="flex items-center justify-center space-x-1 mb-1">
                                                    <i class="fas fa-database text-xs"></i>
                                                    <span>ปี <?= htmlspecialchars($yearMap[$config['source_year']] ?? '-') ?></span>
                                                </div>
                                                <div class="flex items-center justify-center space-x-1">
                                                    <i class="fas fa-chart-pie text-xs"></i>
                                                    <span>Q<?= htmlspecialchars($config['source_quarter']) ?></span>
                                                </div>
                                            </div>
                                            <div class="flex justify-center space-x-1">
                                                <button onclick="editConfig(<?= $config['id'] ?>, <?= $config['year'] ?>, <?= $config['quarter'] ?>, <?= $config['source_year'] ?>, <?= $config['source_quarter'] ?>)" 
                                                        class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-md text-xs transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteConfig(<?= $config['id'] ?>)" 
                                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-xs transition-colors">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-gray-500 text-sm mb-3">ยังไม่มีการตั้งค่า</p>
                                            <button onclick="quickAdd(<?= $year['id'] ?>, <?= $q ?>)" 
                                                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1 rounded-md text-xs transition-colors">
                                                <i class="fas fa-plus mr-1"></i>เพิ่ม
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Add -->
    <div id="modalAdd" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4" style="display:none;">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95">
            <form method="post" id="addForm">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-2xl px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">เพิ่มการตั้งค่าใหม่</h3>
                    <button type="button" onclick="closeModalAdd()" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ปีที่จะแสดง</label>
                            <select name="year" id="add_year" class="select2-add w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">เลือกปี</option>
                                <?php foreach ($years as $y): ?>
                                <option value="<?= $y['id'] ?>"><?= $y['year'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ไตรมาสที่จะแสดง</label>
                            <select name="quarter" id="add_quarter" class="select2-add w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">เลือกไตรมาส</option>
                                <option value="1">ไตรมาส 1</option>
                                <option value="2">ไตรมาส 2</option>
                                <option value="3">ไตรมาส 3</option>
                                <option value="4">ไตรมาส 4</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-database mr-2 text-indigo-600"></i>
                            ข้อมูลที่จะนำมาแสดง
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">จากปี</label>
                                <select name="source_year" id="add_source_year" class="select2-add w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="">เลือกปี</option>
                                    <?php foreach ($years as $y): ?>
                                    <option value="<?= $y['id'] ?>"><?= $y['year'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">จากไตรมาส</label>
                                <select name="source_quarter" id="add_source_quarter" class="select2-add w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="">เลือกไตรมาส</option>
                                    <option value="1">ไตรมาส 1</option>
                                    <option value="2">ไตรมาส 2</option>
                                    <option value="3">ไตรมาส 3</option>
                                    <option value="4">ไตรมาส 4</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 rounded-b-2xl">
                    <button type="button" onclick="closeModalAdd()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="modalEdit" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4" style="display:none;">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95">
            <form method="post" id="editForm">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-t-2xl px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">แก้ไขการตั้งค่า</h3>
                    <button type="button" onclick="closeModalEdit()" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ปีที่จะแสดง</label>
                            <select name="year" id="edit_year" class="select2-edit w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" required>
                                <option value="">เลือกปี</option>
                                <?php foreach ($years as $y): ?>
                                <option value="<?= $y['id'] ?>"><?= $y['year'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ไตรมาสที่จะแสดง</label>
                            <select name="quarter" id="edit_quarter" class="select2-edit w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" required>
                                <option value="">เลือกไตรมาส</option>
                                <option value="1">ไตรมาส 1</option>
                                <option value="2">ไตรมาส 2</option>
                                <option value="3">ไตรมาส 3</option>
                                <option value="4">ไตรมาส 4</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-database mr-2 text-amber-600"></i>
                            ข้อมูลที่จะนำมาแสดง
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">จากปี</label>
                                <select name="source_year" id="edit_source_year" class="select2-edit w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" required>
                                    <option value="">เลือกปี</option>
                                    <?php foreach ($years as $y): ?>
                                    <option value="<?= $y['id'] ?>"><?= $y['year'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">จากไตรมาส</label>
                                <select name="source_quarter" id="edit_source_quarter" class="select2-edit w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" required>
                                    <option value="">เลือกไตรมาส</option>
                                    <option value="1">ไตรมาส 1</option>
                                    <option value="2">ไตรมาส 2</option>
                                    <option value="3">ไตรมาส 3</option>
                                    <option value="4">ไตรมาส 4</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 rounded-b-2xl">
                    <button type="button" onclick="closeModalEdit()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2-add').select2({
            dropdownParent: $('#modalAdd'),
            placeholder: 'เลือก...',
            allowClear: true
        });
        
        $('.select2-edit').select2({
            dropdownParent: $('#modalEdit'),
            placeholder: 'เลือก...',
            allowClear: true
        });
        
        // Handle success/error messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            Swal.fire({
                title: 'สำเร็จ!',
                text: 'บันทึกการตั้งค่าเรียบร้อยแล้ว',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        if (urlParams.get('error') === 'duplicate') {
            Swal.fire({
                title: 'ผิดพลาด!',
                text: 'มีการตั้งค่าสำหรับปีและไตรมาสนี้อยู่แล้ว',
                icon: 'error',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        if (urlParams.get('error') === 'database') {
            Swal.fire({
                title: 'ผิดพลาด!',
                text: 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล',
                icon: 'error',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

    function openModalAdd() {
        $('#modalAdd').removeClass('scale-95').addClass('scale-100').show();
    }

    function closeModalAdd() {
        $('#modalAdd').removeClass('scale-100').addClass('scale-95');
        setTimeout(() => $('#modalAdd').hide(), 200);
        // Reset form
        $('#addForm')[0].reset();
        $('.select2-add').val(null).trigger('change');
    }

    function openModalEdit() {
        $('#modalEdit').removeClass('scale-95').addClass('scale-100').show();
    }

    function closeModalEdit() {
        $('#modalEdit').removeClass('scale-100').addClass('scale-95');
        setTimeout(() => $('#modalEdit').hide(), 200);
        // Reset form
        $('#editForm')[0].reset();
        $('.select2-edit').val(null).trigger('change');
    }

    function quickAdd(yearId, quarter) {
        $('#add_year').val(yearId).trigger('change');
        $('#add_quarter').val(quarter).trigger('change');
        $('#add_source_year').val(yearId).trigger('change');
        $('#add_source_quarter').val(quarter).trigger('change');
        openModalAdd();
    }

    function editConfig(id, year, quarter, source_year, source_quarter) {
        $('#edit_id').val(id);
        $('#edit_year').val(year).trigger('change');
        $('#edit_quarter').val(quarter).trigger('change');
        $('#edit_source_year').val(source_year).trigger('change');
        $('#edit_source_quarter').val(source_quarter).trigger('change');
        openModalEdit();
    }

    function deleteConfig(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: 'คุณต้องการลบการตั้งค่านี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-trash mr-2"></i>ลบ',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Create and submit delete form
                const form = $('<form method="post" style="display:none;">' +
                    '<input type="hidden" name="action" value="delete">' +
                    '<input type="hidden" name="id" value="' + id + '">' +
                    '</form>');
                $('body').append(form);
                form.submit();
            }
        });
    }

    // Modal click outside to close
    $(document).click(function(e) {
        if ($(e.target).is('#modalAdd')) {
            closeModalAdd();
        }
        if ($(e.target).is('#modalEdit')) {
            closeModalEdit();
        }
    });

    // ESC key to close modals
    $(document).keyup(function(e) {
        if (e.keyCode === 27) {
            closeModalAdd();
            closeModalEdit();
        }
    });
    </script>
</body>
</html>