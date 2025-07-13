<?php
ob_start(); // เริ่มบัฟเฟอร์เอาต์พุต
include './db.php';
session_start();

// ตรวจสอบว่าผู้ใช้เป็น Admin หรือไม่
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: index.php'); // เปลี่ยนเส้นทางไปยังหน้าหลักหากไม่ใช่ Admin
    exit;
}

$message = '';
$action = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_subcategory'])) {
            // เพิ่ม subcategory ใหม่
            $category_id = $_POST['category_id'];
            $name = $_POST['name'];
            $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name) VALUES (?, ?)");
            $stmt->execute([$category_id, $name]);
            $message = 'Subcategory added successfully!';
            $action = 'add';
        } elseif (isset($_POST['edit_subcategory'])) {
            // แก้ไข subcategory
            $id = $_POST['id'];
            $category_id = $_POST['category_id'];
            $name = $_POST['name'];

            // อัปเดต subcategory
            $stmt = $pdo->prepare("UPDATE subcategories SET category_id = ?, name = ? WHERE id = ?");
            $stmt->execute([$category_id, $name, $id]);
            $message = 'Subcategory updated successfully!';
            $action = 'edit';
        } elseif (isset($_POST['delete_subcategory'])) {
            // ลบ subcategory
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Subcategory deleted successfully!';
            $action = 'delete';
        }
    } catch (PDOException $e) {
        // ตรวจสอบข้อผิดพลาด SQLSTATE เพื่อระบุปัญหาเกี่ยวกับ Foreign Key Constraint
        if ($e->getCode() == '23000') {
            $message = 'Unable to delete subcategory. It is being used by other records.';
            $action = 'error';
        } else {
            throw $e; // ปล่อยข้อผิดพลาดอื่น ๆ ที่ไม่ได้จัดการไป
        }
    }

    // แสดง SweetAlert2 และเปลี่ยนเส้นทางไปยังหน้า index.php?page=manage_subcategories
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '$message',
                icon: '" . ($action === 'error' ? 'error' : 'success') . "',
                timer: 1500,
                showConfirmButton: false
            }).then(function() {
                window.location.href = 'index.php?page=manage_subcategories';
            });
        });
    </script>";
    exit;
}
ob_end_flush(); // ส่งบัฟเฟอร์เอาต์พุตทั้งหมด

// Get all years for dropdown
$years_stmt = $pdo->query("SELECT id, year FROM years ORDER BY year DESC");
$years = $years_stmt->fetchAll(PDO::FETCH_ASSOC);

?>



<style>
/* DataTables Pagination Styling */
.dataTables_wrapper .dataTables_paginate {
    float: right !important;
    text-align: right;
    padding-top: 0.25rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    box-sizing: border-box;
    display: inline-block !important;
    min-width: 1.5rem;
    padding: 0.5rem 1rem !important;
    margin-left: 2px;
    text-align: center;
    text-decoration: none !important;
    cursor: pointer;
    border: 1px solid #d1d5db !important;
    border-radius: 0.375rem !important;
    background: white !important;
    color: #374151 !important;
    transition: all 0.2s ease;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #f3f4f6 !important;
    border-color: #9ca3af !important;
    color: #374151 !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: white !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
    cursor: not-allowed !important;
    opacity: 0.5;
    background: #f9fafb !important;
    color: #9ca3af !important;
}

.dataTables_wrapper .dataTables_info {
    float: left !important;
    padding-top: 0.75rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.dataTables_wrapper .dataTables_length {
    float: left !important;
    margin-bottom: 1rem;
}

.dataTables_wrapper .dataTables_filter {
    float: right !important;
    margin-bottom: 1rem;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #d1d5db !important;
    border-radius: 0.375rem !important;
    padding: 0.5rem !important;
    margin-left: 0.5rem;
}

/* Fix for buttons not showing */
.dataTables_wrapper .dataTables_length select {
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.25rem;
    margin-left: 0.5rem;
    margin-right: 0.5rem;
}

/* Ensure pagination is visible */
.dataTables_wrapper .dataTables_paginate .paginate_button.previous,
.dataTables_wrapper .dataTables_paginate .paginate_button.next {
    display: inline-block !important;
}

/* Responsive table cell ellipsis */
@media (max-width: 640px) {
    #subcategoryTable th, #subcategoryTable td {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        font-size: 0.95rem !important;
    }
    #subcategoryTable td, #subcategoryTable th {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
    }
}
#subcategoryTable td {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    word-break: break-word;
}
@media (max-width: 640px) {
    #subcategoryTable td {
        max-width: 100px;
        font-size: 0.9rem;
    }
}

