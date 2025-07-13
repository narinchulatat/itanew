<?php
// การเชื่อมต่อกับฐานข้อมูล
include 'db.php';

$message = '';
$messageType = ''; // ใช้สำหรับกำหนดประเภทของข้อความ

// Pagination settings
$limit = 10; // จำนวนรายการต่อหน้า
$page = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$start = ($page - 1) * $limit;

// นับจำนวนทั้งหมดของผู้ใช้
$total_results = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_pages = ceil($total_results / $limit);

// ดึงข้อมูลผู้ใช้สำหรับหน้าเฉพาะ (join profile เพื่อเอาชื่อ-นามสกุล)
$users = $pdo->prepare("SELECT users.id, username, role_name, role_id, profile.first_name, profile.last_name FROM users JOIN roles ON users.role_id = roles.id LEFT JOIN profile ON users.id = profile.user_id LIMIT :start, :limit");
$users->bindParam(':start', $start, PDO::PARAM_INT);
$users->bindParam(':limit', $limit, PDO::PARAM_INT);
$users->execute();
$users = $users->fetchAll();

// ดึงข้อมูลบทบาททั้งหมด
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role_id = $_POST['role_id'];

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $password, $role_id])) {
            $message = 'User added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error adding user!';
            $messageType = 'error';
        }
    }

    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $role_id = $_POST['role_id'];
        $password = $_POST['password'];

        if (!empty($password)) {
            $password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role_id = ? WHERE id = ?");
            $stmt->execute([$username, $password, $role_id, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, role_id = ? WHERE id = ?");
            $stmt->execute([$username, $role_id, $id]);
        }

        if ($stmt) {
            $message = 'User updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating user!';
            $messageType = 'error';
        }
    }

    if (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'User deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting user!';
            $messageType = 'error';
        }
    }
}
?>
<section class="content-header">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">User Management</h1>
</section>

