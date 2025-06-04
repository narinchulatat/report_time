<?php
require './db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_code = $_POST['emp_code'];
    $emp_pname = $_POST['emp_pname']; // เพิ่มคำนำหน้า
    $emp_fname = $_POST['emp_fname'];
    $emp_lname = $_POST['emp_lname'];
    $dept_code = $_POST['dept_code'];
    $emp_status = $_POST['emp_status'];
    $flag_show = $_POST['flag_show'];

    // เตรียมคำสั่ง SQL เพื่ออัปเดตข้อมูลพนักงาน
    $sql = "UPDATE employee SET emp_pname = ?, emp_fname = ?, emp_lname = ?, dept_code = ?, emp_status = ?, flag_show = ? WHERE emp_code = ?";
    $stmt = $conn->prepare($sql);

    try {
        // ดำเนินการอัปเดตข้อมูล
        $stmt->execute([$emp_pname, $emp_fname, $emp_lname, $dept_code, $emp_status, $flag_show, $emp_code]);
        echo json_encode(['status' => 'success', 'message' => 'ข้อมูลพนักงานอัปเดตเรียบร้อยแล้ว']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
}
?>