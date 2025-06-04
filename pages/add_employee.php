<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
require './db.php';

$message = '';

// การจัดการข้อมูลเมื่อส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_code = $_POST['emp_code'];
    $dept_code = $_POST['dept_code'];
    $emp_status = $_POST['emp_status'];
    $flag_show = $_POST['flag_show'];
    $emp_pname = $_POST['emp_pname'];

    // ดึงข้อมูล fname และ lname จาก users โดยใช้ emp_code
    $stmt = $conn->prepare("SELECT fname, lname FROM users WHERE emp_code = ?");
    $stmt->execute([$emp_code]);
    $user = $stmt->fetch();

    if ($user) {
        $emp_fname = $user['fname'];
        $emp_lname = $user['lname'];
    } else {
        $message = 'ไม่พบข้อมูลพนักงาน';
    }

    if (isset($_POST['save'])) {
        // เพิ่มข้อมูลใหม่
        $sql = "INSERT INTO employee (emp_code, dept_code, emp_status, flag_show, emp_fname, emp_lname, emp_pname) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        try {
            $stmt->execute([$emp_code, $dept_code, $emp_status, $flag_show, $emp_fname, $emp_lname, $emp_pname]);
            $message = 'เพิ่มข้อมูลพนักงานสำเร็จ';
        } catch (PDOException $e) {
            $message = 'เกิดข้อผิดพลาด (เพิ่ม): ' . $e->getMessage(); // แสดงข้อความข้อผิดพลาด
        }
    } elseif (isset($_POST['update'])) {
        // แก้ไขข้อมูล
        $sql = "UPDATE employee SET dept_code = ?, emp_status = ?, flag_show = ?, emp_fname = ?, emp_lname = ?, emp_pname = ? WHERE emp_code = ?";
        $stmt = $conn->prepare($sql);
        try {
            $stmt->execute([$dept_code, $emp_status, $flag_show, $emp_fname, $emp_lname, $emp_pname, $emp_code]);
            $message = 'แก้ไขข้อมูลพนักงานสำเร็จ';
        } catch (PDOException $e) {
            $message = 'เกิดข้อผิดพลาด (แก้ไข): ' . $e->getMessage(); // แสดงข้อความข้อผิดพลาด
        }
    }
}

// ดึงข้อมูล dept_code จากตาราง department
// $departments = $conn->query("SELECT dept_code, dept_name FROM department")->fetchAll();
// ดึงข้อมูล dept_code และ dept_name จากตาราง department
$departments = $conn->query("SELECT dept_code, dept_name FROM department")->fetchAll();

// ดึงข้อมูล emp_code, emp_fname, emp_lname จากตาราง users
$users = $conn->query("SELECT emp_code, fname, lname FROM users")->fetchAll();

