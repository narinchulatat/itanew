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
        if (isset($_POST['add_category'])) {
            $name = $_POST['name'];
            $year = $_POST['year'];
            $quarter = $_POST['quarter'];
            $stmt = $pdo->prepare("INSERT INTO categories (name, year, quarter) VALUES (?, ?, ?)");
            $stmt->execute([$name, $year, $quarter]);
            $message = 'เพิ่มหมวดหมู่หลักสำเร็จ!';
            $action = 'add';
        } elseif (isset($_POST['edit_category'])) {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $year = $_POST['year'];
            $quarter = $_POST['quarter'];
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, year = ?, quarter = ? WHERE id = ?");
            $stmt->execute([$name, $year, $quarter, $id]);
            $message = 'แก้ไขหมวดหมู่หลักสำเร็จ!';
            $action = 'edit';
        } elseif (isset($_POST['delete_category'])) {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'ลบหมวดหมู่หลักสำเร็จ!';
            $action = 'delete';
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $message = 'ไม่สามารถลบหมวดหมู่หลักได้ เนื่องจากถูกใช้งานในข้อมูลอื่น';
            $action = 'error';
        } else {
            throw $e;
        }
    }
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '$message',
                icon: '" . ($action === 'error' ? 'error' : 'success') . "',
                timer: 1500,
                showConfirmButton: false
            }).then(function() {
                window.location.href = 'index.php?page=manage_categories';
            });
        });
    </script>";
    exit;
}
ob_end_flush(); // ส่งบัฟเฟอร์เอาต์พุตทั้งหมด

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
.dataTables_wrapper .dataTables_length select {
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.25rem;
    margin-left: 0.5rem;
    margin-right: 0.5rem;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.previous,
.dataTables_wrapper .dataTables_paginate .paginate_button.next {
    display: inline-block !important;
}
/* Responsive table cell ellipsis */
@media (max-width: 640px) {
    #categoryTable th, #categoryTable td {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        font-size: 0.95rem !important;
    }
    #categoryTable td, #categoryTable th {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
    }
}
#categoryTable td {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    word-break: break-word;
}
@media (max-width: 640px) {
    #categoryTable td {
        max-width: 100px;
        font-size: 0.9rem;
    }
}
/* Responsive modal width */
@media (max-width: 640px) {
    #categoryModal .max-w-lg {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0 !important;
        padding: 0.5rem !important;
    }
}
/* Responsive select dropdown */
#name {
    white-space: nowrap;
    word-break: break-word;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    font-size: 1rem;
    padding: 0.5rem;
}
#name option {
    max-width: 95vw;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    font-size: 1rem;
    padding: 0.25rem 0.5rem;
}
@media (max-width: 640px) {
    #name {
        font-size: 0.95rem;
        padding: 0.4rem;
    }
    #name option {
        font-size: 0.95rem;
        max-width: 90vw;
    }
}
</style>

