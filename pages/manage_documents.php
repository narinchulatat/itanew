<?php
session_start();
include('../db_sqlite.php');

// Mock session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role_id'] = 1;
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID
$user_role = $_SESSION['role_id']; // Get the logged-in user's role

// Handle document management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_document'])) {
        // Handle file upload
        $target_dir = "uploads/";
        $file_name = basename($_FILES["file_upload"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file)) {
            // Add new document
            $title = $_POST['title'];
            $content = $_POST['content'];
            $category_id = $_POST['category_id'];
            $subcategory_id = $_POST['subcategory_id'];
            $access_rights = $_POST['access_rights'];
            $file_label = $title; // Use title as the file label
            $status = 'รออนุมัติ'; // Set status to 'Pending Approval'

            $stmt = $pdo->prepare("INSERT INTO documents (title, content, status, created_at, category_id, subcategory_id, access_rights, approved, file_name, file_upload, file_label, uploaded_by) VALUES (?, ?, ?, NOW(), ?, ?, ?, 0, ?, NOW(), ?, ?)");
            $stmt->execute([$title, $content, $status, $category_id, $subcategory_id, $access_rights, $file_name, $file_label, $user_id]);

            echo "<script>
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: 'เอกสารถูกเพิ่มเรียบร้อยแล้ว',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = 'index.php?page=manage_documents';
                });
                </script>";
        } else {
            echo "<script>
                Swal.fire({
                    title: 'ผิดพลาด!',
                    text: 'ไม่สามารถอัปโหลดไฟล์ได้',
                    icon: 'error',
                    timer: 1500,
                    showConfirmButton: false
                });
                </script>";
        }
    } elseif (isset($_POST['edit_document'])) {
        // Edit document
        $id = $_POST['id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        $category_id = $_POST['category_id'];
        $subcategory_id = $_POST['subcategory_id'];
        $access_rights = $_POST['access_rights'];
        $file_label = $title; // Use title as the file label
        $status = 'รออนุมัติ'; // Set status to 'Pending Approval'

        if (!empty($_FILES["file_upload"]["name"])) {
            // If a new file is uploaded
            $target_dir = "uploads/";
            $file_name = basename($_FILES["file_upload"]["name"]);
            $target_file = $target_dir . $file_name;
            move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file);

            $stmt = $pdo->prepare("UPDATE documents SET title = ?, content = ?, status = ?, updated_at = NOW(), category_id = ?, subcategory_id = ?, access_rights = ?, file_name = ?, file_upload = NOW(), file_label = ? WHERE id = ?");
            $stmt->execute([$title, $content, $status, $category_id, $subcategory_id, $access_rights, $file_name, $file_label, $id]);
        } else {
            // If no new file is uploaded
            $stmt = $pdo->prepare("UPDATE documents SET title = ?, content = ?, status = ?, updated_at = NOW(), category_id = ?, subcategory_id = ?, access_rights = ?, file_label = ? WHERE id = ?");
            $stmt->execute([$title, $content, $status, $category_id, $subcategory_id, $access_rights, $file_label, $id]);
        }

        echo "<script>
            Swal.fire({
                title: 'สำเร็จ!',
                text: 'เอกสารถูกแก้ไขเรียบร้อยแล้ว',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(function() {
                window.location.href = 'index.php?page=manage_documents';
            });
            </script>";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_document'])) {
            $id = $_POST['id'];

            try {
                // เริ่มการทำงานในรูปแบบการทำธุรกรรม
                $pdo->beginTransaction();

                // ดึงข้อมูลของไฟล์ที่ต้องการลบ
                $stmt = $pdo->prepare("SELECT file_name FROM documents WHERE id = ?");
                $stmt->execute([$id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($file && $file['file_name']) {
                    $file_path = "uploads/" . $file['file_name'];

                    // ตรวจสอบว่าไฟล์มีอยู่จริงและทำการลบ
                    if (file_exists($file_path)) {
                        if (!unlink($file_path)) {
                            throw new Exception("Failed to delete file: " . $file_path);
                        }
                    }
                }

                // ลบข้อมูลเอกสารจากฐานข้อมูล
                $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
                $stmt->execute([$id]);

                // ตรวจสอบว่ามีแถวที่ถูกลบหรือไม่
                if ($stmt->rowCount() > 0) {
                    // ทำการยืนยันการทำธุรกรรม
                    $pdo->commit();
                    // Redirect ไปยังหน้า manage_documents เพื่อแสดงข้อมูลใหม่
                    header("Location: index.php?page=manage_documents&status=deleted");
                    exit;
                } else {
                    throw new Exception("Failed to delete document ID $id from database.");
                }
            } catch (Exception $e) {
                // หากมีข้อผิดพลาด ให้ยกเลิกการทำธุรกรรม
                $pdo->rollBack();
                echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href = 'index.php?page=manage_documents';</script>";
            }
        }
    }
}

// Fetch documents
$stmt = $pdo->query("SELECT d.id, d.title, d.content, d.status, d.file_name, d.file_label, d.category_id, d.subcategory_id, d.uploaded_by, d.access_rights, d.approved, c.name AS category_name, s.name AS subcategory_name, u.username AS uploaded_by_username 
                     FROM documents d
                     LEFT JOIN categories c ON d.category_id = c.id
                     LEFT JOIN subcategories s ON d.subcategory_id = s.id
                     LEFT JOIN users u ON d.uploaded_by = u.id");
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch config ปี/ไตรมาส
$config = $pdo->query("SELECT * FROM home_display_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$year_id = $config ? $config['year'] : null;
$quarter = $config ? $config['quarter'] : null;
// Fetch categories
if ($year_id && $quarter) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE year = ? AND `quarter` = ?");
    $stmt->execute([$year_id, $quarter]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $categories = [];
}
// Fetch all subcategories
$stmt = $pdo->query("SELECT * FROM subcategories");
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการเอกสาร</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
    <style>
        body { background: #f6f8fa; }
        .container {
            max-width: 1200px !important;
            width: 100%;
            margin: 0 auto !important;
            padding: 1rem !important;
        }
        .shadow-lg { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .rounded-lg { border-radius: 0.75rem; }
        .table-responsive {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow-x: auto;
        }
        #documentsTable {
            width: 100% !important;
            min-width: unset !important;
        }
        @media (max-width: 640px) {
            .container {
                max-width: 100vw !important;
                width: 100vw !important;
                padding: 0.5rem !important;
            }
            .table-responsive, #documentsTable {
                width: 100vw !important;
            }
        }
        #documentsTable th, #documentsTable td { white-space: nowrap; word-break: break-word; max-width: 180px; overflow: hidden; text-overflow: ellipsis; }
        @media (max-width: 640px) {
            #documentsTable th, #documentsTable td { max-width: 100px; font-size: 0.9rem; }
            .modal-content { max-width: 98vw !important; width: 98vw !important; margin: 0 !important; padding: 0.5rem !important; }
        }
        .select2-container { width: 100% !important; }
        .select2-selection { min-height: 38px !important; }
        .select2-selection__rendered {
            white-space: normal !important;
            word-break: break-word !important;
            text-overflow: ellipsis;
            overflow: hidden;
            max-width: 100%;
            display: block;
        }
        .select2-container .select2-dropdown {
            max-width: 98vw !important;
            width: 100% !important;
            min-width: 200px;
            word-break: break-word;
            white-space: normal;
            z-index: 9999 !important;
        }
        .select2-container .select2-results__option {
            white-space: normal !important;
            word-break: break-word !important;
            max-width: 98vw;
        }
        .swal2-popup { font-size: 1.1rem !important; }
        /* --- Fix select2 dropdown overflow modal --- */
        .select2-container .select2-dropdown {
            max-width: 98vw !important;
            width: 100% !important;
            min-width: 200px;
            word-break: break-word;
            white-space: normal;
            z-index: 9999 !important;
        }
        .select2-container .select2-results__option {
            white-space: normal;
            word-break: break-word;
        }
        .modal-content {
            max-width: 80rem !important; /* tailwind max-w-7xl */
            width: 100% !important;
            height: auto !important;
            border-radius: 0.75rem !important;
            padding: 2rem !important;
            overflow-y: auto !important;
            box-sizing: border-box;
        }
        @media (max-width: 1280px) {
            .modal-content {
                max-width: 98vw !important;
            }
        }
        @media (max-width: 640px) {
            .modal-content {
                max-width: 98vw !important;
                padding: 0.5rem !important;
            }
        }
        /* --- Fix select2 dropdown/category text wrap only in modal --- */
        #addModal .select2-container {
            width: 100% !important;
            max-width: 100% !important;
        }
        #addModal .select2-selection {
            min-height: 38px !important;
            max-width: 100% !important;
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }
        #addModal .select2-selection__rendered {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            max-width: 100%;
            display: block;
        }
        #addModal .select2-container .select2-dropdown {
            position: absolute !important;
            left: 0 !important;
            right: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            min-width: 200px;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
            z-index: 99999 !important;
        }
        #addModal .select2-container .select2-results__option {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            max-width: 100%;
        }
        .close {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-blue-700">จัดการเอกสาร</h3>
        <button onclick="openModal()" class="bg-green-600 text-white rounded px-4 py-2 hover:bg-green-700"><i class="fa fa-plus mr-2"></i>เพิ่มเอกสาร</button>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="table-responsive">
            <table id="documentsTable" class="table table-bordered w-full">
                <thead>
                    <tr>
                        <th>ชื่อเอกสาร</th>
                        <th>หมวดหมู่หลัก</th>
                        <th>หมวดหมู่ย่อย</th>
                        <th>สถานะ</th>
                        <th>ไฟล์</th>
                        <th>สิทธิ์</th>
                        <th>อนุมัติ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($documents) > 0): ?>
                        <?php foreach ($documents as $document): ?>
                            <tr 
                                data-id="<?= $document['id'] ?>"
                                data-title="<?= htmlspecialchars($document['title']) ?>"
                                data-content="<?= htmlspecialchars($document['content']) ?>"
                                data-category_id="<?= $document['category_id'] ?>"
                                data-subcategory_id="<?= $document['subcategory_id'] ?>"
                                data-access_rights="<?= htmlspecialchars($document['access_rights']) ?>"
                                data-file_name="<?= htmlspecialchars($document['file_name']) ?>"
                                data-approved="<?= $document['approved'] ?>"
                            >
                                <td><?= htmlspecialchars($document['title']) ?></td>
                                <td><?= htmlspecialchars($document['category_name']) ?></td>
                                <td><?= htmlspecialchars($document['subcategory_name']) ?></td>
                                <td><?= htmlspecialchars($document['status']) ?></td>
                                <td>
                                    <?php if (!empty($document['file_name'])): ?>
                                        <a href="uploads/<?= htmlspecialchars($document['file_name']) ?>" target="_blank" class="inline-flex items-center bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 text-sm">
                                            <i class="fa fa-file-alt mr-1"></i> เปิดดูเอกสาร
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($document['access_rights']) ?></td>
                                <td>
                                    <span class="label <?= $document['approved'] ? 'label-success' : 'label-warning' ?>">
                                        <?= $document['approved'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="bg-blue-600 text-white rounded px-3 py-1 hover:bg-blue-700 text-sm edit-btn" <?= $document['approved'] ? 'disabled style="opacity:0.5;cursor:not-allowed"' : '' ?>>แก้ไข</button>
                                    <button type="button" class="bg-red-600 text-white rounded px-3 py-1 hover:bg-red-700 text-sm delete-btn" <?= $document['approved'] ? 'disabled style="opacity:0.5;cursor:not-allowed"' : '' ?> onclick="confirmDelete('<?= $document['id'] ?>')">ลบ</button>
                                    <?php if ($user_role == 2 && !$document['approved']): ?>
                                        <form method="POST" action="index.php?page=manage_documents" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $document['id'] ?>">
                                            <button type="submit" name="approve_document" class="btn btn-warning btn-sm">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">ไม่พบเอกสาร</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal -->
    <div id="addModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="modal-content bg-white rounded-lg shadow-lg w-full max-w-7xl mx-auto p-6"> <!-- ใช้ max-w-7xl ของ tailwindcss -->
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <h4 class="text-lg font-bold" id="modalTitle">เพิ่ม/แก้ไขเอกสาร</h4>
                <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="space-y-4" id="documentForm">
                <input type="hidden" id="id" name="id">
                <div class="form-group">
                    <label for="title">ชื่อเอกสาร:</label>
                    <input type="text" class="border rounded px-3 py-2 w-full" id="title" name="title" placeholder="ชื่อเอกสาร" required>
                </div>
                <div class="form-group">
                    <label for="content">รายละเอียด:</label>
                    <textarea class="border rounded px-3 py-2 w-full" id="content" name="content" placeholder="รายละเอียด" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="category_id">หมวดหมู่หลัก:</label>
                    <select class="border rounded px-3 py-2 w-full select2" id="category_id" name="category_id" onchange="loadSubcategories(this.value)" required>
                        <option value="">-- เลือกหมวดหมู่หลัก --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subcategory_id">หมวดหมู่ย่อย:</label>
                    <select class="border rounded px-3 py-2 w-full select2" id="subcategory_id" name="subcategory_id" required>
                        <option value="">-- เลือกหมวดหมู่ย่อย --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file_upload">เอกสารประกอบ:</label>
                    <div class="relative">
                        <input type="file" class="border rounded px-3 py-2 w-full hidden" id="file_upload" name="file_upload">
                        <label for="file_upload" id="fileLabel" class="cursor-pointer flex items-center justify-between border rounded px-3 py-2 w-full bg-gray-50 hover:bg-blue-50 text-gray-700">
                            <span id="fileName">เลือกไฟล์...</span>
                            <span class="ml-2 text-blue-600"><i class="fa fa-upload"></i></span>
                        </label>
                    </div>
                    <div id="currentFile" class="mt-2"></div>
                </div>
                <div class="form-group">
                    <label for="access_rights">สิทธิ์การเข้าถึง:</label>
                    <select class="border rounded px-3 py-2 w-full select2" id="access_rights" name="access_rights">
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                    </select>
                </div>
                <button type="submit" id="submitBtn" name="add_document" class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700 w-full">บันทึก</button>
            </form>
        </div>
    </div>
<!-- </div> -->
<script>
$(document).ready(function() {
    // DataTables
    $('#documentsTable').DataTable({
        paging: true,
        searching: true,
        info: true,
        autoWidth: false,
        responsive: true,
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
        initComplete: function() {
            $('.dataTables_wrapper .dataTables_paginate').addClass('float-right');
            $('.dataTables_wrapper .dataTables_info').addClass('float-left');
            $('.dataTables_wrapper .dataTables_length').addClass('float-left');
            $('.dataTables_wrapper .dataTables_filter').addClass('float-right');
        }
    });
    // Select2
    $('.select2').select2({
        dropdownAutoWidth: true,
        width: '100%',
        minimumResultsForSearch: 10,
        // Fix select2 in modal
        dropdownParent: $('#addModal')
    });
    // Modal
    window.openModal = function() {
        $('#addModal').removeClass('hidden');
        // Refresh select2 dropdowns
        setTimeout(function(){
            $('#category_id').select2('destroy').select2({
                dropdownAutoWidth: true,
                width: '100%',
                minimumResultsForSearch: 10,
                dropdownParent: $('#addModal')
            });
            $('#subcategory_id').select2('destroy').select2({
                dropdownAutoWidth: true,
                width: '100%',
                minimumResultsForSearch: 10,
                dropdownParent: $('#addModal')
            });
            $('#access_rights').select2('destroy').select2({
                dropdownAutoWidth: true,
                width: '100%',
                minimumResultsForSearch: 10,
                dropdownParent: $('#addModal')
            });
        }, 100);
    }
    window.closeModal = function() {
        $('#addModal').addClass('hidden');
        clearForm();
    }
    // Edit document
    $('.edit-btn').on('click', function() {
        var row = $(this).closest('tr');
        var approved = row.data('approved');
        if (approved) {
            Swal.fire({
                title: 'ไม่สามารถแก้ไขได้',
                text: 'เอกสารนี้ได้รับการอนุมัติแล้ว ไม่สามารถแก้ไขได้',
                icon: 'warning',
                confirmButtonText: 'ตกลง'
            });
            return;
        }
        var id = row.data('id');
        var title = row.data('title');
        var content = row.data('content');
        var category_id = row.data('category_id');
        var subcategory_id = row.data('subcategory_id');
        var access_rights = row.data('access_rights');
        var file_name = row.data('file_name');
        $('#id').val(id);
        $('#title').val(title);
        $('#content').val(content);
        $('#category_id').val(category_id).trigger('change');
        // โหลด subcategory ก่อน set ค่า
        $.get('get_subcategories.php?category_id=' + category_id, function(data) {
            $('#subcategory_id').html(data);
            $('#subcategory_id').val(subcategory_id).trigger('change');
        });
        $('#file_upload').val(''); // reset file input
        $('#access_rights').val(access_rights).trigger('change');
        // แสดงไฟล์เดิมถ้ามี
        if (file_name) {
            if ($('#old_file').length === 0) {
                $('<div id="old_file" class="mb-2 text-sm text-gray-600">ไฟล์เดิม: <a href="uploads/' + file_name + '" target="_blank" class="text-blue-600 underline">' + file_name + '</a></div>').insertBefore('#file_upload');
            } else {
                $('#old_file').html('ไฟล์เดิม: <a href="uploads/' + file_name + '" target="_blank" class="text-blue-600 underline">' + file_name + '</a>');
            }
        } else {
            $('#old_file').remove();
        }
        openModal();
    });
    // SweetAlert2 for delete confirmation
    window.confirmDelete = function(documentId) {
        Swal.fire({
            title: 'คุณแน่ใจหรือไม่?',
            text: 'คุณต้องการลบเอกสารนี้หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?page=manage_documents';
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = documentId;
                form.appendChild(input);
                var deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_document';
                deleteInput.value = '1';
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    // Reset form
    window.clearForm = function() {
        $('#id').val('');
        $('#title').val('');
        $('#content').val('');
        $('#category_id').val('').trigger('change');
        $('#subcategory_id').html('<option value="">-- เลือกหมวดหมู่ย่อย --</option>').val('').trigger('change');
        $('#file_upload').val('');
        $('#access_rights').val('public').trigger('change');
        $('#old_file').remove();
    }
});
var subcategories = <?php echo json_encode($subcategories); ?>;
function loadSubcategories(category_id) {
    var options = '<option value="">-- เลือกหมวดหมู่ย่อย --</option>';
    subcategories.forEach(function(subcat) {
        if (subcat.category_id == category_id) {
            options += '<option value="' + subcat.id + '">' + subcat.name + '</option>';
        }
    });
    $('#subcategory_id').html(options).trigger('change');
}
$('#category_id').on('change', function() {
    var category_id = $(this).val();
    loadSubcategories(category_id);
});
$('.edit-btn').on('click', function() {
    if ($(this).is(':disabled')) return;
    var row = $(this).closest('tr');
    var id = row.data('id');
    var title = row.data('title');
    var content = row.data('content');
    var category_id = row.data('category_id');
    var subcategory_id = row.data('subcategory_id');
    var access_rights = row.data('access_rights');
    var file_name = row.data('file_name');
    $('#id').val(id);
    $('#title').val(title);
    $('#content').val(content);
    $('#category_id').val(category_id).trigger('change');
    loadSubcategories(category_id);
    setTimeout(function(){
        $('#subcategory_id').val(subcategory_id).trigger('change');
    }, 100);
    $('#file_upload').val(''); // reset file input
    $('#access_rights').val(access_rights).trigger('change');
    // แสดงไฟล์เดิมถ้ามี
    if (file_name) {
        if ($('#old_file').length === 0) {
            $('<div id="old_file" class="mb-2 text-sm text-gray-600">ไฟล์เดิม: <a href="uploads/' + file_name + '" target="_blank" class="text-blue-600 underline">' + file_name + '</a></div>').insertBefore('#file_upload');
        } else {
            $('#old_file').html('ไฟล์เดิม: <a href="uploads/' + file_name + '" target="_blank" class="text-blue-600 underline">' + file_name + '</a>');
        }
    } else {
        $('#old_file').remove();
    }
    openModal();
});
</script>
</body>
</html>