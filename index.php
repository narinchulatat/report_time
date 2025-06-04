<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// กำหนดค่า page จาก URL ถ้าไม่มีกำหนดค่า default เป็น home
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// จำกัดค่า page ให้รับเฉพาะอักขระที่ปลอดภัย (เช่น a-z, 0-9, และ _)
$page = preg_replace('/[^a-zA-Z0-9_]/', '', $page);

// ไฟล์ที่เราต้องการ include
$file = 'pages/' . $page . '.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($page); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.3.4/dist/sweetalert2.min.css">
    <!-- Google Fonts: Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .content {
            margin-top: 20px;
            /* เว้นระยะจาก Navbar */
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php?page=home">My Application</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=add_user">เพิ่มสมาชิก</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=add_employee">พนักงาน</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=report">รายงาน</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=contact">Contact Us</a>
                        </li>
                    </ul>
                </div>
                <!-- <a href="logout.php" class="btn btn-danger">Logout</a> -->
                <button id="logoutButton" class="btn btn-danger">Logout</button>
            </div>
        </nav>

        <?php
        // ตรวจสอบว่าไฟล์นั้นมีอยู่หรือไม่ และรวมไฟล์ถ้ามี
        if (file_exists($file)) {
            include $file;
        } else {
            echo "<h1>404 Page Not Found</h1>";
        }
        ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.3.4/dist/sweetalert2.all.min.js"></script>

    <!-- Custom JS (ถ้ามี) -->
</body>
<script>
    document.getElementById("logoutButton").onclick = function() {
        Swal.fire({
            title: 'ออกจากระบบ',
            text: "คุณต้องการออกจากระบบใช่ไหม?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, ออกจากระบบ!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // ถ้าผู้ใช้ยืนยัน, เปลี่ยนเส้นทางไปที่ logout.php
                window.location.href = 'logout.php?logout=true';
            }
        });
    };
</script>

</html>