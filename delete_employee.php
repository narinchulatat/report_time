<?php
require './db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_code = $_POST['emp_code'];

    // เตรียมคำสั่งลบ
    $sql = "DELETE FROM employee WHERE emp_code = ?";
    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute([$emp_code]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
