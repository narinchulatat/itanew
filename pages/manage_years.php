<?php
require_once '../db.php';
session_start();

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: index.php');
    exit;
}

$message = '';
$action = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_year'])) {
            $year = $_POST['year'];
            $stmt = $pdo->prepare("INSERT INTO years (year) VALUES (?)");
            $stmt->execute([$year]);
            $message = 'เพิ่มปีสำเร็จ!';
            $action = 'add';
        } elseif (isset($_POST['edit_year'])) {
            $id = $_POST['id'];
            $year = $_POST['year'];
            $stmt = $pdo->prepare("UPDATE years SET year = ? WHERE id = ?");
            $stmt->execute([$year, $id]);
            $message = 'แก้ไขปีสำเร็จ!';
            $action = 'edit';
        } elseif (isset($_POST['delete_year'])) {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM years WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'ลบปีสำเร็จ!';
            $action = 'delete';
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $message = 'ไม่สามารถลบปีนี้ได้ เนื่องจากถูกใช้งานในข้อมูลอื่น';
            $action = 'error';
        } else {
            $message = 'เกิดข้อผิดพลาดในการดำเนินการ: ' . $e->getMessage();
            $action = 'error';
        }
    }
    
    // ใช้ JavaScript เพื่อแสดงข้อความและ redirect
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '$message',
                icon: '" . ($action === 'error' ? 'error' : 'success') . "',
                timer: 1500,
                showConfirmButton: false
            }).then(function() {
                window.location.href = 'index.php?page=manage_years';
            });
        });
    </script>";
    exit;
}
?>

<style>
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
@media (max-width: 640px) {
    #yearTable th, #yearTable td {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        font-size: 0.95rem !important;
    }
    #yearTable td, #yearTable th {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
    }
}
#yearTable td {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    word-break: break-word;
}
@media (max-width: 640px) {
    #yearTable td {
        max-width: 100px;
        font-size: 0.9rem;
    }
}
#yearModal .max-w-lg { max-width: 98vw !important; width: 98vw !important; margin: 0 !important; padding: 0.5rem !important; }
</style>

<section class="content-header">
    <h1 class="text-2xl font-bold text-gray-800">กำหนดปี (พ.ศ.)</h1>
</section>
<section class="content">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-800">ปี (พ.ศ.)</h3>
                <button type="button" onclick="openModal()" class="bg-green-600 text-white rounded-lg px-6 py-2 hover:bg-green-700 transition duration-200 shadow-md">
                    <i class="fas fa-plus mr-2"></i>เพิ่มปี
                </button>
            </div>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table id="yearTable" class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">ปี (พ.ศ.)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM years ORDER BY year DESC");
                        $stmt->execute();
                        $years = $stmt->fetchAll();
                        foreach ($years as $year) : ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $year['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($year['year']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button class="bg-blue-600 text-white rounded-md px-3 py-1 hover:bg-blue-700 transition duration-200 text-sm edit-btn"
                                        data-id="<?= $year['id'] ?>"
                                        data-year="<?= $year['year'] ?>"
                                        data-toggle="modal"
                                        data-target="#yearModal">
                                        <i class="fas fa-edit mr-1"></i>แก้ไข
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $year['id'] ?>">
                                        <button type="submit" name="delete_year" class="bg-red-600 text-white rounded-md px-3 py-1 hover:bg-red-700 transition duration-200 text-sm delete-btn">
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

<!-- Modal for Adding/Editing Year -->
<div id="yearModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4" style="max-width:400px;">
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
            <h4 class="text-lg font-semibold text-gray-800" id="yearModalTitle">เพิ่มปี (พ.ศ.)</h4>
            <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl close transition duration-200" onclick="closeModal()">&times;</button>
        </div>
        <div class="px-6 py-6">
            <form id="yearForm" action="index.php?page=manage_years" method="POST">
                <input type="hidden" id="yearId" name="id">
                <div class="space-y-4">
                    <div>
                        <label for="yearInput" class="block text-sm font-medium text-gray-700 mb-2">ปี (พ.ศ.)</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" id="yearInput" name="year" placeholder="กรอกปี พ.ศ." min="2500" max="2600" required>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-200">ยกเลิก</button>
                    <button type="submit" name="add_year" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition duration-200 shadow-md" id="yearFormSubmitButton">
                        <i class="fas fa-save mr-2"></i>เพิ่มปี
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openModal() {
    document.getElementById('yearModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('yearModalTitle').textContent = 'เพิ่มปี (พ.ศ.)';
    document.getElementById('yearFormSubmitButton').innerHTML = '<i class="fas fa-save mr-2"></i>เพิ่มปี';
    document.getElementById('yearFormSubmitButton').setAttribute('name', 'add_year');
}
function closeModal() {
    document.getElementById('yearModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    resetForm();
}
function resetForm() {
    document.getElementById('yearForm').reset();
    document.getElementById('yearId').value = '';
    document.getElementById('yearInput').value = '';
    document.getElementById('yearModalTitle').textContent = 'เพิ่มปี (พ.ศ.)';
    document.getElementById('yearFormSubmitButton').innerHTML = '<i class="fas fa-save mr-2"></i>เพิ่มปี';
    document.getElementById('yearFormSubmitButton').setAttribute('name', 'add_year');
}
$(document).ready(function() {
    var table = $('#yearTable').DataTable({
        paging: true,
        searching: true,
        info: true,
        autoWidth: false,
        responsive: true,
        language: {
            search: 'ค้นหา:',
            lengthMenu: 'แสดง _MENU_ รายการ',
            info: 'แสดงรายการที่ _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ',
            infoEmpty: 'แสดงรายการที่ 0 ถึง 0 จากทั้งหมด 0 รายการ',
            infoFiltered: '(กรองจากทั้งหมด _MAX_ รายการ)',
            paginate: {
                first: 'หน้าแรก',
                last: 'หน้าสุดท้าย',
                next: 'ถัดไป',
                previous: 'ก่อนหน้า'
            },
            zeroRecords: 'ไม่พบข้อมูลที่ต้องการ'
        },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'ทั้งหมด']],
        pageLength: 10,
        initComplete: function() {
            $('.dataTables_wrapper .dataTables_paginate').css('float', 'right');
            $('.dataTables_wrapper .dataTables_info').css('float', 'left');
            $('.dataTables_wrapper .dataTables_length').css('float', 'left');
            $('.dataTables_wrapper .dataTables_filter').css('float', 'right');
        }
    });
    $('#yearTable tbody').on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        var year = $(this).data('year');
        $('#yearId').val(id);
        $('#yearInput').val(year);
        $('#yearModalTitle').text('แก้ไขปี (พ.ศ.)');
        $('#yearFormSubmitButton').html('<i class="fas fa-edit mr-2"></i>แก้ไขปี');
        $('#yearFormSubmitButton').attr('name', 'edit_year');
        openModal();
    });
    $('#yearTable tbody').on('click', '.delete-btn', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        Swal.fire({
            title: 'คุณต้องการลบปีนี้ใช่หรือไม่?',
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