<!-- Main content -->
<div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-blue-700">จัดการผู้ใช้</h2>
        <button id="addUserBtn" class="bg-green-600 text-white rounded-lg px-5 py-2 font-semibold shadow hover:bg-green-700 transition">เพิ่มผู้ใช้</button>
    </div>
    <!-- Modal แบบ Tailwind CSS -->
    <div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto border border-gray-200">
            <div class="flex justify-between items-center border-b px-8 py-6">
                <h4 class="text-xl font-bold" id="userModalLabel">เพิ่มผู้ใช้</h4>
                <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl close" id="closeModalBtn">&times;</button>
            </div>
            <div class="px-8 py-6">
                <form id="userForm" action="index.php?page=manage_users&pg=<?= $page ?>" method="POST" class="space-y-6">
                    <div>
                        <label for="username" class="block text-base font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-blue-500 focus:outline-none text-base" id="username" name="username" required>
                    </div>
                    <div id="passwordField">
                        <label for="password" class="block text-base font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-blue-500 focus:outline-none text-base" id="password" name="password">
                        <p class="text-xs text-gray-500 mt-1">เว้นว่างไว้หากคุณไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                    </div>
                    <div>
                        <label for="role_id" class="block text-base font-medium text-gray-700 mb-2">Role</label>
                        <select class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-blue-500 focus:outline-none text-base" id="role_id" name="role_id">
                            <?php foreach ($roles as $role) : ?>
                                <option value="<?= $role['id'] ?>"><?= $role['role_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" id="userId" name="id">
                    <button type="submit" name="add_user" class="bg-blue-600 text-white rounded-lg px-4 py-3 font-semibold hover:bg-blue-700 w-full transition text-base" id="userFormSubmitButton">บันทึก</button>
                </form>
            </div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <!-- แสดงตารางข้อมูลผู้ใช้ -->
        <table id="userTable" class="min-w-full divide-y divide-gray-200 mt-4">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Username</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ชื่อ</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">นามสกุล</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                <?php foreach ($users as $user) : ?>
                    <tr class="hover:bg-blue-50 transition">
                        <td class="px-4 py-2 text-sm text-gray-800"><?= $user['id'] ?></td>
                        <td class="px-4 py-2 text-sm text-gray-800"><?= $user['username'] ?></td>
                        <td class="px-4 py-2 text-sm text-gray-800">
                            <?= (!empty($user['first_name'])) ? htmlspecialchars($user['first_name']) : '<span class="text-gray-400">ไม่มีข้อมูล</span>' ?>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-800">
                            <?= (!empty($user['last_name'])) ? htmlspecialchars($user['last_name']) : '<span class="text-gray-400">ไม่มีข้อมูล</span>' ?>
                        </td>
                        <td class="px-4 py-2"><span class="inline-block px-2 py-1 rounded-lg bg-blue-600 text-white text-xs font-semibold shadow"><?= ucwords($user['role_name']) ?></span></td>
                        <td class="px-4 py-2 flex gap-2">
                            <button type="button" class="bg-blue-600 text-white rounded-lg px-3 py-1 font-semibold hover:bg-blue-700 text-sm shadow edit-btn transition" data-id="<?= $user['id'] ?>" data-username="<?= $user['username'] ?>" data-role_id="<?= $user['role_id'] ?>" data-first_name="<?= htmlspecialchars($user['first_name'] ?? '-') ?>" data-last_name="<?= htmlspecialchars($user['last_name'] ?? '-') ?>">
                                แก้ไข
                            </button>
                            <form method="POST" class="delete-form" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="delete_user" value="delete">
                                <button type="button" class="bg-red-600 text-white rounded-lg px-3 py-1 font-semibold hover:bg-red-700 text-sm shadow delete-btn transition">
                                    ลบ
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
// Modal control
const userModal = document.getElementById('userModal');
const addUserBtn = document.getElementById('addUserBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const userForm = document.getElementById('userForm');
const userFormSubmitButton = document.getElementById('userFormSubmitButton');
const userModalLabel = document.getElementById('userModalLabel');
const passwordInput = document.getElementById('password');
const userIdInput = document.getElementById('userId');
const usernameInput = document.getElementById('username');
const roleIdInput = document.getElementById('role_id');
// เพิ่ม field สำหรับชื่อ-นามสกุล (readonly) ใน modal
let firstNameInput = document.getElementById('first_name');
let lastNameInput = document.getElementById('last_name');
if (!firstNameInput || !lastNameInput) {
    const div = document.createElement('div');
    div.className = 'flex gap-4';
    div.innerHTML = `
        <div class="w-1/2">
            <label class="block text-base font-medium text-gray-700 mb-2">ชื่อ</label>
            <input type="text" class="border border-gray-300 rounded-lg px-4 py-3 w-full bg-gray-100 text-gray-500" id="first_name" name="first_name" readonly tabindex="-1">
        </div>
        <div class="w-1/2">
            <label class="block text-base font-medium text-gray-700 mb-2">นามสกุล</label>
            <input type="text" class="border border-gray-300 rounded-lg px-4 py-3 w-full bg-gray-100 text-gray-500" id="last_name" name="last_name" readonly tabindex="-1">
        </div>
    `;
    userForm.insertBefore(div, userForm.children[1]);
    firstNameInput = document.getElementById('first_name');
    lastNameInput = document.getElementById('last_name');
}
addUserBtn.addEventListener('click', function() {
    userForm.reset();
    userForm.action = 'index.php?page=manage_users&pg=<?= $page ?>';
    userModalLabel.textContent = 'เพิ่มผู้ใช้';
    userFormSubmitButton.name = 'add_user';
    userFormSubmitButton.textContent = 'บันทึก';
    passwordInput.style.display = 'block';
    passwordInput.required = true;
    userIdInput.value = '';
    firstNameInput.value = '';
    lastNameInput.value = '';
    firstNameInput.parentElement.parentElement.style.display = 'flex';
    userModal.classList.remove('hidden');
});

closeModalBtn.addEventListener('click', function() {
    userModal.classList.add('hidden');
});

// Edit button event
const editBtns = document.querySelectorAll('.edit-btn');
editBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const username = this.getAttribute('data-username');
        const role_id = this.getAttribute('data-role_id');
        const first_name = this.getAttribute('data-first_name');
        const last_name = this.getAttribute('data-last_name');
        userForm.action = 'index.php?page=manage_users&pg=<?= $page ?>';
        userIdInput.value = id;
        userModalLabel.textContent = 'แก้ไขผู้ใช้';
        usernameInput.value = username;
        roleIdInput.value = role_id;
        userFormSubmitButton.name = 'edit_user';
        userFormSubmitButton.textContent = 'อัปเดต';
        passwordInput.style.display = 'block';
        passwordInput.required = false;
        firstNameInput.value = first_name;
        lastNameInput.value = last_name;
        firstNameInput.readOnly = true;
        lastNameInput.readOnly = true;
        firstNameInput.tabIndex = -1;
        lastNameInput.tabIndex = -1;
        firstNameInput.parentElement.parentElement.style.display = 'flex';
        userModal.classList.remove('hidden');
    });
});