/* Responsive modal width */
@media (max-width: 640px) {
    #subcategoryModal .max-w-lg {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0 !important;
        padding: 0.5rem !important;
    }
}

/* Enhanced select dropdown styling for better overflow control */
#category_id, #year_id, #quarter {
    position: relative;
    z-index: 10;
    max-width: 100%;
    min-width: 0;
    font-size: 1rem;
    padding: 0.5rem;
    /* Ensure dropdown list has proper height constraints */
    max-height: 300px;
    overflow-y: auto;
}

/* Target the dropdown list container (works in webkit browsers) */
#category_id::-webkit-scrollbar {
    width: 8px;
}

#category_id::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#category_id::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

#category_id::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Options styling */
#category_id option, #year_id option, #quarter option {
    padding: 0.5rem;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

/* Modal specific dropdown enhancements */
#subcategoryModal select {
    position: relative;
    z-index: 1000;
}

/* Ensure dropdown appears above modal content */
#subcategoryModal {
    z-index: 9999;
}

/* Fix for Select2 dropdown positioning within modal */
.select2-container {
    z-index: 10000 !important;
}

.select2-dropdown {
    z-index: 10001 !important;
    max-height: 300px !important;
    overflow-y: auto !important;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    #category_id, #year_id, #quarter {
        font-size: 0.95rem;
        padding: 0.4rem;
        max-height: 200px;
    }
    
    #category_id option, #year_id option, #quarter option {
        font-size: 0.95rem;
        padding: 0.4rem;
    }
    
    .select2-dropdown {
        max-height: 200px !important;
    }
}
</style>