// ดึงข้อมูล employee ทั้งหมดพร้อมกับชื่อแผนก
$sql = "SELECT e.*, d.dept_name FROM employee e JOIN department d ON e.dept_code = d.dept_code";
$employees = $conn->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลพนักงาน</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
        <!-- Google Fonts: Sarabun -->
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
    <style>
        .swal-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            /* เพิ่มระยะห่างระหว่างฟอร์ม */
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .swal2-input {
            margin: 0;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ced4da;
            font-size: 1rem;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        select.swal2-input {
            width: 100%;
            height: 40px;
        }

        input.swal2-input {
            width: 100%;
            height: 40px;
        }
    </style>
</head>

<body>
    <!-- <div class="container mt-5"> -->
        <h2 class="text-center">จัดการข้อมูลพนักงาน</h2>

        <?php if ($message): ?>
            <div class="alert alert-info text-center"><?= $message; ?></div>
        <?php endif; ?>

        <!-- Footer ตารางแสดงข้อมูล -->
        <div class="card">
            <div class="card-header">
                <h5>ข้อมูลพนักงาน</h5>
            </div>
            <div class="card-body">
                <table id="employeeTable" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>รหัสพนักงาน</th>
                            <th>คำนำหน้า</th> <!-- เพิ่มคำนำหน้า -->
                            <th>ชื่อพนักงาน</th>
                            <th>แผนก</th>
                            <th>สถานะ</th>
                            <th>แสดงในรายงาน</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?= $employee['emp_code']; ?></td>
                                <td><?= $employee['emp_pname']; ?></td> <!-- แสดงคำนำหน้า -->
                                <td><?= $employee['emp_fname'] . ' ' . $employee['emp_lname']; ?></td>
                                <td><?= $employee['dept_name']; ?></td>
                                <td><?= $employee['emp_status'] == 'Y' ? 'ยังทำงานอยู่' : 'ลาออกแล้ว'; ?></td>
                                <td><?= $employee['flag_show'] == 'Y' ? 'แสดง' : 'ไม่แสดง'; ?></td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Button group">
                                        <button class="btn btn-warning btn-sm edit-btn" data-emp-code="<?= $employee['emp_code']; ?>">แก้ไข</button>
                                        <button class="btn btn-danger btn-sm delete" data-id="<?= $employee['emp_code']; ?>">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <!-- </div> -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#employeeTable').DataTable({
                "pageLength": 100 // แสดง 100 รายการต่อหน้า
            });

            // ฟังก์ชันลบพนักงาน
            $('#employeeTable').on('click', '.delete', function() {
                var emp_code = $(this).data('id');
                var row = $(this).closest('tr'); // รับแถวที่ต้องการลบ

                // แสดงการยืนยันก่อนลบ
                Swal.fire({
                    title: 'ยืนยันการลบ',
                    text: "คุณต้องการลบข้อมูลพนักงานนี้ใช่หรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ใช่, ลบเลย!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // ส่งคำขอลบไปยัง server
                        $.ajax({
                            url: 'delete_employee.php',
                            method: 'POST',
                            data: {
                                emp_code: emp_code
                            },
                            success: function(response) {
                                var data = JSON.parse(response);
                                if (data.status === 'success') {
                                    // ลบแถวในตาราง
                                    row.remove();
                                    Swal.fire(
                                        'ลบสำเร็จ!',
                                        'ข้อมูลพนักงานถูกลบเรียบร้อยแล้ว.',
                                        'success'
                                    );
                                } else {
                                    Swal.fire(
                                        'เกิดข้อผิดพลาด!',
                                        data.message,
                                        'error'
                                    );
                                }
                            }
                        });
                    }
                });
            });

            // ดึงข้อมูลแผนกที่มีทั้งหมดในรูปแบบ JSON
            var departments = <?php echo json_encode($departments); ?>;

            $(document).on('click', '.edit-btn', function() {
                var emp_code = $(this).data('emp-code');

                // ดึงข้อมูลพนักงานที่ต้องการแก้ไขผ่าน Ajax
                $.ajax({
                    url: 'get_employee_data.php',
                    method: 'POST',
                    data: {
                        emp_code: emp_code
                    },
                    success: function(response) {
                        var data = JSON.parse(response);

                        // สร้างตัวเลือกแผนกจากข้อมูลที่ได้มา
                        var departmentOptions = '';
                        departments.forEach(function(dept) {
                            var selected = data.dept_code == dept.dept_code ? 'selected' : '';
                            departmentOptions += '<option value="' + dept.dept_code + '" ' + selected + '>' + dept.dept_name + '</option>';
                        });

                        // สร้างตัวเลือกคำนำหน้าจากข้อมูลที่ได้มา
                        var pnameOptions = [{
                                value: '',
                                text: 'เลือกคำนำหน้า'
                            },
                            {
                                value: 'นาย',
                                text: 'นาย'
                            },
                            {
                                value: 'นาง',
                                text: 'นาง'
                            },
                            {
                                value: 'นางสาว',
                                text: 'นางสาว'
                            }
                        ];
                        var pnameSelect = pnameOptions.map(function(pname) {
                            var selected = data.emp_pname == pname.value ? 'selected' : '';
                            return '<option value="' + pname.value + '" ' + selected + '>' + pname.text + '</option>';
                        }).join('');

                        // เปิด SweetAlert ฟอร์มแก้ไขข้อมูลพนักงาน
                        Swal.fire({
                            title: 'แก้ไขข้อมูลพนักงาน',
                            html: '<form id="editForm" class="swal-form">' +
                                '<div class="form-group">' +
                                '<label for="emp_code">รหัสพนักงาน:</label>' +
                                '<input type="text" id="emp_code" class="swal2-input" value="' + data.emp_code + '" readonly>' +
                                '</div>' +
                                '<div class="form-group">' +
                                '<label for="emp_pname">คำนำหน้า:</label>' +
                                '<select id="emp_pname" class="swal2-input">' + pnameSelect + '</select>' +
                                '</div>' +
                                '<div class="form-group">' +
                                '<label for="emp_fname">ชื่อพนักงาน:</label>' +
                                '<input type="text" id="emp_fname" class="swal2-input" value="' + data.emp_fname + '">' +
                                '</div>' +
                                '<div class="form-group">' +
                                '<label for="emp_lname">นามสกุล:</label>' +
                                '<input type="text" id="emp_lname" class="swal2-input" value="' + data.emp_lname + '">' +
                                '</div>' +
                                '<div class="form-group">' +
                                '<label for="dept_code">แผนก:</label>' +
                                '<select id="dept_code" class="swal2-input">' + departmentOptions + '</select>' +
                                '</div>' +
                                '<div class="form-group">' +
                                '<label for="emp_status">สถานะ:</label>' +
                                '<select id="emp_status" class="swal2-input">' +
                                '<option value="Y"' + (data.emp_status == 'Y' ? ' selected' : '') + '>ยังทำงานอยู่</option>' +
                                '<option value="N"' + (data.emp_status == 'N' ? ' selected' : '') + '>ลาออกแล้ว</option>' +
                                '</select>' +
                                '</div>' +
                                '<div class="form-group">' +
                                '<label for="flag_show">แสดงในรายงาน:</label>' +
                                '<select id="flag_show" class="swal2-input">' +
                                '<option value="Y"' + (data.flag_show == 'Y' ? ' selected' : '') + '>แสดง</option>' +
                                '<option value="N"' + (data.flag_show == 'N' ? ' selected' : '') + '>ไม่แสดง</option>' +
                                '</select>' +
                                '</div>' +
                                '</form>',
                            showCancelButton: true,
                            confirmButtonText: 'บันทึก',
                            preConfirm: function() {
                                return {
                                    emp_code: $('#emp_code').val(),
                                    emp_pname: $('#emp_pname').val(), // เพิ่ม emp_pname
                                    emp_fname: $('#emp_fname').val(),
                                    emp_lname: $('#emp_lname').val(),
                                    dept_code: $('#dept_code').val(),
                                    emp_status: $('#emp_status').val(),
                                    flag_show: $('#flag_show').val()
                                };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // ส่งข้อมูลที่แก้ไขกลับไปอัปเดต
                                $.ajax({
                                    url: 'update_employee.php',
                                    method: 'POST',
                                    data: result.value,
                                    success: function(response) {
                                        Swal.fire('บันทึกสำเร็จ', '', 'success').then(() => {
                                            location.reload(); // รีเฟรชหน้าเว็บหลังจากบันทึกสำเร็จ
                                        });
                                    }
                                });
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', status, error);
                    }
                });
            });
        });
    </script>
</body>

</html>