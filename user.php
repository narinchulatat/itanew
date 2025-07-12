<?php
// การเชื่อมต่อกับฐานข้อมูล
include 'db.php';

// กำหนดตัวแปรและตรวจสอบการกระทำ (add, edit, delete)
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role_id = $_POST['role_id'];

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $password, $role_id])) {
            $message = 'User added successfully!';
        } else {
            $message = 'Error adding user!';
        }
    }

    if (isset($_POST['edit_user'])) {
        $username = $_POST['username'];
        $role_id = $_POST['role_id'];

        $stmt = $pdo->prepare("UPDATE users SET username = ?, role_id = ? WHERE id = ?");
        if ($stmt->execute([$username, $role_id, $id])) {
            $message = 'User updated successfully!';
        } else {
            $message = 'Error updating user!';
        }
    }
}

if ($action == 'delete' && !empty($id)) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = 'User deleted successfully!';
    } else {
        $message = 'Error deleting user!';
    }
}

// ดึงข้อมูลผู้ใช้และบทบาททั้งหมดจากฐานข้อมูล
$users = $pdo->query("SELECT users.id, username, role_name FROM users JOIN roles ON users.role_id = roles.id")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();

// ดึงข้อมูลผู้ใช้ที่ต้องการแก้ไข
$userToEdit = null;
if ($action == 'edit' && !empty($id)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $userToEdit = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">User Management</h2>

        <!-- แสดงข้อความแจ้งเตือน -->
        <?php if ($message) : ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- แสดงแบบฟอร์มสำหรับเพิ่มหรือแก้ไขผู้ใช้ -->
        <?php if ($action == 'add' || ($action == 'edit' && $userToEdit)) : ?>
        <form action="index.php<?= $action == 'edit' ? '?action=edit&id=' . $id : '?action=add' ?>" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= $userToEdit['username'] ?? '' ?>" required>
            </div>
            <?php if ($action == 'add') : ?>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label for="role_id" class="form-label">Role</label>
                <select class="form-control" id="role_id" name="role_id">
                    <?php foreach ($roles as $role) : ?>
                        <option value="<?= $role['id'] ?>" <?= ($userToEdit && $userToEdit['role_id'] == $role['id']) ? 'selected' : '' ?>>
                            <?= $role['role_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="<?= $action == 'edit' ? 'edit_user' : 'add_user' ?>" class="btn btn-primary"><?= $action == 'edit' ? 'Update' : 'Add' ?> User</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
        <?php endif; ?>

        <!-- ปุ่มเพิ่มผู้ใช้ -->
        <a href="user.php?action=add" class="btn btn-success mb-3">Add User</a>

        <!-- แสดงตารางข้อมูลผู้ใช้ -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= $user['username'] ?></td>
                    <td><?= $user['role_name'] ?></td>
                    <td>
                        <a href="user.php?action=edit&id=<?= $user['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#confirmDeleteModal" data-id="<?= $user['id'] ?>">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete this user?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <a href="#" class="btn btn-danger" id="confirmDeleteButton">Delete</a>
          </div>
        </div>
      </div>
    </div>

    <!-- JavaScript สำหรับ Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- สคริปต์สำหรับการลบผู้ใช้ -->
    <script>
        $('#confirmDeleteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var deleteUrl = 'user.php?action=delete&id=' + id;
            $('#confirmDeleteButton').attr('href', deleteUrl);
        });
    </script>
</body>
</html>