// SweetAlert2 confirm for delete
const deleteBtns = document.querySelectorAll('.delete-btn');
deleteBtns.forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const form = this.closest('.delete-form');
        Swal.fire({
            title: 'คุณต้องการลบผู้ใช้นี้ใช่หรือไม่?',
            text: 'หากลบแล้วจะไม่สามารถกู้คืนได้',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก',
            customClass: {
                popup: 'rounded-xl p-8',
                title: 'font-bold text-lg text-red-700',
                content: 'text-gray-700',
                icon: 'swal2-icon-custom'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

// DataTable (optional, for search/paging only)
$(document).ready(function() {
    $('#userTable').DataTable({
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
            zeroRecords: "ไม่พบข้อมูลที่ต้องการ"
        },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "ทั้งหมด"]],
        pageLength: 10,
        initComplete: function() {
            $('.dataTables_wrapper .dataTables_paginate').css('float', 'right');
            $('.dataTables_wrapper .dataTables_info').css('float', 'left');
            $('.dataTables_wrapper .dataTables_length').css('float', 'left');
            $('.dataTables_wrapper .dataTables_filter').css('float', 'right');
        },
        columns: [
            { title: "ID" },
            { title: "Username" },
            { title: "ชื่อ" },
            { title: "นามสกุล" },
            { title: "Role" },
            { title: "Actions", orderable: false, searchable: false }
        ]
    });
});
</script>

<!-- SweetAlert popup handler -->
<?php if (!empty($message)) : ?>
    <script>
        Swal.fire({
            title: "<?= $message ?>",
            icon: "<?= $messageType == 'success' ? 'success' : 'error' ?>",
            timer: 1500,
            showConfirmButton: false
        }).then(function() {
            window.location.href = 'index.php?page=manage_users&pg=<?= $page ?>';
        });
    </script>
<?php endif; ?>

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
    #userTable th, #userTable td {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        font-size: 0.95rem !important;
    }
    #userTable td, #userTable th {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
    }
}
#userTable td {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    word-break: break-word;
}
@media (max-width: 640px) {
    #userTable td {
        max-width: 100px;
        font-size: 0.9rem;
    }
}
/* Responsive modal width */
@media (max-width: 640px) {
    #userModal .max-w-lg {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0 !important;
        padding: 0.5rem !important;
    }
}
/* Responsive select dropdown */
#role_id {
    white-space: nowrap;
    word-break: break-word;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    font-size: 1rem;
    padding: 0.5rem;
}
#role_id option {
    max-width: 95vw;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    font-size: 1rem;
    padding: 0.25rem 0.5rem;
}
@media (max-width: 640px) {
    #role_id {
        font-size: 0.95rem;
        padding: 0.4rem;
    }
    #role_id option {
        font-size: 0.95rem;
        max-width: 90vw;
    }
}
</style>