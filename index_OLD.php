<?php
$page = $_GET['page'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดเก็บเอกสาร (Integrity and Transparency Assessment: ITA) โรงพยาบาลน้ำยืน จังหวัดอุบลราชธานี (ปีงบประมาณ 2568)</title>

    <!-- Google Font: Kanit -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- jQuery 3 -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap.min.js"></script>
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <!-- AdminLTE 2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/skins/_all-skins.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap.min.css">
    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Custom Style for Kanit Font -->
    <style>
        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        ul,
        li,
        div,
        span {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">
        <?php include 'header.php'; ?>
        <?php include 'sidebar.php'; ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- <section class="content"> -->
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                ระบบจัดเก็บเอกสาร (Integrity and Transparency Assessment: ITA) โรงพยาบาลน้ำยืน จังหวัดอุบลราชธานี (ปีงบประมาณ 2568)
                    <small></small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active"></li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <?php
                $file = 'pages/' . $page . '.php';
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "<h1>404 Page Not Found</h1>";
                }
                ?>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="pull-right hidden-xs">
                ITA โรงพยาบาลน้ำยืน
            </div>
            <strong>Copyright &copy; 2024 <a href="#">Developed By นายนรินทร์ จุลทัศน์ ตําแหน่งนักวิชาการคอมพิวเตอร์ปฏิบัติการ โรงพยาบาลน้ำยืน Version 1.1</a>.</strong> All rights reserved.
        </footer>
    </div>

    <!-- Bootstrap 3.3.7 -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/js/adminlte.min.js"></script>
</body>

</html>