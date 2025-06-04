<?php
$host = 'localhost';
$dbname = 'namyuenh_time_db';
$username = 'namyuenh_time_db';
$password = 'XcsqC5MXbCTRKWvgM6Dx';

// สร้างการเชื่อมต่อฐานข้อมูล
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
//Set ว/ด/ป เวลา ให้เป็นของประเทศไทย
date_default_timezone_set('Asia/Bangkok');
