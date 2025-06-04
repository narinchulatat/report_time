<?php
session_start();
include 'db.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ตรวจสอบข้อมูลผู้ใช้
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        echo "<script>
                Swal.fire({
                    title: 'Login Successful!',
                    text: 'Welcome, " . $user['fname'] . "!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'index.php';
                });
              </script>";
    } else {
        echo "<script>
                Swal.fire({
                    title: 'Login Failed!',
                    text: 'Username or password is incorrect!',
                    icon: 'error',
                    confirmButtonText: 'Try Again'
                });
              </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- ตรวจสอบว่ามีการโหลดที่นี่ -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center">Login</h2>
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
    <script>
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST') { ?>
            <?php if ($user && password_verify($password, $user['password'])) { ?>
                Swal.fire({
                    title: 'Login Successful!',
                    text: 'Welcome, <?php echo $user['fname']; ?>!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'index.php';
                });
            <?php } else { ?>
                Swal.fire({
                    title: 'Login Failed!',
                    text: 'Username or password is incorrect!',
                    icon: 'error',
                    confirmButtonText: 'Try Again'
                });
            <?php } ?>
        <?php } ?>
    </script>
</body>
</html>