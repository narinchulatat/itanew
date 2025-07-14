<?php
session_start();
include('./db.php');
include('./includes/FileUploadSecurity.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID
$user_role = $_SESSION['role_id']; // Get the logged-in user's role

// Initialize file upload security
$fileUploadSecurity = new FileUploadSecurity();

// Handle document management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_document'])) {
        // Validate and handle file upload
        $uploadResult = null;
        $file_name = null;
        $target_file = null;
        
        if (isset($_FILES["file_upload"]) && $_FILES["file_upload"]["error"] !== UPLOAD_ERR_NO_FILE) {
            // Validate file using security class
            $validation = $fileUploadSecurity->validateFile($_FILES["file_upload"]);
            
            if (!$validation['valid']) {
                // Log failed attempt
                $fileUploadSecurity->logUploadAttempt($user_id, $_FILES["file_upload"]["name"] ?? 'unknown', $_FILES["file_upload"]["size"] ?? 0, 'failed', $validation['errors']);
                
                echo "<script>
                    Swal.fire({
                        title: 'ไฟล์ไม่ถูกต้อง!',
                        html: '" . implode('<br>', $validation['errors']) . "',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    </script>";
                exit;
            }
            
            try {
                // Create upload directory
                $uploadDir = $fileUploadSecurity->createUploadDirectory();
                
                // Generate safe file name
                $file_name = $fileUploadSecurity->generateSafeFileName($_FILES["file_upload"]["name"]);
                $target_file = $uploadDir . $file_name;
                
                // Check if it's an image that needs compression
                $fileExtension = $validation['fileInfo']['extension'];
                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png']);
                
                if ($isImage) {
                    // Compress image
                    if ($fileUploadSecurity->compressImage($_FILES["file_upload"]["tmp_name"], $target_file)) {
                        $uploadResult = true;
                    } else {
                        // Fallback to normal upload if compression fails
                        $uploadResult = move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file);
                    }
                } else {
                    // Normal file upload
                    $uploadResult = move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file);
                }
                
                if (!$uploadResult) {
                    throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
                }
                
                // Log successful upload
                $fileUploadSecurity->logUploadAttempt($user_id, $_FILES["file_upload"]["name"], $_FILES["file_upload"]["size"], 'success');
                
            } catch (Exception $e) {
                // Log failed attempt
                $fileUploadSecurity->logUploadAttempt($user_id, $_FILES["file_upload"]["name"] ?? 'unknown', $_FILES["file_upload"]["size"] ?? 0, 'failed', [$e->getMessage()]);
                
                echo "<script>
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: '" . $e->getMessage() . "',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    </script>";
                exit;
            }
        }
        
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

        $file_name = null;
        
        if (!empty($_FILES["file_upload"]["name"]) && $_FILES["file_upload"]["error"] !== UPLOAD_ERR_NO_FILE) {
            // Validate file using security class
            $validation = $fileUploadSecurity->validateFile($_FILES["file_upload"]);
            
            if (!$validation['valid']) {
                // Log failed attempt
                $fileUploadSecurity->logUploadAttempt($user_id, $_FILES["file_upload"]["name"] ?? 'unknown', $_FILES["file_upload"]["size"] ?? 0, 'failed', $validation['errors']);
                
                echo "<script>
                    Swal.fire({
                        title: 'ไฟล์ไม่ถูกต้อง!',
                        html: '" . implode('<br>', $validation['errors']) . "',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    </script>";
                exit;
            }
            
            try {
                // Create upload directory
                $uploadDir = $fileUploadSecurity->createUploadDirectory();
                
                // Generate safe file name
                $file_name = $fileUploadSecurity->generateSafeFileName($_FILES["file_upload"]["name"]);
                $target_file = $uploadDir . $file_name;
                
                // Check if it's an image that needs compression
                $fileExtension = $validation['fileInfo']['extension'];
                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png']);
                
                if ($isImage) {
                    // Compress image
                    if ($fileUploadSecurity->compressImage($_FILES["file_upload"]["tmp_name"], $target_file)) {
                        $uploadResult = true;
                    } else {
                        // Fallback to normal upload if compression fails
                        $uploadResult = move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file);
                    }
                } else {
                    // Normal file upload
                    $uploadResult = move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file);
                }
                
                if (!$uploadResult) {
                    throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
                }
                
                // Log successful upload
                $fileUploadSecurity->logUploadAttempt($user_id, $_FILES["file_upload"]["name"], $_FILES["file_upload"]["size"], 'success');
                
            } catch (Exception $e) {
                // Log failed attempt
                $fileUploadSecurity->logUploadAttempt($user_id, $_FILES["file_upload"]["name"] ?? 'unknown', $_FILES["file_upload"]["size"] ?? 0, 'failed', [$e->getMessage()]);
                
                echo "<script>
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: '" . $e->getMessage() . "',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    </script>";
                exit;
            }
            
            // Update with new file
            $stmt = $pdo->prepare("UPDATE documents SET title = ?, content = ?, status = ?, updated_at = NOW(), category_id = ?, subcategory_id = ?, access_rights = ?, file_name = ?, file_upload = NOW(), file_label = ? WHERE id = ?");
            $stmt->execute([$title, $content, $status, $category_id, $subcategory_id, $access_rights, $file_name, $file_label, $id]);
        } else {
            // Update without new file
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

// Get all years for dropdown
$years_stmt = $pdo->query("SELECT id, year FROM years ORDER BY year DESC");
$years = $years_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories (will be filtered by JavaScript)
$categories = [];
// Fetch all subcategories (will be filtered by JavaScript)
$subcategories = [];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการเอกสาร</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        #documentsTable th, #documentsTable td { 
            white-space: nowrap; 
            word-break: break-word; 
            max-width: 180px; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        @media (max-width: 640px) {
            #documentsTable th, #documentsTable td { 
                max-width: 100px; 
                font-size: 0.9rem; 
            }
        }
        .swal2-popup { font-size: 1.1rem !important; }
        .close {
            cursor: pointer;
        }
        
        /* Modal responsive design */
        .modal-content {
            max-width: 80rem !important;
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
                width: 98vw !important;
                margin: 0 !important;
                padding: 0.5rem !important;
            }
        }
        
        /* Custom Tailwind Dropdown Styles */
        .custom-dropdown {
            position: relative;
        }
        
        .dropdown-button {
            width: 100%;
            min-height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .dropdown-button:hover {
            border-color: #9ca3af;
        }
        
        .dropdown-button:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 0.25rem;
        }
        
        .dropdown-option {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            word-wrap: break-word;
            white-space: normal;
        }
        
        .dropdown-option:hover {
            background-color: #f3f4f6;
        }
        
        .dropdown-option:last-child {
            border-bottom: none;
        }
        
        .dropdown-option.selected {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        
        .dropdown-search {
            padding: 0.5rem 0.75rem;
            border: none;
            border-bottom: 1px solid #e5e7eb;
            width: 100%;
            font-size: 0.875rem;
        }
        
        .dropdown-search:focus {
            outline: none;
            border-bottom-color: #3b82f6;
        }
        
        .dropdown-arrow {
            transition: transform 0.2s;
        }
        
        .dropdown-arrow.rotated {
            transform: rotate(180deg);
        }
        
        /* Modal context adjustments */
        #addModal .custom-dropdown .dropdown-content {
            z-index: 99999;
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .dropdown-content {
                max-height: 150px;
            }
            .dropdown-option {
                font-size: 0.9rem;
                padding: 0.4rem 0.6rem;
            }
        }
        
        /* Ensure modal content has proper overflow handling */
        #addModal .bg-white {
            overflow: visible !important;
        }
        
        /* Ensure modal itself doesn't interfere with dropdowns */
        #addModal {
            overflow: visible !important;
        }
        
        /* File Upload Styles */
        .file-upload-container {
            margin-bottom: 1rem;
        }
        
        .file-drop-zone {
            border: 2px dashed #d1d5db;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }
        
        .file-drop-zone:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        .file-drop-zone.dragover {
            border-color: #3b82f6;
            background-color: #dbeafe;
        }
        
        .file-drop-content {
            pointer-events: none;
        }
        
        .file-info {
            margin-top: 1rem;
        }
        
        .upload-progress {
            margin-top: 1rem;
        }
        
        .progress-bar {
            transition: width 0.3s ease;
        }
        
        /* File type icons */
        .file-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        
        .file-icon.pdf { color: #ef4444; }
        .file-icon.doc, .file-icon.docx { color: #2563eb; }
        .file-icon.xls, .file-icon.xlsx { color: #16a34a; }
        .file-icon.ppt, .file-icon.pptx { color: #ea580c; }
        .file-icon.txt { color: #6b7280; }
        .file-icon.jpg, .file-icon.jpeg, .file-icon.png { color: #8b5cf6; }
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="year_id">ปี:</label>
                        <div class="custom-dropdown" id="year_dropdown">
                            <div class="dropdown-button" onclick="toggleDropdown('year_dropdown')">
                                <span id="year_selected">เลือกปี</span>
                                <svg class="dropdown-arrow w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div class="dropdown-content hidden">
                                <input type="text" class="dropdown-search" placeholder="ค้นหาปี..." onkeyup="filterDropdown('year_dropdown', this.value)">
                                <div class="dropdown-option" data-value="" onclick="selectYear('', 'เลือกปี')">เลือกปี</div>
                                <?php foreach ($years as $year): ?>
                                    <div class="dropdown-option" data-value="<?= $year['id'] ?>" onclick="selectYear('<?= $year['id'] ?>', '<?= htmlspecialchars($year['year']) ?>')"><?= htmlspecialchars($year['year']) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="hidden" id="year_id" name="year_id" required>
                    </div>
                    <div class="form-group">
                        <label for="quarter">ไตรมาส:</label>
                        <div class="custom-dropdown" id="quarter_dropdown">
                            <div class="dropdown-button" onclick="toggleDropdown('quarter_dropdown')">
                                <span id="quarter_selected">เลือกไตรมาส</span>
                                <svg class="dropdown-arrow w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div class="dropdown-content hidden">
                                <div class="dropdown-option" data-value="" onclick="selectQuarter('', 'เลือกไตรมาส')">เลือกไตรมาส</div>
                                <div class="dropdown-option" data-value="1" onclick="selectQuarter('1', 'ไตรมาส 1')">ไตรมาส 1</div>
                                <div class="dropdown-option" data-value="2" onclick="selectQuarter('2', 'ไตรมาส 2')">ไตรมาส 2</div>
                                <div class="dropdown-option" data-value="3" onclick="selectQuarter('3', 'ไตรมาส 3')">ไตรมาส 3</div>
                                <div class="dropdown-option" data-value="4" onclick="selectQuarter('4', 'ไตรมาส 4')">ไตรมาส 4</div>
                            </div>
                        </div>
                        <input type="hidden" id="quarter" name="quarter" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="category_id">หมวดหมู่หลัก:</label>
                    <div class="custom-dropdown" id="category_dropdown">
                        <div class="dropdown-button" onclick="toggleDropdown('category_dropdown')">
                            <span id="category_selected">เลือกปีและไตรมาสก่อน</span>
                            <svg class="dropdown-arrow w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div class="dropdown-content hidden">
                            <input type="text" class="dropdown-search" placeholder="ค้นหาหมวดหมู่..." onkeyup="filterDropdown('category_dropdown', this.value)">
                            <div class="dropdown-option" data-value="" onclick="selectOption('category_dropdown', '', 'เลือกหมวดหมู่หลัก')">กรุณาเลือกปีและไตรมาสก่อน</div>
                        </div>
                    </div>
                    <input type="hidden" id="category_id" name="category_id" required>
                </div>
                <div class="form-group">
                    <label for="subcategory_id">หมวดหมู่ย่อย:</label>
                    <div class="custom-dropdown" id="subcategory_dropdown">
                        <div class="dropdown-button" onclick="toggleDropdown('subcategory_dropdown')">
                            <span id="subcategory_selected">เลือกหมวดหมู่หลักก่อน</span>
                            <svg class="dropdown-arrow w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div class="dropdown-content hidden">
                            <input type="text" class="dropdown-search" placeholder="ค้นหาหมวดหมู่ย่อย..." onkeyup="filterDropdown('subcategory_dropdown', this.value)">
                            <div class="dropdown-option" data-value="" onclick="selectOption('subcategory_dropdown', '', 'เลือกหมวดหมู่ย่อย')">เลือกหมวดหมู่หลักก่อน</div>
                        </div>
                    </div>
                    <input type="hidden" id="subcategory_id" name="subcategory_id" required>
                </div>
                <div class="form-group">
                    <label for="file_upload">เอกสารประกอบ:</label>
                    <div class="file-upload-container">
                        <div class="file-drop-zone" id="fileDropZone">
                            <div class="file-drop-content">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600 mb-2">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</p>
                                <p class="text-sm text-gray-500 mb-4">ไฟล์ที่รองรับ: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG</p>
                                <p class="text-sm text-gray-500">ขนาดไฟล์สูงสุด: <?php echo $fileUploadSecurity->getMaxFileSizeFormatted(); ?></p>
                            </div>
                            <input type="file" class="hidden" id="file_upload" name="file_upload" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png">
                        </div>
                        <div id="fileInfo" class="file-info hidden">
                            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-file text-blue-600 mr-2"></i>
                                    <div>
                                        <p class="font-medium" id="selectedFileName"></p>
                                        <p class="text-sm text-gray-500" id="selectedFileSize"></p>
                                    </div>
                                </div>
                                <button type="button" class="text-red-600 hover:text-red-800" id="removeFile">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div id="uploadProgress" class="upload-progress hidden">
                            <div class="bg-gray-200 rounded-full h-2 mb-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" id="progressBar" style="width: 0%"></div>
                            </div>
                            <p class="text-sm text-gray-600 text-center" id="progressText">กำลังอัปโหลด...</p>
                        </div>
                    </div>
                    <div id="currentFile" class="mt-2"></div>
                </div>
                <div class="form-group">
                    <label for="access_rights">สิทธิ์การเข้าถึง:</label>
                    <div class="custom-dropdown" id="access_rights_dropdown">
                        <div class="dropdown-button" onclick="toggleDropdown('access_rights_dropdown')">
                            <span id="access_rights_selected">เลือกสิทธิ์การเข้าถึง</span>
                            <svg class="dropdown-arrow w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div class="dropdown-content hidden">
                            <div class="dropdown-option" data-value="" onclick="selectOption('access_rights_dropdown', '', 'เลือกสิทธิ์การเข้าถึง')">เลือกสิทธิ์การเข้าถึง</div>
                            <div class="dropdown-option" data-value="public" onclick="selectOption('access_rights_dropdown', 'public', 'Public')">Public</div>
                            <div class="dropdown-option" data-value="private" onclick="selectOption('access_rights_dropdown', 'private', 'Private')">Private</div>
                        </div>
                    </div>
                    <input type="hidden" id="access_rights" name="access_rights">
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

    // Year and Quarter selection functions
    window.selectYear = function(yearId, yearText) {
        selectOption('year_dropdown', yearId, yearText);
        loadCategoriesForYearQuarter();
    };
    
    window.selectQuarter = function(quarter, quarterText) {
        selectOption('quarter_dropdown', quarter, quarterText);
        loadCategoriesForYearQuarter();
    };
    
    window.loadCategoriesForYearQuarter = function() {
        const yearId = $('#year_id').val();
        const quarter = $('#quarter').val();
        
        // Clear categories and subcategories
        clearCategoryDropdown();
        clearSubcategoryDropdown();
        
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
    };
    
    window.updateCategoryDropdown = function(categories) {
        const categoryContent = document.getElementById('category_dropdown').querySelector('.dropdown-content');
        let options = '<input type="text" class="dropdown-search" placeholder="ค้นหาหมวดหมู่..." onkeyup="filterDropdown(\'category_dropdown\', this.value)">';
        options += '<div class="dropdown-option" data-value="" onclick="selectOption(\'category_dropdown\', \'\', \'เลือกหมวดหมู่หลัก\')">เลือกหมวดหมู่หลัก</div>';
        
        categories.forEach(function(category) {
            options += '<div class="dropdown-option" data-value="' + category.id + '" onclick="selectCategory(\'' + category.id + '\', \'' + category.name.replace(/'/g, '&#39;') + '\')">' + category.name + '</div>';
        });
        
        categoryContent.innerHTML = options;
        
        // Reset category selection
        selectOption('category_dropdown', '', 'เลือกหมวดหมู่หลัก');
    };
    
    window.clearCategoryDropdown = function() {
        const categoryContent = document.getElementById('category_dropdown').querySelector('.dropdown-content');
        categoryContent.innerHTML = '<input type="text" class="dropdown-search" placeholder="ค้นหาหมวดหมู่..." onkeyup="filterDropdown(\'category_dropdown\', this.value)"><div class="dropdown-option" data-value="" onclick="selectOption(\'category_dropdown\', \'\', \'เลือกหมวดหมู่หลัก\')">เลือกปีและไตรมาสก่อน</div>';
        selectOption('category_dropdown', '', 'เลือกปีและไตรมาสก่อน');
    };
    
    window.clearSubcategoryDropdown = function() {
        const subcategoryContent = document.getElementById('subcategory_dropdown').querySelector('.dropdown-content');
        subcategoryContent.innerHTML = '<input type="text" class="dropdown-search" placeholder="ค้นหาหมวดหมู่ย่อย..." onkeyup="filterDropdown(\'subcategory_dropdown\', this.value)"><div class="dropdown-option" data-value="" onclick="selectOption(\'subcategory_dropdown\', \'\', \'เลือกหมวดหมู่ย่อย\')">เลือกหมวดหมู่หลักก่อน</div>';
        selectOption('subcategory_dropdown', '', 'เลือกหมวดหมู่หลักก่อน');
    };

    // Custom Dropdown Functions
    window.toggleDropdown = function(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const content = dropdown.querySelector('.dropdown-content');
        const arrow = dropdown.querySelector('.dropdown-arrow');
        
        // Close all other dropdowns
        document.querySelectorAll('.custom-dropdown .dropdown-content').forEach(function(otherContent) {
            if (otherContent !== content) {
                otherContent.classList.add('hidden');
                otherContent.parentElement.querySelector('.dropdown-arrow').classList.remove('rotated');
            }
        });
        
        // Toggle current dropdown
        content.classList.toggle('hidden');
        arrow.classList.toggle('rotated');
        
        // Clear search when opening
        if (!content.classList.contains('hidden')) {
            const searchInput = content.querySelector('.dropdown-search');
            if (searchInput) {
                searchInput.value = '';
                filterDropdown(dropdownId, '');
                searchInput.focus();
            }
        }
    };

    window.selectOption = function(dropdownId, value, text) {
        const dropdown = document.getElementById(dropdownId);
        const selectedSpan = dropdown.querySelector('span[id$="_selected"]');
        const hiddenInput = dropdown.parentElement.querySelector('input[type="hidden"]');
        const content = dropdown.querySelector('.dropdown-content');
        const arrow = dropdown.querySelector('.dropdown-arrow');
        
        // Update display
        selectedSpan.textContent = text;
        hiddenInput.value = value;
        
        // Update selected state
        dropdown.querySelectorAll('.dropdown-option').forEach(option => {
            option.classList.remove('selected');
        });
        const targetOption = dropdown.querySelector(`[data-value="${value}"]`);
        if (targetOption) {
            targetOption.classList.add('selected');
        }
        
        // Close dropdown
        content.classList.add('hidden');
        arrow.classList.remove('rotated');
    };

    window.selectCategory = function(categoryId, categoryName) {
        selectOption('category_dropdown', categoryId, categoryName);
        loadSubcategoriesForCategory(categoryId);
    };
    
    window.loadSubcategoriesForCategory = function(categoryId) {
        if (!categoryId) {
            clearSubcategoryDropdown();
            return $.Deferred().resolve();
        }
        
        return $.post('ajax/get_subcategories.php', {
            category_id: categoryId
        })
        .done(function(response) {
            const data = JSON.parse(response);
            updateSubcategoryDropdown(data.subcategories);
        })
        .fail(function() {
            console.error('Failed to load subcategories');
            clearSubcategoryDropdown();
        });
    };
    
    window.updateSubcategoryDropdown = function(subcategories) {
        const subcategoryContent = document.getElementById('subcategory_dropdown').querySelector('.dropdown-content');
        let options = '<input type="text" class="dropdown-search" placeholder="ค้นหาหมวดหมู่ย่อย..." onkeyup="filterDropdown(\'subcategory_dropdown\', this.value)">';
        options += '<div class="dropdown-option" data-value="" onclick="selectOption(\'subcategory_dropdown\', \'\', \'เลือกหมวดหมู่ย่อย\')">เลือกหมวดหมู่ย่อย</div>';
        
        subcategories.forEach(function(subcategory) {
            options += '<div class="dropdown-option" data-value="' + subcategory.id + '" onclick="selectOption(\'subcategory_dropdown\', \'' + subcategory.id + '\', \'' + subcategory.name.replace(/'/g, '&#39;') + '\')">' + subcategory.name + '</div>';
        });
        
        subcategoryContent.innerHTML = options;
        
        // Reset subcategory selection
        selectOption('subcategory_dropdown', '', 'เลือกหมวดหมู่ย่อย');
    };

    window.filterDropdown = function(dropdownId, searchTerm) {
        const dropdown = document.getElementById(dropdownId);
        const options = dropdown.querySelectorAll('.dropdown-option');
        
        options.forEach(option => {
            const text = option.textContent.toLowerCase();
            const matches = text.includes(searchTerm.toLowerCase());
            option.style.display = matches ? 'block' : 'none';
        });
    };

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            document.querySelectorAll('.custom-dropdown .dropdown-content').forEach(content => {
                content.classList.add('hidden');
                content.parentElement.querySelector('.dropdown-arrow').classList.remove('rotated');
            });
        }
    });

    // Modal functions
    window.openModal = function() {
        $('#addModal').removeClass('hidden');
    };

    window.closeModal = function() {
        $('#addModal').addClass('hidden');
        clearForm();
    };

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
        
        // แสดง loading indicator
        Swal.fire({
            title: 'กำลังโหลดข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // ดึงข้อมูลเอกสารผ่าน AJAX
        $.ajax({
            url: 'fetch_document.php',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response && response.id) {
                    // เติมข้อมูลพื้นฐาน
                    $('#id').val(response.id);
                    $('#title').val(response.title);
                    $('#content').val(response.content);
                    
                    // เติมข้อมูลปีและไตรมาส
                    selectYear(response.year, response.year);
                    selectQuarter(response.quarter, 'ไตรมาส ' + response.quarter);
                    
                    // รอให้ categories โหลดเสร็จแล้วค่อยเติม category และ subcategory
                    setTimeout(function() {
                        loadCategoriesForYearQuarter().then(function() {
                            // เติม category
                            selectCategory(response.category_id, response.category_name);
                            
                            // รอให้ subcategories โหลดเสร็จแล้วค่อยเติม subcategory
                            setTimeout(function() {
                                loadSubcategoriesForCategory(response.category_id).then(function() {
                                    selectOption('subcategory_dropdown', response.subcategory_id, response.subcategory_name);
                                });
                            }, 300);
                        });
                    }, 300);
                    
                    // เติมข้อมูลสิทธิ์การเข้าถึง
                    setDropdownValue('access_rights_dropdown', response.access_rights, getAccessRightsText(response.access_rights));
                    
                    // แสดงไฟล์เดิม
                    if (response.file_name) {
                        $('#currentFile').html('<div class="text-sm text-gray-600">ไฟล์เดิม: <a href="uploads/' + response.file_name + '" target="_blank" class="text-blue-600 underline">' + response.file_name + '</a></div>');
                    } else {
                        $('#currentFile').html('');
                    }
                    
                    // เปลี่ยนปุ่มและ modal title
                    $('#modalTitle').text('แก้ไขเอกสาร');
                    $('#submitBtn').attr('name', 'edit_document').html('<i class="fas fa-save mr-2"></i>บันทึกการแก้ไข');
                    
                    Swal.close();
                    openModal();
                } else {
                    Swal.fire({
                        title: 'ไม่พบข้อมูลเอกสาร',
                        icon: 'error',
                        timer: 1500,
                        showConfirmButton: false
                    });
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
            }
        });
    });

    // Helper function to set dropdown value
    window.setDropdownValue = function(dropdownId, value, text) {
        const dropdown = document.getElementById(dropdownId);
        const selectedSpan = dropdown.querySelector('span[id$="_selected"]');
        const hiddenInput = document.querySelector(`#${dropdownId.replace('_dropdown', '')}`);
        
        // Update display
        if (selectedSpan) selectedSpan.textContent = text || 'เลือก...';
        if (hiddenInput) hiddenInput.value = value;
        
        // Update selected state
        dropdown.querySelectorAll('.dropdown-option').forEach(option => {
            option.classList.remove('selected');
        });
        const targetOption = dropdown.querySelector(`[data-value="${value}"]`);
        if (targetOption) {
            targetOption.classList.add('selected');
        }
    };

    window.getAccessRightsText = function(accessRights) {
        return accessRights === 'public' ? 'Public' : accessRights === 'private' ? 'Private' : 'เลือกสิทธิ์การเข้าถึง';
    };

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
    };

    // Reset form
    window.clearForm = function() {
        $('#id').val('');
        $('#title').val('');
        $('#content').val('');
        
        // Reset dropdowns
        selectOption('year_dropdown', '', 'เลือกปี');
        selectOption('quarter_dropdown', '', 'เลือกไตรมาส');
        selectOption('category_dropdown', '', 'เลือกปีและไตรมาสก่อน');
        selectOption('subcategory_dropdown', '', 'เลือกหมวดหมู่หลักก่อน');
        selectOption('access_rights_dropdown', '', 'เลือกสิทธิ์การเข้าถึง');
        
        // Clear category and subcategory options
        clearCategoryDropdown();
        clearSubcategoryDropdown();
        
        // Reset file upload interface
        $('#file_upload').val('');
        $('#currentFile').html('');
        $('#fileName').text('เลือกไฟล์...');
        
        // Reset new file upload interface
        fileDropZone.classList.remove('hidden');
        fileInfo.classList.add('hidden');
        uploadProgress.classList.add('hidden');
        
        // Reset modal title and button
        $('#modalTitle').text('เพิ่มเอกสาร');
        $('#submitBtn').attr('name', 'add_document').html('<i class="fas fa-save mr-2"></i>บันทึก');
    };
    
    // File upload handler with security validation
    const fileUpload = document.getElementById('file_upload');
    const fileDropZone = document.getElementById('fileDropZone');
    const fileInfo = document.getElementById('fileInfo');
    const selectedFileName = document.getElementById('selectedFileName');
    const selectedFileSize = document.getElementById('selectedFileSize');
    const removeFileBtn = document.getElementById('removeFile');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    // File validation configuration
    const allowedTypes = <?php echo json_encode($fileUploadSecurity->getAllowedTypes()); ?>;
    const maxFileSize = <?php echo $fileUploadSecurity->getMaxFileSize(); ?>;
    const maxFileSizeFormatted = '<?php echo $fileUploadSecurity->getMaxFileSizeFormatted(); ?>';
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Get file extension
    function getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }
    
    // Get file icon class
    function getFileIconClass(extension) {
        const iconMap = {
            'pdf': 'fa-file-pdf',
            'doc': 'fa-file-word',
            'docx': 'fa-file-word',
            'xls': 'fa-file-excel',
            'xlsx': 'fa-file-excel',
            'ppt': 'fa-file-powerpoint',
            'pptx': 'fa-file-powerpoint',
            'txt': 'fa-file-alt',
            'jpg': 'fa-file-image',
            'jpeg': 'fa-file-image',
            'png': 'fa-file-image'
        };
        return iconMap[extension] || 'fa-file';
    }
    
    // Client-side file validation
    function validateFileClient(file) {
        const errors = [];
        
        // Check file size
        if (file.size > maxFileSize) {
            errors.push('ขนาดไฟล์ใหญ่เกินไป (สูงสุด ' + maxFileSizeFormatted + ')');
        }
        
        // Check file type
        const fileExtension = getFileExtension(file.name);
        if (!allowedTypes.includes(fileExtension)) {
            errors.push('ประเภทไฟล์ไม่ถูกต้อง (อนุญาตเฉพาะ: ' + allowedTypes.join(', ').toUpperCase() + ')');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    // Handle file selection
    function handleFileSelect(file) {
        const validation = validateFileClient(file);
        
        if (!validation.valid) {
            Swal.fire({
                title: 'ไฟล์ไม่ถูกต้อง!',
                html: validation.errors.join('<br>'),
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            return;
        }
        
        // Show file info
        const extension = getFileExtension(file.name);
        const iconClass = getFileIconClass(extension);
        
        selectedFileName.innerHTML = '<i class="fas ' + iconClass + ' file-icon ' + extension + '"></i>' + file.name;
        selectedFileSize.textContent = formatFileSize(file.size);
        
        fileDropZone.classList.add('hidden');
        fileInfo.classList.remove('hidden');
        
        // Simulate upload progress (for demo purposes)
        showUploadProgress();
    }
    
    // Show upload progress
    function showUploadProgress() {
        uploadProgress.classList.remove('hidden');
        let progress = 0;
        
        const interval = setInterval(() => {
            progress += Math.random() * 20;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                setTimeout(() => {
                    uploadProgress.classList.add('hidden');
                }, 500);
            }
            
            progressBar.style.width = progress + '%';
            progressText.textContent = 'กำลังอัปโหลด... ' + Math.round(progress) + '%';
        }, 200);
    }
    
    // File input change handler
    fileUpload.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleFileSelect(file);
        }
    });
    
    // Drag and drop handlers
    fileDropZone.addEventListener('click', function() {
        fileUpload.click();
    });
    
    fileDropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileDropZone.classList.add('dragover');
    });
    
    fileDropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileDropZone.classList.remove('dragover');
    });
    
    fileDropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        fileDropZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            fileUpload.files = files;
            handleFileSelect(file);
        }
    });
    
    // Remove file handler
    removeFileBtn.addEventListener('click', function() {
        fileUpload.value = '';
        fileDropZone.classList.remove('hidden');
        fileInfo.classList.add('hidden');
        uploadProgress.classList.add('hidden');
    });
    
    // Form submission validation
    document.getElementById('documentForm').addEventListener('submit', function(e) {
        const file = fileUpload.files[0];
        if (file) {
            const validation = validateFileClient(file);
            if (!validation.valid) {
                e.preventDefault();
                Swal.fire({
                    title: 'ไฟล์ไม่ถูกต้อง!',
                    html: validation.errors.join('<br>'),
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }
        }
    });
    
    // Original file upload handler (keeping for backward compatibility)
    $('#file_upload').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $('#fileName').text(fileName);
        } else {
            $('#fileName').text('เลือกไฟล์...');
        }
    });
});
</script>
</body>
</html>