<section class="content-header">
    <h1 class="text-2xl font-bold text-gray-800">จัดการหมวดหมู่ย่อย</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-800">หมวดหมู่ย่อย</h3>
                <button onclick="openModal()" class="bg-green-600 text-white rounded-lg px-6 py-2 hover:bg-green-700 transition duration-200 shadow-md">
                    <i class="fas fa-plus mr-2"></i>เพิ่มหมวดหมู่ย่อย
                </button>
            </div>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table id="subcategoryTable" class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        // ดึงข้อมูล subcategories ทั้งหมดพร้อมกับชื่อ Category ที่สัมพันธ์กัน
                        $stmt = $pdo->prepare("SELECT subcategories.id, subcategories.name, subcategories.category_id, categories.name as category_name FROM subcategories JOIN categories ON subcategories.category_id = categories.id");
                        $stmt->execute();
                        $subcategories = $stmt->fetchAll();
                        foreach ($subcategories as $subcategory) : ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $subcategory['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($subcategory['category_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($subcategory['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button class="bg-blue-600 text-white rounded-md px-3 py-1 hover:bg-blue-700 transition duration-200 text-sm edit-btn"
                                        data-id="<?= $subcategory['id'] ?>"
                                        data-toggle="modal"
                                        data-target="#subcategoryModal">
                                        <i class="fas fa-edit mr-1"></i>แก้ไข
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $subcategory['id'] ?>">
                                        <button type="submit" name="delete_subcategory" class="bg-red-600 text-white rounded-md px-3 py-1 hover:bg-red-700 transition duration-200 text-sm delete-btn" onclick="return confirm('Are you sure you want to delete this subcategory?');">
                                            <i class="fas fa-trash mr-1"></i>ลบ
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal for Adding/Editing Subcategory -->
<div id="subcategoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm transition duration-300 ease-in-out hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl md:max-w-3xl mx-4 border border-gray-100 transform transition-all duration-300 ease-in-out">
        <div class="flex justify-between items-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-t-2xl px-8 py-6">
            <div class="flex items-center space-x-3">
                <div class="bg-white bg-opacity-20 p-2 rounded-lg">
                    <i class="fas fa-folder-plus text-lg"></i>
                </div>
                <h4 class="text-xl font-bold tracking-tight">เพิ่ม/แก้ไขหมวดหมู่ย่อย</h4>
            </div>
            <button type="button" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 transition duration-200" onclick="closeModal()">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="px-8 py-8">
            <form id="subcategoryForm" action="index.php?page=manage_subcategories" method="POST">
                <div class="space-y-6">
                    <input type="hidden" id="subcategoryId" name="id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="year_id" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                                <i class="fas fa-calendar-alt text-blue-500"></i>
                                <span>ปี</span>
                            </label>
                            <select class="w-full h-12 px-4 py-2 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 hover:bg-white transition duration-200 shadow-sm" 
                                    id="year_id" name="year_id" required onchange="loadCategoriesForYearQuarter()">
                                <option value="">เลือกปี</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?= $year['id'] ?>"><?= htmlspecialchars($year['year']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="quarter" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                                <i class="fas fa-calendar-quarter text-green-500"></i>
                                <span>ไตรมาส</span>
                            </label>
                            <select class="w-full h-12 px-4 py-2 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 hover:bg-white transition duration-200 shadow-sm" 
                                    id="quarter" name="quarter" required onchange="loadCategoriesForYearQuarter()">
                                <option value="">เลือกไตรมาส</option>
                                <option value="1">ไตรมาส 1</option>
                                <option value="2">ไตรมาส 2</option>
                                <option value="3">ไตรมาส 3</option>
                                <option value="4">ไตรมาส 4</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="category_id" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                            <i class="fas fa-folder-open text-orange-500"></i>
                            <span>หมวดหมู่หลัก</span>
                        </label>
                        <select class="w-full h-12 px-4 py-2 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 hover:bg-white transition duration-200 shadow-sm" 
                                id="category_id" name="category_id" required>
                            <option value="">เลือกปีและไตรมาสก่อน</option>
                        </select>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                            <i class="fas fa-tag text-purple-500"></i>
                            <span>ชื่อหมวดหมู่ย่อย</span>
                        </label>
                        <textarea id="name" name="name" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 hover:bg-white transition duration-200 resize-none shadow-sm" required placeholder="กรุณากรอกชื่อหมวดหมู่ย่อย..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-100">
                    <button type="button" class="bg-gray-100 text-gray-700 rounded-xl px-6 py-3 font-semibold hover:bg-gray-200 transition duration-200 shadow-sm flex items-center space-x-2" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        <span>ยกเลิก</span>
                    </button>
                    <button type="submit" id="subcategoryFormSubmitButton" name="add_subcategory" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl px-6 py-3 font-semibold hover:from-blue-700 hover:to-indigo-700 transition duration-200 shadow-lg hover:shadow-xl flex items-center space-x-2 transform hover:scale-105">
                        <i class="fas fa-save"></i>
                        <span>เพิ่มหมวดหมู่ย่อย</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('subcategoryModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้า
        
        // Reinitialize Select2 when modal opens
        setTimeout(function() {
            reinitializeSelect2();
        }, 100);
    }
    
    function closeModal() {
        document.getElementById('subcategoryModal').classList.add('hidden');
        document.body.style.overflow = 'auto'; // คืนค่าการเลื่อนหน้า
        resetForm();
    }
    
    function resetForm() {
        document.getElementById('subcategoryForm').reset();
        document.getElementById('subcategoryId').value = '';
        document.getElementById('subcategoryFormSubmitButton').textContent = 'Add Subcategory';
        document.getElementById('subcategoryFormSubmitButton').innerHTML = '<i class="fas fa-save mr-2"></i>Add Subcategory';
        document.getElementById('subcategoryFormSubmitButton').setAttribute('name', 'add_subcategory');
        
        // Clear Select2 selections
        $('#category_id').val('').trigger('change');
        $('#year_id').val('').trigger('change');
        $('#quarter').val('').trigger('change');
        
        clearCategoryDropdown();
        
        // Reinitialize Select2 after clearing
        setTimeout(function() {
            reinitializeSelect2();
        }, 100);
    }
    
    function loadCategoriesForYearQuarter() {
        const yearId = $('#year_id').val();
        const quarter = $('#quarter').val();
        
        // Clear categories
        clearCategoryDropdown();
        
        if (yearId && quarter) {
            return $.post('ajax/get_categories.php', {
                year_id: yearId,
                quarter: quarter
            })
            .done(function(response) {
                const data = JSON.parse(response);
                updateCategoryDropdown(data.categories);
            })
            .fail(function() {
                console.error('Failed to load categories');
            });
        } else {
            return $.Deferred().resolve();
        }
    }
    
    function updateCategoryDropdown(categories) {
        const categorySelect = $('#category_id');
        categorySelect.empty();
        categorySelect.append('<option value="">เลือกหมวดหมู่หลัก</option>');
        
        categories.forEach(function(category) {
            categorySelect.append('<option value="' + category.id + '">' + category.name + '</option>');
        });
        
        categorySelect.trigger('change');
        
        // Reinitialize Select2 after updating options
        setTimeout(function() {
            categorySelect.select2('destroy');
            categorySelect.select2({
                dropdownParent: $('#subcategoryModal'),
                width: '100%',
                placeholder: 'เลือกหมวดหมู่หลัก',
                allowClear: false,
                dropdownCssClass: 'custom-select2-dropdown',
                dropdownAutoWidth: true,
                maximumResultsForSearch: 10,
                escapeMarkup: function(markup) {
                    return markup;
                },
                templateResult: function(option) {
                    if (!option.id) {
                        return option.text;
                    }
                    var text = option.text;
                    if (text.length > 50) {
                        text = text.substring(0, 47) + '...';
                    }
                    return $('<span title="' + option.text + '">' + text + '</span>');
                }
            });
        }, 100);
    }
    
    function clearCategoryDropdown() {
        const categorySelect = $('#category_id');
        categorySelect.empty();
        categorySelect.append('<option value="">เลือกปีและไตรมาสก่อน</option>');
        categorySelect.trigger('change');
        
        // Reinitialize Select2 for cleared dropdown
        setTimeout(function() {
            categorySelect.select2('destroy');
            categorySelect.select2({
                dropdownParent: $('#subcategoryModal'),
                width: '100%',
                placeholder: 'เลือกปีและไตรมาสก่อน',
                allowClear: false,
                dropdownCssClass: 'custom-select2-dropdown',
                dropdownAutoWidth: true,
                maximumResultsForSearch: 10,
                escapeMarkup: function(markup) {
                    return markup;
                }
            });
        }, 100);
    }
    
    function initializeSelect2() {
        // Initialize Select2 for all select elements in the modal
        $('#year_id, #quarter, #category_id').select2({
            dropdownParent: $('#subcategoryModal'),
            width: '100%',
            placeholder: function() {
                return $(this).find('option:first').text();
            },
            allowClear: false,
            dropdownCssClass: 'custom-select2-dropdown',
            // Configure dropdown positioning within modal
            dropdownAutoWidth: true,
            // Set maximum height for dropdown
            maximumResultsForSearch: 10, // Show search box if more than 10 options
            escapeMarkup: function(markup) {
                return markup;
            },
            templateResult: function(option) {
                if (!option.id) {
                    return option.text;
                }
                // Limit text length for better display
                var text = option.text;
                if (text.length > 50) {
                    text = text.substring(0, 47) + '...';
                }
                return $('<span title="' + option.text + '">' + text + '</span>');
            }
        });
        
        // Custom CSS for Select2 dropdown positioning
        $('<style>')
            .prop('type', 'text/css')
            .html(`
                .custom-select2-dropdown {
                    max-height: 300px !important;
                    overflow-y: auto !important;
                    z-index: 10001 !important;
                }
                .select2-container--default .select2-results__option {
                    padding: 8px 12px !important;
                    max-width: 100% !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                    white-space: nowrap !important;
                }
                .select2-container--default .select2-results__option--highlighted {
                    background-color: #3b82f6 !important;
                }
                @media (max-width: 640px) {
                    .custom-select2-dropdown {
                        max-height: 200px !important;
                    }
                    .select2-container--default .select2-results__option {
                        padding: 6px 8px !important;
                        font-size: 0.9rem !important;
                    }
                }
            `)
            .appendTo('head');
    }
    
    function reinitializeSelect2() {
        // Destroy existing Select2 instances
        $('#year_id, #quarter, #category_id').select2('destroy');
        // Reinitialize
        initializeSelect2();
    }

    // ปิด modal เมื่อคลิกที่ backdrop
    document.getElementById('subcategoryModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // ปิด modal เมื่อกด ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('subcategoryModal').classList.contains('hidden')) {
            closeModal();
        }
    });

    $(document).ready(function() {
        // Initialize Select2 for better dropdown control
        initializeSelect2();
        
        var table = $('#subcategoryTable').DataTable({
            "paging": true,
            "searching": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "search": "ค้นหา:",
                "lengthMenu": "แสดง _MENU_ รายการ",
                "info": "แสดงรายการที่ _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                "infoEmpty": "แสดงรายการที่ 0 ถึง 0 จากทั้งหมด 0 รายการ",
                "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "หน้าสุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                },
                "zeroRecords": "ไม่พบข้อมูลที่ต้องการ"
            },
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "ทั้งหมด"]],
            "pageLength": 10,
            "initComplete": function() {
                $('.dataTables_wrapper .dataTables_paginate').css('float', 'right');
                $('.dataTables_wrapper .dataTables_info').css('float', 'left');
                $('.dataTables_wrapper .dataTables_length').css('float', 'left');
                $('.dataTables_wrapper .dataTables_filter').css('float', 'right');
            }
        });

        // ใช้ on() เพื่อจับปุ่ม Edit ที่ถูกสร้างขึ้นใหม่เมื่อเปลี่ยนหน้า
        $('#subcategoryTable tbody').on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            // แสดง loading indicator
            $('#subcategoryFormSubmitButton').html('<i class="fas fa-spinner fa-spin mr-2"></i>Loading...');
            $.ajax({
                url: 'fetch_subcategory.php', // แก้ไข path ให้ถูกต้อง
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response && response.id) {
                        // เติมข้อมูลในฟอร์ม
                        $('#subcategoryId').val(response.id);
                        $('#name').val(response.name);
                        
                        // เติมข้อมูลปีและไตรมาส
                        $('#year_id').val(response.year).trigger('change');
                        $('#quarter').val(response.quarter).trigger('change');
                        
                        // รอให้ categories โหลดเสร็จแล้วค่อยเติม category_id
                        setTimeout(function() {
                            loadCategoriesForYearQuarter().then(function() {
                                $('#category_id').val(response.category_id).trigger('change');
                            });
                        }, 500);
                        
                        // เปลี่ยนข้อความปุ่ม
                        $('#subcategoryFormSubmitButton').html('<i class="fas fa-edit mr-2"></i>แก้ไขหมวดหมู่ย่อย');
                        $('#subcategoryFormSubmitButton').attr('name', 'edit_subcategory');
                        openModal();
                    } else {
                        Swal.fire({
                            title: 'ไม่พบข้อมูลหมวดหมู่ย่อย',
                            icon: 'error',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        resetForm();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', xhr.responseText, status, error);
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาดในการดึงข้อมูล',
                        text: error,
                        icon: 'error',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    resetForm();
                }
            });
        });

        // เมื่อ Modal ถูกปิด, ฟอร์มจะถูกรีเซ็ต
        $('#subcategoryModal').on('hidden.bs.modal', function() {
            resetForm();
        });


    });
</script>