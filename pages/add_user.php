<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

include './db.php';
$message = '';
$alert_type = '';

// Fetch departments
$departments = $conn->query("SELECT dept_code, dept_name FROM department")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (emp_code, fname, lname, username, password, offid, offname, status, level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['emp_code'],
            $_POST['fname'],
            $_POST['lname'],
            $_POST['username'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $_POST['offid'],
            $_POST['offname'],
            $_POST['status'],
            $_POST['level']
        ]);

        // Insert into employee table
        $emp_status = 'Y'; // กำหนดสถานะให้เป็น 'Y'
        $flag_show = 'Y'; // กำหนด flag_show ให้เป็น 'Y'

        $sql = "INSERT INTO employee (emp_code, dept_code, emp_status, flag_show, emp_fname, emp_lname, emp_pname) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$emp_code, $dept_code, $emp_status, $flag_show, $emp_fname, $emp_lname, $emp_pname]);
        if (!$result) {
            echo "Error executing query.";
            print_r($stmt->errorInfo()); // แสดงข้อผิดพลาดจากฐานข้อมูล
        }

        $stmt->execute([
            $_POST['emp_code'], // emp_code จากฟอร์ม
            $_POST['offid'], // dept_code
            $emp_status, // ใช้ค่าที่กำหนดเป็น 'Y'
            $flag_show, // ใช้ค่าที่กำหนดเป็น 'Y'
            $_POST['fname'], // fname จากฟอร์ม
            $_POST['lname'], // lname จากฟอร์ม
            '' // ถ้าต้องการค่า emp_pname สามารถกำหนดได้ที่นี่
        ]);

        $message = 'User added successfully!';
        $alert_type = 'success';
    }

    if (isset($_POST['edit'])) {
        if (!empty($_POST['password'])) {
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
            $stmt->execute([$_POST['id']]);
            $hashedPassword = $stmt->fetchColumn();
        }

        $stmt = $conn->prepare("UPDATE users SET emp_code=?, fname=?, lname=?, username=?, password=?, offid=?, offname=?, status=?, level=? WHERE id=?");
        $stmt->execute([
            $_POST['emp_code'],
            $_POST['fname'],
            $_POST['lname'],
            $_POST['username'],
            $hashedPassword,
            $_POST['offid'],
            $_POST['offname'],
            $_POST['status'],
            $_POST['level'],
            $_POST['id']
        ]);
        $message = 'User updated successfully!';
        $alert_type = 'success';
    }

    if (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$_POST['id']]);
        $message = 'User deleted successfully!';
        $alert_type = 'success';
    }
}

// Fetch all users
$users = $conn->query("SELECT * FROM users")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <!-- Google Fonts: Sarabun -->
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        #edit_offname {
            text-align: left;
            /* จัดตำแหน่งข้อความไปทางซ้าย */
            font-weight: normal;
            /* เปลี่ยนให้ข้อความไม่หนา */
            width: calc(100% - 10px);
            /* ปรับขนาดความกว้าง */
        }
    </style>
</head>

