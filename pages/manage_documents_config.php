<?php
session_start();
include('./db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role_id'];

// Get all years for dropdown
$years_stmt = $pdo->query("SELECT id, year FROM years ORDER BY year DESC");
$years = $years_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for dropdown
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all subcategories for dropdown
$subcategories_stmt = $pdo->query("SELECT * FROM subcategories ORDER BY name");
$subcategories = $subcategories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's current configuration
$user_config_stmt = $pdo->prepare("SELECT * FROM manage_documents_config WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
$user_config_stmt->execute([$user_id]);
$user_config = $user_config_stmt->fetch(PDO::FETCH_ASSOC);

// Set default values from user config
$config_year = $user_config ? $user_config['year'] : '';
$config_quarter = $user_config ? $user_config['quarter'] : '';
$config_main_category = $user_config ? $user_config['main_category_id'] : '';
$config_sub_category = $user_config ? $user_config['sub_category_id'] : '';
$config_is_active = $user_config ? $user_config['is_active'] : 1;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าจัดการเอกสาร</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f6f8fa; }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .config-card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: white;
            font-size: 1rem;
        }
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        .btn-success {
            background-color: #10b981;
            color: white;
        }
        .btn-success:hover {
            background-color: #059669;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-inactive {
            background-color: #fef2f2;
            color: #991b1b;
        }
        .grid {
            display: grid;
            gap: 1.5rem;
        }
        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        @media (max-width: 768px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-cog mr-2 text-blue-600"></i>
                ตั้งค่าจัดการเอกสาร
            </h1>
            <a href="index.php?page=manage_documents" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>

        <!-- Current Configuration Display -->
        <?php if ($user_config): ?>
        <div class="config-card">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                การตั้งค่าปัจจุบัน
            </h3>
            <div class="grid grid-cols-2">
                <div>
                    <span class="form-label">ปี:</span>
                    <span class="text-gray-700">
                        <?php 
                        $year_stmt = $pdo->prepare("SELECT year FROM years WHERE id = ?");
                        $year_stmt->execute([$config_year]);
                        $year_result = $year_stmt->fetch();
                        echo $year_result ? $year_result['year'] : 'ไม่ระบุ';
                        ?>
                    </span>
                </div>
                <div>
                    <span class="form-label">ไตรมาส:</span>
                    <span class="text-gray-700">ไตรมาส <?= $config_quarter ?: 'ไม่ระบุ' ?></span>
                </div>
                <div>
                    <span class="form-label">หมวดหมู่หลัก:</span>
                    <span class="text-gray-700">
                        <?php 
                        if ($config_main_category) {
                            $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                            $cat_stmt->execute([$config_main_category]);
                            $cat_result = $cat_stmt->fetch();
                            echo $cat_result ? $cat_result['name'] : 'ไม่ระบุ';
                        } else {
                            echo 'ไม่ระบุ';
                        }
                        ?>
                    </span>
                </div>
                <div>
                    <span class="form-label">หมวดหมู่ย่อย:</span>
                    <span class="text-gray-700">
                        <?php 
                        if ($config_sub_category) {
                            $subcat_stmt = $pdo->prepare("SELECT name FROM subcategories WHERE id = ?");
                            $subcat_stmt->execute([$config_sub_category]);
                            $subcat_result = $subcat_stmt->fetch();
                            echo $subcat_result ? $subcat_result['name'] : 'ไม่ระบุ';
                        } else {
                            echo 'ไม่ระบุ';
                        }
                        ?>
                    </span>
                </div>
                <div>
                    <span class="form-label">สถานะ:</span>
                    <span class="status-badge <?= $config_is_active ? 'status-active' : 'status-inactive' ?>">
                        <?= $config_is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?>
                    </span>
                </div>
                <div>
                    <span class="form-label">อัปเดตล่าสุด:</span>
                    <span class="text-gray-700">
                        <?= $user_config ? date('d/m/Y H:i', strtotime($user_config['updated_at'])) : 'ไม่ระบุ' ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Configuration Form -->
        <div class="config-card">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-edit mr-2 text-green-600"></i>
                แก้ไขการตั้งค่า
            </h3>
            <form id="configForm">
                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label class="form-label" for="year">ปี <span class="text-red-500">*</span></label>
                        <select class="form-select" id="year" name="year" required>
                            <option value="">เลือกปี</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year['id'] ?>" <?= $config_year == $year['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['year']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="quarter">ไตรมาส <span class="text-red-500">*</span></label>
                        <select class="form-select" id="quarter" name="quarter" required>
                            <option value="">เลือกไตรมาส</option>
                            <option value="1" <?= $config_quarter == 1 ? 'selected' : '' ?>>ไตรมาส 1</option>
                            <option value="2" <?= $config_quarter == 2 ? 'selected' : '' ?>>ไตรมาส 2</option>
                            <option value="3" <?= $config_quarter == 3 ? 'selected' : '' ?>>ไตรมาส 3</option>
                            <option value="4" <?= $config_quarter == 4 ? 'selected' : '' ?>>ไตรมาส 4</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label class="form-label" for="main_category_id">หมวดหมู่หลัก</label>
                        <select class="form-select" id="main_category_id" name="main_category_id">
                            <option value="">เลือกหมวดหมู่หลัก (ไม่บังคับ)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $config_main_category == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="sub_category_id">หมวดหมู่ย่อย</label>
                        <select class="form-select" id="sub_category_id" name="sub_category_id">
                            <option value="">เลือกหมวดหมู่ย่อย (ไม่บังคับ)</option>
                            <?php foreach ($subcategories as $subcategory): ?>
                                <option value="<?= $subcategory['id'] ?>" <?= $config_sub_category == $subcategory['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subcategory['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="is_active">สถานะการใช้งาน</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="1" <?= $config_is_active == 1 ? 'selected' : '' ?>>เปิดใช้งาน</option>
                        <option value="0" <?= $config_is_active == 0 ? 'selected' : '' ?>>ปิดใช้งาน</option>
                    </select>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        <i class="fas fa-undo mr-2"></i>รีเซ็ต
                    </button>
                    <?php if ($user_config): ?>
                    <button type="button" onclick="deleteConfig()" class="btn" style="background-color: #dc2626; color: white;">
                        <i class="fas fa-trash mr-2"></i>ลบการตั้งค่า
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Handle form submission
            $('#configForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'save_full_config',
                    year: $('#year').val(),
                    quarter: $('#quarter').val(),
                    main_category_id: $('#main_category_id').val() || null,
                    sub_category_id: $('#sub_category_id').val() || null,
                    is_active: $('#is_active').val()
                };

                // Validate required fields
                if (!formData.year || !formData.quarter) {
                    Swal.fire({
                        title: 'ข้อมูลไม่ครบถ้วน',
                        text: 'กรุณาเลือกปีและไตรมาส',
                        icon: 'warning',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                $.post('ajax/manage_documents_config.php', formData)
                    .done(function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: 'สำเร็จ!',
                                    text: 'บันทึกการตั้งค่าเรียบร้อยแล้ว',
                                    icon: 'success',
                                    confirmButtonText: 'ตกลง'
                                }).then(function() {
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.error || 'Unknown error');
                            }
                        } catch (e) {
                            Swal.fire({
                                title: 'ผิดพลาด!',
                                text: 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + e.message,
                                icon: 'error',
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    })
                    .fail(function() {
                        Swal.fire({
                            title: 'ผิดพลาด!',
                            text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                            icon: 'error',
                            confirmButtonText: 'ตกลง'
                        });
                    });
            });

            // Load subcategories when main category changes
            $('#main_category_id').on('change', function() {
                const categoryId = $(this).val();
                
                $('#sub_category_id').empty().append('<option value="">กำลังโหลด...</option>');
                
                if (categoryId) {
                    $.post('ajax/manage_documents_config.php', {
                        action: 'get_subcategories',
                        category_id: categoryId
                    })
                    .done(function(response) {
                        try {
                            const data = JSON.parse(response);
                            $('#sub_category_id').empty().append('<option value="">เลือกหมวดหมู่ย่อย (ไม่บังคับ)</option>');
                            
                            data.subcategories.forEach(function(subcategory) {
                                $('#sub_category_id').append(
                                    '<option value="' + subcategory.id + '">' + 
                                    subcategory.name + '</option>'
                                );
                            });
                        } catch (e) {
                            console.error('Error parsing subcategories response:', e);
                            $('#sub_category_id').empty().append('<option value="">เกิดข้อผิดพลาด</option>');
                        }
                    })
                    .fail(function() {
                        $('#sub_category_id').empty().append('<option value="">เกิดข้อผิดพลาด</option>');
                    });
                } else {
                    $('#sub_category_id').empty().append('<option value="">เลือกหมวดหมู่ย่อย (ไม่บังคับ)</option>');
                }
            });
        });

        function resetForm() {
            $('#configForm')[0].reset();
            $('#sub_category_id').empty().append('<option value="">เลือกหมวดหมู่ย่อย (ไม่บังคับ)</option>');
        }

        function deleteConfig() {
            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: 'คุณต้องการลบการตั้งค่าทั้งหมดหรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('ajax/manage_documents_config.php', {
                        action: 'delete_config'
                    })
                    .done(function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: 'สำเร็จ!',
                                    text: 'ลบการตั้งค่าเรียบร้อยแล้ว',
                                    icon: 'success',
                                    confirmButtonText: 'ตกลง'
                                }).then(function() {
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.error || 'Unknown error');
                            }
                        } catch (e) {
                            Swal.fire({
                                title: 'ผิดพลาด!',
                                text: 'เกิดข้อผิดพลาดในการลบข้อมูล: ' + e.message,
                                icon: 'error',
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    })
                    .fail(function() {
                        Swal.fire({
                            title: 'ผิดพลาด!',
                            text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                            icon: 'error',
                            confirmButtonText: 'ตกลง'
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>