<?php
session_start();
include './db.php';

// ฟังก์ชันสำหรับการเข้ารหัส Base32
function base32_encode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = '';
    $padding = strlen($data) % 5;
    $data .= str_repeat("\0", 5 - $padding);

    for ($i = 0; $i < strlen($data); $i += 5) {
        $chunk = substr($data, $i, 5);
        $bits = '';
        for ($j = 0; $j < 5; $j++) {
            $bits .= str_pad(decbin(ord($chunk[$j])), 8, '0', STR_PAD_LEFT);
        }
        for ($j = 0; $j < 8; $j++) {
            $index = bindec(substr($bits, $j * 5, 5));
            $base32 .= $alphabet[$index];
        }
    }

    return rtrim($base32, '=');
}

// ฟังก์ชันสำหรับการถอดรหัส Base32
function base32_decode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = str_pad($data, ceil(strlen($data) / 8) * 8, '=', STR_PAD_RIGHT);
    $base64 = '';
    for ($i = 0; $i < strlen($data); $i += 8) {
        $chunk = substr($data, $i, 8);
        $bits = '';
        for ($j = 0; $j < 8; $j++) {
            $bits .= str_pad(decbin(strpos($alphabet, $chunk[$j])), 5, '0', STR_PAD_LEFT);
        }
        for ($j = 0; $j < strlen($bits) / 8; $j++) {
            $base64 .= chr(bindec(substr($bits, $j * 8, 8)));
        }
    }
    return rtrim($base64, "\0");
}

// ฟังก์ชันสำหรับการเข้ารหัสชื่อไฟล์
function encode_file_name($file_name) {
    return urlencode(base32_encode($file_name));
}

// รับค่า subcategory_id จาก URL และถอดรหัส
$subcategory_id = isset($_GET['subcategory_id']) ? base32_decode($_GET['subcategory_id']) : 0;

// ดึงข้อมูลเอกสารที่ได้รับการอนุมัติตามหมวดหมู่ย่อย
$stmt = $pdo->prepare("
    SELECT d.id, d.title, d.file_name, d.file_upload, c.name as category_name, s.name as subcategory_name, 
           CONCAT(p.first_name, ' ', p.last_name) AS uploader_name
    FROM documents d
    JOIN categories c ON d.category_id = c.id
    JOIN subcategories s ON d.subcategory_id = s.id
    JOIN users u ON d.uploaded_by = u.id
    JOIN profile p ON u.id = p.user_id
    WHERE d.subcategory_id = ? AND d.approved = 1
");
$stmt->execute([$subcategory_id]);
$documents = $stmt->fetchAll();

// ดึงชื่อหมวดหมู่ย่อย
$stmt = $pdo->prepare("SELECT name FROM subcategories WHERE id = ?");
$stmt->execute([$subcategory_id]);
$subcategory = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($subcategory['name']) ?> - เอกสารที่อนุมัติแล้ว</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5/css/dataTables.bootstrap5.min.css" />
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
        body { background: #f6f8fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 1rem; }
        .shadow-lg { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .rounded-lg { border-radius: 0.75rem; }
        .table-responsive { overflow-x: auto; }
        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .max-w-xs { max-width: 12rem; }
        @media (max-width: 640px) {
            .table-responsive { overflow-x: auto; }
            th, td { font-size: 0.95em; }
            .max-w-xs { max-width: 7rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="shadow-lg rounded-lg bg-white p-6 mb-6">
        <h2 class="text-xl font-bold text-blue-700 mb-4">เอกสารในหมวดหมู่</h2>
        <div class="table-responsive">
            <table id="documentsTable" class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead class="bg-blue-50">
                    <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                        <th>ชื่อเอกสาร</th>
                        <th>หมวดหมู่ย่อย</th>
                        <th>สถานะ</th>
                        <th>ดาวน์โหลด</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-medium">
                    <?php foreach ($documents as $index => $document): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= htmlspecialchars($document['title']) ?>"><?= htmlspecialchars($document['title']) ?></td>
                            <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= htmlspecialchars($document['subcategory_name']) ?>"><?= htmlspecialchars($document['subcategory_name']) ?></td>
                            <td class="py-4 px-6 max-w-xs truncate cursor-pointer" title="<?= $document['approved'] == 1 ? 'อนุมัติ' : 'รอการอนุมัติ' ?>"><?= $document['approved'] == 1 ? 'อนุมัติ' : 'รอการอนุมัติ' ?></td>
                            <td class="py-4 px-6">
                                <a href="download.php?file=<?= encode_file_name($document['file_name']) ?>" class="inline-flex items-center bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600" download>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                                    ดาวน์โหลด
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#documentsTable').DataTable({
        paging: true,
        searching: true,
        info: true,
        autoWidth: false,
        // responsive: true, // ลบออก ใช้ CSS responsive แทน
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
        pageLength: 10
    });
});
</script>
</body>
</html>
