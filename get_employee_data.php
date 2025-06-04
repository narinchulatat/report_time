<?php
require './db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_code = $_POST['emp_code'];

    // ดึงข้อมูลพนักงานจากฐานข้อมูลโดยใช้ emp_code
    $stmt = $conn->prepare("SELECT emp_code, emp_pname, emp_fname, emp_lname, emp_position, dept_code, emp_status, flag_show FROM employee WHERE emp_code = ?");
    $stmt->execute([$emp_code]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC); // ใช้ FETCH_ASSOC เพื่อดึงข้อมูลแบบ associative เท่านั้น

    // ส่งข้อมูลในรูปแบบ JSON
    echo json_encode($employee);
}
?>
