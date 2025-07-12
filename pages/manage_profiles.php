<?php
include './db.php';
session_start();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // ใช้ user_id ของผู้ใช้ที่ล็อกอิน
$message = '';

// ดึงข้อมูลโปรไฟล์ของผู้ใช้ที่ล็อกอิน
$stmt = $pdo->prepare("SELECT * FROM profile WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // ตรวจสอบและอัปโหลดรูปภาพ
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";

        // สร้างชื่อไฟล์ใหม่ด้วยการต่อท้าย user_id และเวลาปัจจุบัน
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION); // ดึงนามสกุลไฟล์
        $new_filename = $user_id . '_' . time() . '.' . $ext;
        $target_file = $target_dir . $new_filename;

        move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file);
        $profile_image = $new_filename;
    } else {
        $profile_image = $profile['profile_image'] ?? 'default.png';
    }

    if (isset($_POST['add_profile'])) {
        // เพิ่มข้อมูลโปรไฟล์ใหม่
        $stmt = $pdo->prepare("INSERT INTO profile (user_id, first_name, last_name, email, phone, address, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $first_name, $last_name, $email, $phone, $address, $profile_image]);
        $message = 'Profile added successfully!';
    } elseif (isset($_POST['edit_profile'])) {
        // อัปเดตข้อมูลโปรไฟล์
        $stmt = $pdo->prepare("UPDATE profile SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, profile_image = ? WHERE user_id = ?");
        $stmt->execute([$first_name, $last_name, $email, $phone, $address, $profile_image, $user_id]);
        $message = 'Profile updated successfully!';
    } elseif (isset($_POST['delete_profile'])) {
        // ลบข้อมูลโปรไฟล์
        $stmt = $pdo->prepare("DELETE FROM profile WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $message = 'Profile deleted successfully!';
        $profile = null; // ตั้งค่าโปรไฟล์เป็น null หลังจากลบ
    }
    // ดึงข้อมูลโปรไฟล์ใหม่หลังจากการอัปเดต
    $stmt = $pdo->prepare("SELECT * FROM profile WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    // ใช้ SweetAlert1 ในการแสดงผลข้อความและเปลี่ยนเส้นทาง
    echo "<script>
        Swal.fire({
            title: '$message',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        }).then(function() {
            window.location.href = 'index.php?page=manage_profiles';
        });
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .profile-card {
            border: 1px solid #ddd;
            padding: 20px;
            text-align: center;
            background-color: #f9f9f9;
            border-radius: 10px;
        }

        .profile-card img {
            border-radius: 50%;
            margin-bottom: 15px;
            width: 120px;
            height: 120px;
        }

        .profile-card h3 {
            margin-bottom: 10px;
            font-weight: bold;
        }

        .profile-card p {
            margin-bottom: 20px;
            color: #777;
        }

        .profile-card .list-group-item {
            border: none;
            padding: 5px 0;
        }

        .profile-card .btn-follow {
            margin-top: 20px;
        }

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
@media (max-width: 640px) {
    #profileTable th, #profileTable td {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        font-size: 0.95rem !important;
    }
    #profileTable td, #profileTable th {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
    }
}
#profileTable td {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    word-break: break-word;
}
@media (max-width: 640px) {
    #profileTable td {
        max-width: 100px;
        font-size: 0.9rem;
    }
}
@media (max-width: 640px) {
    #profileModal .max-w-lg {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0 !important;
        padding: 0.5rem !important;
    }
}
#first_name, #last_name, #email, #phone, #address {
    white-space: nowrap;
    word-break: break-word;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    font-size: 1rem;
    padding: 0.5rem;
}
#first_name option, #last_name option, #email option, #phone option, #address option {
    max-width: 95vw;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    font-size: 1rem;
    padding: 0.25rem 0.5rem;
}
@media (max-width: 640px) {
    #first_name, #last_name, #email, #phone, #address {
        font-size: 0.95rem;
        padding: 0.4rem;
    }
    #first_name option, #last_name option, #email option, #phone option, #address option {
        font-size: 0.95rem;
        max-width: 90vw;
    }
}
    </style>
