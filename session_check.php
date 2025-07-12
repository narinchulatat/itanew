<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>
        Swal.fire({
            icon: 'warning',
            title: 'Please login first',
            showConfirmButton: false,
            timer: 1500
        }).then(function() {
            window.location.href = 'login.php';
        });
    </script>";
    exit;
}
?>
