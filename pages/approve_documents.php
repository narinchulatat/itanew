<?php
// ย้าย ob_start() ไปไว้ก่อน session_start() และใส่ ob_clean() ก่อน header redirect
ob_start();
ob_clean(); // ล้าง output buffer ก่อน

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('./db.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้ว
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'index.php?page=login';</script>";
    exit;
}

// ตรวจสอบสิทธิ์ (อนุญาตให้ role_id = 1 และ 2 เข้าถึงได้)
if (!in_array($_SESSION['role_id'], [1, 2])) {
    echo "<script>window.location.href = 'index.php?page=home';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_documents']) && isset($_POST['document_ids'])) {
        $document_ids = $_POST['document_ids'];
        foreach ($document_ids as $doc_id) {
            // อัปเดตสถานะการอนุมัติเอกสารในตาราง documents
            $stmt = $pdo->prepare("UPDATE documents SET approved = 1, status = 'approved' WHERE id = ?");
            $stmt->execute([$doc_id]);
            // ลบ approvals เดิมก่อนบันทึกใหม่ (ป้องกันซ้ำ)
            $stmt = $pdo->prepare("DELETE FROM approvals WHERE document_id = ?");
            $stmt->execute([$doc_id]);
            // บันทึกการอนุมัติลงในตาราง approvals
            $stmt = $pdo->prepare("INSERT INTO approvals (document_id, user_id, status, approved_at) VALUES (?, ?, 'approved', NOW())");
            $stmt->execute([$doc_id, $user_id]);
        }
        echo "<script>
            Swal.fire({
                title: 'สำเร็จ!',
                text: 'เอกสารถูกอนุมัติเรียบร้อยแล้ว',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(function() {
                window.location.href = 'index.php?page=approve_documents';
            });
        </script>";
    }
    // เพิ่มส่วนยกเลิกอนุมัติ
    if (isset($_POST['unapprove_document_id']) && !empty($_POST['unapprove_document_id'])) {
        $doc_id = $_POST['unapprove_document_id'];
        // อัปเดตสถานะเอกสาร
        $stmt = $pdo->prepare("UPDATE documents SET approved = 0, status = 'pending' WHERE id = ?");
        $stmt->execute([$doc_id]);
        // ลบข้อมูลใน approvals
        $stmt = $pdo->prepare("DELETE FROM approvals WHERE document_id = ?");
        $stmt->execute([$doc_id]);
        echo "<script>
            Swal.fire({
                title: 'สำเร็จ!',
                text: 'ยกเลิกอนุมัติเอกสารเรียบร้อยแล้ว',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(function() {
                window.location.href = 'index.php?page=approve_documents';
            });
        </script>";
    }
}

// ดึงเอกสารที่ยังไม่ถูกอนุมัติ พร้อมชื่อหมวดหมู่หลักและหมวดหมู่ย่อย
$stmt = $pdo->prepare("
    SELECT 
        d.*, 
        c.name AS category_name, 
        s.name AS subcategory_name 
    FROM documents d
    LEFT JOIN categories c ON d.category_id = c.id
    LEFT JOIN subcategories s ON d.subcategory_id = s.id
    WHERE d.approved = 0
");
$stmt->execute();
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงเอกสารที่ถูกอนุมัติแล้ว
$stmt_approved = $pdo->prepare("
    SELECT 
        d.*, 
        c.name AS category_name, 
        s.name AS subcategory_name 
    FROM documents d
    LEFT JOIN categories c ON d.category_id = c.id
    LEFT JOIN subcategories s ON d.subcategory_id = s.id
    WHERE d.approved = 1
");
$stmt_approved->execute();
$approved_documents = $stmt_approved->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>อนุมัติเอกสาร</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- DataTables Tailwind CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS (core first) -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Tailwind theme -->
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <style>
    @media (max-width: 640px) {
        .table-responsive { overflow-x: auto; }
        th, td { font-size: 0.95em; }
        .min-w-full { min-width: 600px; }
    }
    .truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .max-w-xs { max-width: 12rem; }
    .table-responsive { width: 100%; }
    /* ปรับปุ่มให้สวยขึ้น */
    button.unapproveBtn {
        transition: box-shadow 0.2s;
        box-shadow: 0 1px 4px 0 rgba(220,38,38,0.12);
    }
    button.unapproveBtn:hover {
        box-shadow: 0 2px 8px 0 rgba(220,38,38,0.18);
    }
    button#approveBtn {
        transition: box-shadow 0.2s;
        box-shadow: 0 1px 4px 0 rgba(16,185,129,0.12);
    }
    button#approveBtn:hover {
        box-shadow: 0 2px 8px 0 rgba(16,185,129,0.18);
    }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="container mx-auto p-4">
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-blue-700 mb-4">อนุมัติเอกสาร</h2>
        <form id="approveForm" method="POST" action="index.php?page=approve_documents" class="space-y-4">
            <div class="table-responsive overflow-x-auto">
                <table id="approveTable" class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                            <th><input type="checkbox" id="select_all" onclick="selectAll(this)"></th>
                            <th>ชื่อเอกสาร</th>
                            <th>หมวดหมู่หลัก</th>
                            <th>หมวดหมู่ย่อย</th>
                            <th>ดาวน์โหลด</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-medium">
                        <?php if (count($documents) > 0): ?>
                            <?php foreach ($documents as $document): ?>
                                <tr class="hover:bg-gray-50">
                                    <td><input type="checkbox" name="document_ids[]" value="<?= $document['id'] ?>"></td>
                                    <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= htmlspecialchars($document['title']) ?>"><?= htmlspecialchars($document['title']) ?></td>
                                    <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= htmlspecialchars($document['category_name']) ?>"><?= htmlspecialchars($document['category_name']) ?></td>
                                    <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= htmlspecialchars($document['subcategory_name']) ?>"><?= htmlspecialchars($document['subcategory_name']) ?></td>
                                    <td class="py-4 px-6">
                                        <a href="uploads/<?= htmlspecialchars($document['file_name']) ?>" class="inline-flex items-center bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                                            ดาวน์โหลด
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" id="approveBtn" class="bg-green-600 text-white rounded px-4 py-2 hover:bg-green-700 w-full sm:w-auto">อนุมัติเอกสารที่เลือก</button>
        </form>
    </div>
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-blue-700 mb-4">เอกสารที่อนุมัติแล้ว:</h2>
        <div class="table-responsive overflow-x-auto">
            <form id="unapproveForm" method="POST" action="index.php?page=approve_documents">
            <table id="approve_Table" class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                        <th>ชื่อเอกสาร</th>
                        <th>หมวดหมู่หลัก</th>
                        <th>หมวดหมู่ย่อย</th>
                        <th>ดาวน์โหลด</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-medium">
                    <?php if (count($approved_documents) > 0): ?>
                        <?php foreach ($approved_documents as $document): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= htmlspecialchars($document['title']) ?>"><?= htmlspecialchars($document['title']) ?></td>
                                <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= htmlspecialchars($document['category_name']) ?>"><?= htmlspecialchars($document['category_name']) ?></td>
                                <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= htmlspecialchars($document['subcategory_name']) ?>"><?= htmlspecialchars($document['subcategory_name']) ?></td>
                                <td class="py-4 px-6">
                                    <a href="uploads/<?= htmlspecialchars($document['file_name']) ?>" class="inline-flex items-center bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                                        ดาวน์โหลด
                                    </a>
                                </td>
                                <td class="py-4 px-6">
                                    <button type="button" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 unapproveBtn" data-id="<?= $document['id'] ?>">ยกเลิกอนุมัติ</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <input type="hidden" name="unapprove_document_id" id="unapprove_document_id">
            </form>
        </div>
    </div>
</div>
<script>
function selectAll(source) {
    // เลือกทุก checkbox ใน DataTables (รวมแถวที่ถูก paginate/filter)
    var table = $('#approveTable').DataTable();
    var checked = source.checked;
    table.rows({ search: 'applied' }).nodes().to$().find('input[type="checkbox"]').prop('checked', checked);
}
$(document).ready(function() {
    $('#approveTable').DataTable({
        paging: true,
        searching: true,
        info: true,
        autoWidth: false,
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดงรายการที่ _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            infoEmpty: "แสดงรายการที่ 0 ถึง 0 จากทั้งหมด 0 รายการ",
            infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
            paginate: {
                first: "หน้าแรก",
                last: "หน้าสุดท้าย",
                next: "ถัดไป",
                previous: "ก่อนหน้า"
            },
            zeroRecords: "<span class='block text-center text-gray-500 py-8 text-lg'>ไม่พบข้อมูลที่ต้องการ</span>",
            emptyTable: "<span class='block text-center text-gray-500 py-8 text-lg'>ไม่พบข้อมูลที่ต้องการ</span>"
        },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "ทั้งหมด"]],
        pageLength: 10,
        responsive: true
    });
    $('#approve_Table').DataTable({
        paging: true,
        searching: true,
        info: true,
        autoWidth: false,
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดงรายการที่ _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            infoEmpty: "แสดงรายการที่ 0 ถึง 0 จากทั้งหมด 0 รายการ",
            infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
            paginate: {
                first: "หน้าแรก",
                last: "หน้าสุดท้าย",
                next: "ถัดไป",
                previous: "ก่อนหน้า"
            },
            zeroRecords: "<span class='block text-center text-gray-500 py-8 text-lg'>ไม่พบข้อมูลที่ต้องการ</span>",
            emptyTable: "<span class='block text-center text-gray-500 py-8 text-lg'>ไม่พบข้อมูลที่ต้องการ</span>"
        },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "ทั้งหมด"]],
        pageLength: 10,
        responsive: true
    });
    // SweetAlert2 confirm before submit อนุมัติ
    $('#approveBtn').on('click', function(e) {
        e.preventDefault();
        if ($('input[name="document_ids[]"]:checked').length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกเอกสาร',
                text: 'โปรดเลือกเอกสารที่ต้องการอนุมัติ',
                confirmButtonText: 'ตกลง'
            });
            return;
        }
        // เพิ่ม input approve_documents เพื่อให้ POST ตรง backend
        if ($('#approveForm input[name="approve_documents"]').length === 0) {
            $('#approveForm').append('<input type="hidden" name="approve_documents" value="1">');
        }
        Swal.fire({
            title: 'ยืนยันการอนุมัติ',
            text: 'คุณต้องการอนุมัติเอกสารที่เลือกใช่หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ใช่, อนุมัติ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#approveForm').submit();
            }
        });
    });
    // SweetAlert2 confirm before unapprove
    $(document).on('click', '.unapproveBtn', function(e) {
        e.preventDefault();
        var docId = $(this).data('id');
        Swal.fire({
            title: 'ยืนยันการยกเลิกอนุมัติ',
            text: 'คุณต้องการยกเลิกอนุมัติเอกสารนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ยกเลิกอนุมัติ',
            cancelButtonText: 'กลับไป'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#unapprove_document_id').val(docId);
                $('#unapproveForm')[0].submit();
            }
        });
    });
    $('#approveTable').on('draw.dt', function() {
        // เมื่อเปลี่ยนหน้า/กรอง ให้ sync checkbox select_all
        var allChecked = $('#approveTable tbody input[type="checkbox"]').length > 0 &&
            $('#approveTable tbody input[type="checkbox"]').length === $('#approveTable tbody input[type="checkbox"]:checked').length;
        $('#select_all').prop('checked', allChecked);
    });
});
</script>
</body>
</html>