</head>
<body class="bg-gray-100">
    <section class="content-header">
        <h1 class="text-3xl font-bold text-center my-6">จัดการโปรไฟล์</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-blue-700 mb-4">โปรไฟล์ของฉัน</h2>
                <button onclick="openModal()" class="bg-green-600 text-white rounded px-4 py-2 hover:bg-green-700 mb-4">เพิ่มโปรไฟล์</button>
                <!-- Modal แบบ Tailwind CSS -->
                <div id="profileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
                    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
                        <div class="flex justify-between items-center border-b px-6 py-4">
                            <h4 class="text-lg font-bold">เพิ่มโปรไฟล์</h4>
                            <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl close" onclick="closeModal()">&times;</button>
                        </div>
                        <div class="px-6 py-4">
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input type="text" name="first_name" placeholder="ชื่อจริง" class="border rounded px-3 py-2" value="<?= htmlspecialchars($profile['first_name']) ?? '' ?>" required>
                                    <input type="text" name="last_name" placeholder="นามสกุล" class="border rounded px-3 py-2" value="<?= htmlspecialchars($profile['last_name']) ?? '' ?>" required>
                                    <input type="email" name="email" placeholder="อีเมล" class="border rounded px-3 py-2" value="<?= htmlspecialchars($profile['email']) ?? '' ?>" required>
                                    <input type="text" name="phone" placeholder="เบอร์โทร" class="border rounded px-3 py-2" value="<?= htmlspecialchars($profile['phone']) ?? '' ?>">
                                </div>
                                <textarea name="address" placeholder="ที่อยู่" class="border rounded px-3 py-2 w-full"><?= htmlspecialchars($profile['address']) ?? '' ?></textarea>
                                <input type="file" name="profile_image" class="border rounded px-3 py-2 w-full">
                                <?php if ($profile && $profile['profile_image']): ?>
                                    <img src="uploads/<?= $profile['profile_image'] ?>" alt="Current Profile Image" class="img-thumbnail" style="max-width: 150px; margin-top: 10px;">
                                <?php endif; ?>
                                <button type="submit" name="add_profile" class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700 w-full">บันทึก</button>
                            </form>
                        </div>
                    </div>
                </div>
                <script>
                function openModal() {
                    document.getElementById('profileModal').classList.remove('hidden');
                }
                function closeModal() {
                    document.getElementById('profileModal').classList.add('hidden');
                }
                </script>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="first_name" placeholder="ชื่อจริง" class="border rounded px-3 py-2" value="<?= htmlspecialchars($profile['first_name']) ?? '' ?>" required>
                        <input type="text" name="last_name" placeholder="นามสกุล" class="border rounded px-3 py-2" value="<?= htmlspecialchars($profile['last_name']) ?? '' ?>" required>
                        <input type="email" name="email" placeholder="อีเมล" class="border rounded px-3 py-2" value="<?= htmlspecialchars($profile['email']) ?? '' ?>" required>
                        <input type="text" name="phone" placeholder="เบอร์โทร" class="border rounded px-3 py-2" value="<?= htmlspecialchars($profile['phone']) ?? '' ?>">
                    </div>
                    <textarea name="address" placeholder="ที่อยู่" class="border rounded px-3 py-2 w-full"><?= htmlspecialchars($profile['address']) ?? '' ?></textarea>
                    <input type="file" name="profile_image" class="border rounded px-3 py-2 w-full">
                    <?php if ($profile && $profile['profile_image']): ?>
                        <img src="uploads/<?= $profile['profile_image'] ?>" alt="Current Profile Image" class="img-thumbnail" style="max-width: 150px; margin-top: 10px;">
                    <?php endif; ?>
                    <button type="submit" name="<?= $profile ? 'edit_profile' : 'add_profile' ?>" class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700"><?= $profile ? 'อัปเดตโปรไฟล์' : 'สร้างโปรไฟล์' ?></button>
                </form>
                <div class="mt-4">
                    <button class="bg-blue-600 text-white rounded px-3 py-1 hover:bg-blue-700 text-sm edit-btn">แก้ไข</button>
                    <button class="bg-red-600 text-white rounded px-3 py-1 hover:bg-red-700 text-sm delete-btn">ลบ</button>
                </div>
            </div>
        </div>
    </section>
    <script>
$(document).ready(function() {
    var table = $('#profileTable').DataTable({
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
    // ...modal, reset form, SweetAlert2, etc. ตาม manage_subcategories.php...
});
</script>
</body>
</html>
