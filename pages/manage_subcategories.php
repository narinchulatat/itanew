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

?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

/* Responsive select dropdown */
#category_id {
    position: relative;
    z-index: 10;
    white-space: nowrap;
    word-break: break-word;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    font-size: 1rem;
    padding: 0.5rem;
}
#category_id option {
    max-width: 95vw;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    font-size: 1rem;
    padding: 0.25rem 0.5rem;
    overflow-y: auto;
    max-height: 300px;
}
@media (max-width: 640px) {
    #category_id {
        font-size: 0.95rem;
        padding: 0.4rem;
    }
    #category_id option {
        font-size: 0.95rem;
        max-width: 90vw;
        max-height: 200px;
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
<div id="subcategoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
            <h4 class="text-lg font-semibold text-gray-800">เพิ่ม/แก้ไขหมวดหมู่ย่อย</h4>
            <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl close transition duration-200" onclick="closeModal()">&times;</button>
        </div>
        <div class="px-6 py-6">
            <form id="subcategoryForm" action="index.php?page=manage_subcategories" method="POST">
                <div class="space-y-4">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">หมวดหมู่หลัก</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 select2-dropdown" 
                                id="category_id" 
                                name="category_id" 
                                required>
                            <option value="">เลือกหมวดหมู่หลัก</option>
                            <?php
                            // ดึงข้อมูล categories เพื่อแสดงใน select option
                            $stmt = $pdo->prepare("SELECT * FROM categories");
                            $stmt->execute();
                            $categories = $stmt->fetchAll();
                            foreach ($categories as $category) : ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">ชื่อหมวดหมู่ย่อย</label>
                        <textarea id="name" name="name" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" required></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" class="bg-gray-300 text-gray-700 rounded-md px-4 py-2 hover:bg-gray-400 transition duration-200" onclick="closeModal()">
                        <i class="fas fa-times mr-2"></i>ยกเลิก
                    </button>
                    <button type="submit" id="subcategoryFormSubmitButton" name="add_subcategory" class="bg-blue-600 text-white rounded-md px-4 py-2 hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-save mr-2"></i>เพิ่มหมวดหมู่ย่อย
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
        $('#category_id').val('').trigger('change');
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
                url: '/newita/fetch_subcategory.php', // ใช้ path แบบ absolute เพื่อแก้ปัญหา 404
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response && response.id) {
                        $('#subcategoryId').val(response.id);
                        $('#category_id').val(response.category_id).trigger('change');
                        $('#name').val(response.name);
                        $('#subcategoryFormSubmitButton').html('<i class="fas fa-edit mr-2"></i>Update Subcategory');
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
                    console.log('AJAX error:', xhr.responseText, status, error); // เพิ่ม log สำหรับ debug
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

        // เพิ่ม Select2 ให้ dropdown หมวดหมู่หลัก
        $('#category_id').select2({
            dropdownParent: $('#subcategoryModal'),
            width: '100%',
            minimumResultsForSearch: 10,
            placeholder: 'เลือกหมวดหมู่หลัก',
            allowClear: true
        });
    });
</script>