<section class="content-header">
    <h1 class="text-2xl font-bold text-gray-800">จัดการหมวดหมู่หลัก</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-800">หมวดหมู่หลัก</h3>
                <button type="button" onclick="openModal()" class="bg-green-600 text-white rounded-lg px-6 py-2 hover:bg-green-700 transition duration-200 shadow-md">
                    <i class="fas fa-plus mr-2"></i>เพิ่มหมวดหมู่หลัก
                </button>
            </div>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table id="categoryTable" class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Year (พ.ศ.)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Quarter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        // ดึงข้อมูลปีทั้งหมดจากตาราง years
                        $yearStmt = $pdo->prepare("SELECT * FROM years ORDER BY year DESC");
                        $yearStmt->execute();
                        $years = $yearStmt->fetchAll();

                        // ดึงข้อมูล categories พร้อม JOIN ปี
                        $stmt = $pdo->prepare("SELECT c.*, y.year AS year_text FROM categories c LEFT JOIN years y ON c.year = y.id");
                        $stmt->execute();
                        $categories = $stmt->fetchAll();
                        foreach ($categories as $category) : ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $category['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($category['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($category['year_text']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        ไตรมาส <?= htmlspecialchars($category['quarter']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button class="bg-blue-600 text-white rounded-md px-3 py-1 hover:bg-blue-700 transition duration-200 text-sm edit-btn"
                                        data-id="<?= $category['id'] ?>"
                                        data-toggle="modal"
                                        data-target="#categoryModal">
                                        <i class="fas fa-edit mr-1"></i>แก้ไข
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                        <button type="submit" name="delete_category" class="bg-red-600 text-white rounded-md px-3 py-1 hover:bg-red-700 transition duration-200 text-sm delete-btn">
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

<!-- Modal for Adding/Editing Category -->
<div id="categoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl xl:max-w-4xl mx-4">
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
            <h4 class="text-lg font-semibold text-gray-800">เพิ่ม/แก้ไขหมวดหมู่หลัก</h4>
            <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl close transition duration-200" onclick="closeModal()">&times;</button>
        </div>
        <div class="px-6 py-6">
            <form id="categoryForm" action="index.php?page=manage_categories" method="POST">
                <input type="hidden" id="categoryId" name="id">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">ชื่อหมวดหมู่หลัก</label>
                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" id="name" name="name" placeholder="กรอกชื่อหมวดหมู่หลัก" rows="2" required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 mb-2">ปี (พ.ศ.)</label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" id="year" name="year" required>
                                <option value="">เลือกปี</option>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['year']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="quarter" class="block text-sm font-medium text-gray-700 mb-2">ไตรมาส</label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" 
                                    id="quarter" 
                                    name="quarter" 
                                    required>
                                <option value="">เลือกไตรมาส</option>
                                <option value="1">ไตรมาส 1</option>
                                <option value="2">ไตรมาส 2</option>
                                <option value="3">ไตรมาส 3</option>
                                <option value="4">ไตรมาส 4</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                    <button type="button" 
                            onclick="closeModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-200">
                        ยกเลิก
                    </button>
                    <button type="submit" 
                            name="add_category" 
                            class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition duration-200 shadow-md" 
                            id="categoryFormSubmitButton">
                        <i class="fas fa-save mr-2"></i>Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function openModal() {
        document.getElementById('categoryModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        document.getElementById('categoryModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
        resetForm();
    }
    function resetForm() {
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryId').value = '';
        document.getElementById('name').value = '';
        document.getElementById('year').value = '';
        document.getElementById('quarter').value = '';
        document.getElementById('categoryFormSubmitButton').textContent = 'เพิ่มหมวดหมู่หลัก';
        document.getElementById('categoryFormSubmitButton').innerHTML = '<i class="fas fa-save mr-2"></i>เพิ่มหมวดหมู่หลัก';
        document.getElementById('categoryFormSubmitButton').setAttribute('name', 'add_category');
    }
    document.getElementById('categoryModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('categoryModal').classList.contains('hidden')) {
            closeModal();
        }
    });
    $(document).ready(function() {
        var table = $('#categoryTable').DataTable({
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
                // ปรับ styling หลังจาก DataTable โหลดเสร็จ
                $('.dataTables_wrapper .dataTables_paginate').css('float', 'right');
                $('.dataTables_wrapper .dataTables_info').css('float', 'left');
                $('.dataTables_wrapper .dataTables_length').css('float', 'left');
                $('.dataTables_wrapper .dataTables_filter').css('float', 'right');
            }
        });

        // ใช้ on() เพื่อจับปุ่ม Edit ที่ถูกสร้างขึ้นใหม่เมื่อเปลี่ยนหน้า
        $('#categoryTable tbody').on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            // เรียกข้อมูลล่าสุดจากเซิร์ฟเวอร์เมื่อกดปุ่ม Edit
            $.ajax({
                url: 'fetch_category.php',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX response:', response); // debug ดูค่าที่ได้
                    // เติมข้อมูลในฟอร์ม
                    $('#categoryId').val(response.id);
                    $('#name').val(response.name); // textarea
                    $('#quarter').val(response.quarter); // select
                    openModal();
                    setTimeout(function() {
                        $('#year').val(response.year).trigger('change'); // select ปี (id)
                    }, 200);
                    $('#categoryFormSubmitButton').html('<i class="fas fa-edit mr-2"></i>แก้ไขหมวดหมู่หลัก');
                    $('#categoryFormSubmitButton').attr('name', 'edit_category');
                }
            });
        });

        // SweetAlert2 ยืนยันการลบ
        $('#categoryTable tbody').on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            Swal.fire({
                title: 'คุณต้องการลบหมวดหมู่หลักนี้ใช่หรือไม่?',
                text: 'การลบนี้ไม่สามารถย้อนกลับได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

</body>
</html>