<body>
    <!-- <div class="container"> -->
        <h1 class="mt-4">User Management</h1>

        <!-- Add User Form -->
        <div class="card mb-4">
            <div class="card-header">Add User</div>
            <div class="card-body">
                <form method="POST" class="mb-4">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="emp_code" placeholder="Employee Code" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="fname" placeholder="First Name" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="lname" placeholder="Last Name" required>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="username" placeholder="Username" required>
                        </div>
                        <div class="col-md-4">
                            <input type="password" class="form-control" name="password" placeholder="Password" required>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="offid" id="add_offid" required>
                                <option value="">Select Office ID</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= htmlspecialchars($department['dept_code']) ?>"><?= htmlspecialchars($department['dept_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="offname" id="add_offname" placeholder="Office Name" readonly>
                        </div>
                        <!-- <div class="col-md-2">
                            <input type="text" class="form-control" name="status" placeholder="Status (1/0)">
                        </div> -->
                        <div class="col-md-2">
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Y">Y</option>
                                <option value="N">N</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="level" id="add_level" required>
                                <option value="">Select Level</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="add" class="btn btn-primary w-100">Add User</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- User Table -->
        <div class="card mb-4">
            <div class="card-header">User List</div>
            <div class="card-body">
                <table class="table table-bordered" id="userTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Emp Code</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Username</th>
                            <th>Office Name</th>
                            <th>Status</th>
                            <th>Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['emp_code']) ?></td>
                                <td><?= htmlspecialchars($user['fname']) ?></td>
                                <td><?= htmlspecialchars($user['lname']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['offname']) ?></td>
                                <td><?= htmlspecialchars($user['status']) ?></td>
                                <td><?= htmlspecialchars($user['level']) ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick='populateEditForm(<?= json_encode($user) ?>)'>Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg"> <!-- เพิ่มคลาส modal-lg เพื่อทำให้ Modal ใหญ่ขึ้น -->
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="mb-3">
                                <label for="edit_emp_code" class="form-label">Employee Code</label>
                                <input type="text" class="form-control" name="emp_code" id="edit_emp_code" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="edit_fname" class="form-label">First Name</label>
                                <input type="text" class="form-control" name="fname" id="edit_fname" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_lname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="lname" id="edit_lname" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_username" class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" id="edit_username" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="edit_password" class="form-label">Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" name="password" id="edit_password">
                            </div>
                            <div class="mb-3">
                                <label for="edit_offid" class="form-label">Office ID</label>
                                <select class="form-select" name="offid" id="edit_offid" required>
                                    <option value="">Select Office ID</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?= htmlspecialchars($department['dept_code']) ?>" <?= (isset($user) && $user['offid'] == $department['dept_code']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($department['dept_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_offname" class="form-label">Office Name</label>
                                <input type="text" class="form-control" name="offname" id="edit_offname" readonly style="text-align: left; width: 100%;">
                            </div>
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="Y" <?= (isset($user) && $user['status'] == 'Y') ? 'selected' : '' ?>>Y</option>
                                    <option value="N" <?= (isset($user) && $user['status'] == 'N') ? 'selected' : '' ?>>N</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_level" class="form-label">Level</label>
                                <select class="form-select" name="level" id="edit_level" required>
                                    <option value="">Select Level</option>
                                    <option value="admin" <?= (isset($user) && $user['level'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                    <option value="user" <?= (isset($user) && $user['level'] == 'user') ? 'selected' : '' ?>>User</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="edit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $alert_type ?> mt-3">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    <!-- </div> -->

    <!-- Load jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Load Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <!-- Load DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTables
            $('#userTable').DataTable();

            // Populate edit form function
            window.populateEditForm = function(user) {
                // ฟังก์ชันตั้งค่าคุณสมบัติ value เฉพาะถ้ามี
                var setValueIfExists = function(id, value) {
                    var element = document.getElementById(id);
                    if (element) {
                        element.value = value;
                    }
                };

                // ตั้งค่าในฟอร์มแก้ไข
                setValueIfExists('edit_id', user.id);
                setValueIfExists('edit_emp_code', user.emp_code); // Readonly in HTML
                setValueIfExists('edit_fname', user.fname);
                setValueIfExists('edit_lname', user.lname);
                setValueIfExists('edit_username', user.username); // Readonly in HTML
                setValueIfExists('edit_password', ''); // Don't show password
                setValueIfExists('edit_offid', user.offid);

                // อัปเดตชื่อออฟฟิศตาม Office ID
                var officeName = $('#edit_offid option[value="' + user.offid + '"]').text();
                setValueIfExists('edit_offname', officeName);

                setValueIfExists('edit_status_edit', user.status); // อัปเดต ID ที่นี่
                setValueIfExists('edit_level', user.level);

                // แสดงโมดัลสำหรับแก้ไข
                new bootstrap.Modal(document.getElementById('editModal')).show();
            };

            // Update Office Name when Office ID is selected in add form
            $('#add_offid').change(function() {
                var selectedOfficeID = $(this).val();
                var officeName = $('#add_offid option:selected').text();
                $('#add_offname').val(officeName);
            });

            // Update Office Name when Office ID is selected in edit form
            $('#edit_offid').change(function() {
                var selectedOfficeID = $(this).val();
                var officeName = $('#edit_offid option:selected').text();
                $('#edit_offname').val(officeName);
            });
        });

        // ฟังก์ชันยืนยันการลบ
        function confirmDelete() {
            return Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                return result.isConfirmed; // คืนค่าผลลัพธ์ว่าใช่หรือไม่
            });
        }

        // การแสดงข้อความแจ้งเตือนจาก PHP
        <?php if ($message): ?>
            Swal.fire({
                icon: '<?= $alert_type ?>',
                title: '<?= $message ?>',
                showConfirmButton: false,
                timer: 1500
            });
        <?php endif; ?>
    </script>
</body>

</html>