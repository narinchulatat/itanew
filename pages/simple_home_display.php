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
    ob_end_clean();
    header('Location: login.php');
    exit();
}

// ดึงปีทั้งหมด
$years = $pdo->query('SELECT id, year FROM years ORDER BY year DESC')->fetchAll(PDO::FETCH_ASSOC);
$yearMap = [];
foreach ($years as $y) {
    $yearMap[$y['id']] = $y['year'];
}

// เพิ่ม/แก้ไข/ลบ config - Simplified logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_end_clean();
    
    $action = $_POST['action'] ?? '';
    $year = intval($_POST['year'] ?? 0);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $active_quarter = intval($_POST['active_quarter'] ?? 1);
    $id = intval($_POST['id'] ?? 0);
    
    try {
        // เริ่ม transaction
        $pdo->beginTransaction();
        
        if ($action === 'add') {
            // Validation
            if ($year <= 0) {
                throw new Exception('กรุณาเลือกปีที่จะแสดง');
            }
            
            if ($active_quarter < 1 || $active_quarter > 4) {
                throw new Exception('ไตรมาสต้องอยู่ระหว่าง 1-4');
            }
            
            // ตรวจสอบว่ามีการตั้งค่าสำหรับปีนี้อยู่แล้วหรือไม่
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM home_display_config WHERE year = ?');
            $checkStmt->execute([$year]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception('มีการตั้งค่าสำหรับปีนี้อยู่แล้ว');
            }
            
            // ถ้าตั้งเป็น default ให้ยกเลิก default อื่น ๆ
            if ($is_default) {
                $pdo->prepare('UPDATE home_display_config SET is_default = 0')->execute();
            }
            
            // หา default_year จาก year map
            $yearStmt = $pdo->prepare('SELECT year FROM years WHERE id = ?');
            $yearStmt->execute([$year]);
            $yearRow = $yearStmt->fetch();
            $default_year = $yearRow ? $yearRow['year'] : null;
            
            // ตั้งค่าให้ compatibility fields ตามตรรกะใหม่
            $quarter = $active_quarter;
            $source_year = $year;
            $source_quarter = $active_quarter;
            $default_quarter = $active_quarter;
            
            $stmt = $pdo->prepare('INSERT INTO home_display_config (year, quarter, source_year, source_quarter, is_default, default_year, default_quarter, active_quarter) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$year, $quarter, $source_year, $source_quarter, $is_default, $default_year, $default_quarter, $active_quarter]);
            
        } elseif ($action === 'edit' && $id) {
            // Validation
            if ($year <= 0) {
                throw new Exception('กรุณาเลือกปีที่จะแสดง');
            }
            
            if ($active_quarter < 1 || $active_quarter > 4) {
                throw new Exception('ไตรมาสต้องอยู่ระหว่าง 1-4');
            }
            
            // ตรวจสอบว่ามีการตั้งค่าสำหรับปีนี้อยู่แล้วหรือไม่ (ยกเว้นรายการที่กำลังแก้ไข)
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM home_display_config WHERE year = ? AND id != ?');
            $checkStmt->execute([$year, $id]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception('มีการตั้งค่าสำหรับปีนี้อยู่แล้ว');
            }
            
            // ถ้าตั้งเป็น default ให้ยกเลิก default อื่น ๆ
            if ($is_default) {
                $pdo->prepare('UPDATE home_display_config SET is_default = 0 WHERE id != ?')->execute([$id]);
            }
            
            // หา default_year จาก year map
            $yearStmt = $pdo->prepare('SELECT year FROM years WHERE id = ?');
            $yearStmt->execute([$year]);
            $yearRow = $yearStmt->fetch();
            $default_year = $yearRow ? $yearRow['year'] : null;
            
            // ตั้งค่าให้ compatibility fields ตามตรรกะใหม่
            $quarter = $active_quarter;
            $source_year = $year;
            $source_quarter = $active_quarter;
            $default_quarter = $active_quarter;
            
            $stmt = $pdo->prepare('UPDATE home_display_config SET year=?, quarter=?, source_year=?, source_quarter=?, is_default=?, default_year=?, default_quarter=?, active_quarter=? WHERE id=?');
            $stmt->execute([$year, $quarter, $source_year, $source_quarter, $is_default, $default_year, $default_quarter, $active_quarter, $id]);
            
        } elseif ($action === 'delete' && $id) {
            if ($id <= 0) {
                throw new Exception('ข้อมูลไม่ถูกต้อง');
            }
            
            $stmt = $pdo->prepare('DELETE FROM home_display_config WHERE id=?');
            $stmt->execute([$id]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        header('Location: index.php?page=simple_home_display&success=1');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollback();
        if (strpos($e->getMessage(), 'มีการตั้งค่า') !== false) {
            header('Location: index.php?page=simple_home_display&error=duplicate');
        } else {
            header('Location: index.php?page=simple_home_display&error=database');
        }
        exit();
    }
}

// ดึง config ทั้งหมด
$configs = $pdo->query('SELECT * FROM home_display_config ORDER BY year DESC')->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าการแสดงผลหน้าแรก - แบบง่าย</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .gradient-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .form-container { max-width: 400px; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <div class="gradient-header shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                        <i class="fas fa-cog text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">ตั้งค่าการแสดงผลหน้าแรก</h1>
                        <p class="text-white/80 text-sm">จัดการการแสดงผลข้อมูลตามปีและไตรมาส (แบบง่าย)</p>
                    </div>
                </div>
                <button onclick="openAddModal()" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>เพิ่มการตั้งค่าใหม่</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">จำนวนการตั้งค่า</p>
                        <p class="text-3xl font-bold text-blue-600"><?= count($configs) ?></p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-list text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">ปีที่มีการตั้งค่า</p>
                        <p class="text-3xl font-bold text-green-600"><?= count(array_unique(array_column($configs, 'year'))) ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-calendar-alt text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Table -->
        <div class="bg-white rounded-lg card-shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">รายการการตั้งค่าปัจจุบัน</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ปีที่จะแสดง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ไตรมาสที่จะเปิด</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ค่าเริ่มต้น</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่อัพเดต</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($configs)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg font-medium">ยังไม่มีการตั้งค่า</p>
                                        <p class="text-sm text-gray-400 mt-2">คลิก "เพิ่มการตั้งค่าใหม่" เพื่อเริ่มต้น</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($configs as $config): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars($yearMap[$config['year']] ?? 'ไม่ระบุ') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            ไตรมาส <?= htmlspecialchars($config['active_quarter'] ?? 1) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if (isset($config['is_default']) && $config['is_default']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-star mr-1"></i>ค่าเริ่มต้น
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                ธรรมดา
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($config['updated_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="editConfig(<?= $config['id'] ?>, <?= $config['year'] ?>, <?= isset($config['is_default']) && $config['is_default'] ? 'true' : 'false' ?>, <?= $config['active_quarter'] ?? 1 ?>)" 
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </button>
                                        <button onclick="deleteConfig(<?= $config['id'] ?>)" 
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i> ลบ
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Usage Guide -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-3">
                <i class="fas fa-info-circle mr-2"></i>วิธีการใช้งาน
            </h3>
            <div class="text-sm text-blue-800 space-y-2">
                <p><strong>ปีที่จะแสดง:</strong> ปีที่จะแสดงในหน้า Home และเป็นปีของข้อมูลที่จะใช้</p>
                <p><strong>ตั้งเป็นค่าเริ่มต้น:</strong> การตั้งค่านี้เป็นค่าเริ่มต้นเมื่อเข้าหน้าแรก</p>
                <p><strong>ไตรมาสที่จะเปิด:</strong> tab ไตรมาสที่จะ active เมื่อเข้าหน้าแรก</p>
                <p class="text-blue-600 font-medium">ระบบจะตรวจสอบความซ้ำซ้อนโดยอัตโนมัติ (หนึ่งปีต่อหนึ่งการตั้งค่า)</p>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md form-container">
            <form id="addForm" method="post">
                <div class="bg-blue-500 text-white px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold">แก้ไขการตั้งค่า</h3>
                </div>
                <div class="p-8 space-y-6">
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">ปีที่จะแสดง:</label>
                        <select name="year" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base" required>
                            <option value="">เลือกปี</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year['id'] ?>"><?= $year['year'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" name="is_default" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">ตั้งเป็นค่าเริ่มต้น</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">ไตรมาสที่จะเปิด:</label>
                        <select name="active_quarter" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base" required>
                            <option value="1">ไตรมาส 1</option>
                            <option value="2">ไตรมาส 2</option>
                            <option value="3" selected>ไตรมาส 3</option>
                            <option value="4">ไตรมาส 4</option>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end space-x-3">
                    <button type="button" onclick="closeAddModal()" class="px-6 py-2 text-gray-600 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-8 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md form-container">
            <form id="editForm" method="post">
                <div class="bg-yellow-500 text-white px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold">แก้ไขการตั้งค่า</h3>
                </div>
                <div class="p-8 space-y-6">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">ปีที่จะแสดง:</label>
                        <select name="year" id="edit_year" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-yellow-500 text-base" required>
                            <option value="">เลือกปี</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year['id'] ?>"><?= $year['year'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" name="is_default" id="edit_is_default" class="w-5 h-5 text-yellow-600 rounded border-gray-300 focus:ring-yellow-500">
                            <span class="text-sm font-medium text-gray-700">ตั้งเป็นค่าเริ่มต้น</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">ไตรมาสที่จะเปิด:</label>
                        <select name="active_quarter" id="edit_active_quarter" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-yellow-500 text-base" required>
                            <option value="1">ไตรมาส 1</option>
                            <option value="2">ไตรมาส 2</option>
                            <option value="3">ไตรมาส 3</option>
                            <option value="4">ไตรมาส 4</option>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-6 py-2 text-gray-600 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-8 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Handle success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('success') === '1') {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: 'บันทึกการตั้งค่าเรียบร้อยแล้ว',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname + '?page=simple_home_display');
            }
            
            if (urlParams.get('error') === 'duplicate') {
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: 'มีการตั้งค่าสำหรับปีนี้อยู่แล้ว',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname + '?page=simple_home_display');
            }
            
            if (urlParams.get('error') === 'database') {
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: 'เกิดข้อผิดพลาดในการดำเนินการ กรุณาลองใหม่อีกครั้ง',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname + '?page=simple_home_display');
            }
        });

        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addForm').reset();
        }

        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editForm').reset();
        }

        function editConfig(id, year, is_default, active_quarter) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_year').value = year;
            document.getElementById('edit_is_default').checked = is_default === 'true';
            document.getElementById('edit_active_quarter').value = active_quarter || 1;
            openEditModal();
        }

        function deleteConfig(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: 'คุณต้องการลบการตั้งค่านี้ใช่หรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ใช่, ลบเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Form validation
        document.getElementById('addForm').addEventListener('submit', function(e) {
            const year = this.querySelector('[name="year"]').value;
            const active_quarter = this.querySelector('[name="active_quarter"]').value;
            
            if (!year || !active_quarter) {
                e.preventDefault();
                Swal.fire({
                    title: 'ข้อมูลไม่ครบถ้วน',
                    text: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                    icon: 'warning',
                    confirmButtonText: 'ตกลง'
                });
                return false;
            }
        });

        document.getElementById('editForm').addEventListener('submit', function(e) {
            const year = this.querySelector('[name="year"]').value;
            const active_quarter = this.querySelector('[name="active_quarter"]').value;
            
            if (!year || !active_quarter) {
                e.preventDefault();
                Swal.fire({
                    title: 'ข้อมูลไม่ครบถ้วน',
                    text: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                    icon: 'warning',
                    confirmButtonText: 'ตกลง'
                });
                return false;
            }
        });

        // Close modal when clicking outside
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddModal();
                closeEditModal();
            }
        });
    </script>
</body>
</html>