<?php
// ฟังก์ชันแปลงวันที่เป็นภาษาไทย
function thaiDate($date)
{
    $months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];

    $day = date('j', strtotime($date));
    $month = $months[date('n', strtotime($date))];
    $year = date('Y', strtotime($date)) + 543; // เพิ่ม 543 ปี เพื่อให้เป็นปีไทย

    return $day . ' ' . $month . ' ' . $year;
}

// เริ่มต้น session และรวมไฟล์สำหรับการเชื่อมต่อฐานข้อมูล
session_start();
require './db.php';

// กำหนดค่าเริ่มต้นสำหรับการแสดงรายงานเดือนปัจจุบัน
$date_from = date('Y-m-01'); // วันที่เริ่มต้นเป็นวันที่ 1 ของเดือนนี้
$date_to = date('Y-m-t'); // วันที่สิ้นสุดเป็นวันสุดท้ายของเดือนนี้
$emp_code = ''; // ไม่เลือก emp_code เป็นค่าเริ่มต้น
$dept_code = ''; // ไม่เลือก dept_code เป็นค่าเริ่มต้น

// ตรวจสอบว่าผู้ใช้ได้กดปุ่มค้นหาหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['date_from']) || isset($_GET['date_to']))) {
    $date_from = $_GET['date_from'];
    $date_to = $_GET['date_to'];
    $emp_code = isset($_GET['emp_code']) && !empty($_GET['emp_code']) ? $_GET['emp_code'] : null;
    $dept_code = isset($_GET['dept_code']) && !empty($_GET['dept_code']) ? $_GET['dept_code'] : null;
}

// ดึงข้อมูลแผนกทั้งหมดเพื่อนำไปแสดงใน dropdown
$stmt = $conn->prepare("SELECT dept_code, dept_name FROM department ORDER BY dept_name");
$stmt->execute();
$department = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลพนักงานที่ตรงกับแผนกที่เลือกเพื่อนำไปแสดงใน dropdown
if ($dept_code) {
    $stmt = $conn->prepare("SELECT emp_code, CONCAT(emp_fname, ' ', emp_lname) AS emp_name, dept_code FROM employee WHERE dept_code = :dept_code ORDER BY emp_fname");
    $stmt->execute(['dept_code' => $dept_code]);
} else {
    $stmt = $conn->prepare("SELECT emp_code, CONCAT(emp_fname, ' ', emp_lname) AS emp_name, dept_code FROM employee ORDER BY emp_fname");
    $stmt->execute();
}
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// SQL Query ปรับให้เหมาะสมกับการกรองตาม emp_code หรือ dept_code หรือไม่กรองเลย
if ($emp_code) {
    $stmt = $conn->prepare("SELECT e.emp_code, 
                                   CONCAT(emp_pname, emp_fname, ' ', emp_lname) AS emp_name, 
                                   e.emp_position, 
                                   d.dept_name, 
                                   c.create_date, 
                                   c.date_out, -- เพิ่ม date_out ที่นี่
                                   c.schedule_code 
                            FROM calendar c
                            JOIN employee e ON c.emp_code = e.emp_code
                            JOIN department d ON e.dept_code = d.dept_code
                            WHERE e.emp_code = :emp_code 
                              AND c.create_date BETWEEN :date_from AND :date_to");
    $stmt->execute(['emp_code' => $emp_code, 'date_from' => $date_from, 'date_to' => $date_to]);
} elseif ($dept_code) {
    $stmt = $conn->prepare("SELECT e.emp_code, 
                                   CONCAT(emp_pname, emp_fname, ' ', emp_lname) AS emp_name, 
                                   e.emp_position, 
                                   d.dept_name, 
                                   c.create_date, 
                                   c.date_out, -- เพิ่ม date_out ที่นี่
                                   c.schedule_code 
                            FROM calendar c
                            JOIN employee e ON c.emp_code = e.emp_code
                            JOIN department d ON e.dept_code = d.dept_code
                            WHERE e.dept_code = :dept_code 
                              AND c.create_date BETWEEN :date_from AND :date_to");
    $stmt->execute(['dept_code' => $dept_code, 'date_from' => $date_from, 'date_to' => $date_to]);
} else {
    $stmt = $conn->prepare("SELECT e.emp_code, 
                                   CONCAT(emp_pname, emp_fname, ' ', emp_lname) AS emp_name, 
                                   e.emp_position, 
                                   d.dept_name, 
                                   c.create_date, 
                                   c.date_out, -- เพิ่ม date_out ที่นี่
                                   c.schedule_code 
                            FROM calendar c
                            JOIN employee e ON c.emp_code = e.emp_code
                            JOIN department d ON e.dept_code = d.dept_code
                            WHERE c.create_date BETWEEN :date_from AND :date_to");
    $stmt->execute(['date_from' => $date_from, 'date_to' => $date_to]);
}

$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณจำนวนวันที่เลือก
$num_days = (new DateTime($date_from))->diff(new DateTime($date_to))->days + 1;

// เตรียมข้อมูลการเข้าออกงาน
$attendance_data = [];

// จัดระเบียบข้อมูลการเข้าออกงาน
$attendance_data = []; // Initialize the attendance data array

// Loop through each record from the database
$attendance_data = []; // Initialize the attendance data array

// Loop through each record from the database
foreach ($attendance as $record) {
    $day = (int)date('d', strtotime($record['create_date']));
    $create_time = !empty($record['create_date']) ? date('H:i', strtotime($record['create_date'])) : ''; // เวลาเข้า
    $date_out = !empty($record['date_out']) ? date('H:i', strtotime($record['date_out'])) : ''; // เวลาออก
    $status = '';

    // 1. กรณีไม่มีเวลาเข้า (ขาดงาน)
    if (empty($record['create_date'])) {
        $status = 'ข'; // ขาดงาน
    } else {
        // กรณีมีเวลาเข้า แต่ไม่มีเวลาออก
        if (empty($record['date_out'])) {
            // ช่วงเวลาเช้า (06:00 - 08:15)
            if ($create_time >= '06:00' && $create_time <= '08:15') {
                $status = 'ม'; // มา
            }
            // ช่วงเวลาบ่าย (15:45 - 16:15) เปลี่ยนจาก 15:55 เป็น 15:45
            elseif ($create_time >= '15:45' && $create_time <= '16:15') {
                $status = 'ม'; // มา
            }
            // ช่วงเวลาดึก (23:55 - 00:15)
            elseif ($create_time >= '23:55' || $create_time <= '00:15') {
                $status = 'ม'; // มา
            }
            // กรณีเข้าเกินเวลา (สาย)
            elseif ($create_time > '08:15' && $create_time < '15:45') {
                $status = 'ส'; // สาย
            }
        }
        // กรณีมีเวลาเข้าและออก
        else {
            // ช่วงเวลาเช้า (06:00 - 08:15)
            if ($create_time >= '06:00' && $create_time <= '08:15') {
                $status = 'ม/อ'; // มาและออก
            }
            // ช่วงเวลาบ่าย (15:45 - 16:15) เปลี่ยนจาก 15:55 เป็น 15:45
            elseif ($create_time >= '15:45' && $create_time <= '16:15') {
                $status = 'ม/อ'; // มาและออก
            }
            // ช่วงเวลาดึก (23:55 - 00:15)
            elseif ($create_time >= '23:55' || $create_time <= '00:15') {
                $status = 'ม/อ'; // มาและออก
            }
            // กรณีเข้าเกินเวลา (สาย) และออก
            elseif ($create_time > '08:15' && $create_time < '15:45') {
                $status = 'ส/อ'; // สายและออก
            }
        }
    }

    // แสดงสถานะของวันนั้น
    // echo $status;
    // เก็บข้อมูลการเข้าทำงาน
    $attendance_data[$record['emp_code']]['emp_name'] = $record['emp_name'];
    $attendance_data[$record['emp_code']]['dept_name'] = $record['dept_name'];

    // เก็บข้อมูลวันและสถานะ
    if (!isset($attendance_data[$record['emp_code']]['days'][$day])) {
        $attendance_data[$record['emp_code']]['days'][$day] = []; // Initialize the day array if not set
    }

    // เพิ่มข้อมูลสถานะเข้าและออก
    $attendance_data[$record['emp_code']]['days'][$day][] = [
        'status' => $status,
        'create_time' => $create_time,
        'date_out' => $date_out
    ];
}

// แสดงผล
foreach ($attendance_data as $emp_code => $data) {
    // echo "Employee: " . $data['emp_name'] . ", Department: " . $data['dept_name'] . "<br>";
    foreach ($data['days'] as $day => $records) {
        // echo "Day: $day <br>";
        foreach ($records as $record) {
            // echo "Status: " . $record['status'] . ", Time In: " . $record['create_time'] . ", Time Out: " . $record['date_out'] . "<br>";
        }
    }
}

// ฟังก์ชันส่งออกเป็น Excel
if (isset($_GET['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=attendance_report_" . date('Ymd') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // เริ่มต้นสร้าง HTML ตารางที่จะแปลงเป็น Excel
    echo '<table border="1">';

    // หัวตาราง
    echo '<tr>';
    echo '<th>ชื่อพนักงาน</th>';
    echo '<th>แผนก</th>';

    // แสดงจำนวนวันที่จะใส่ในตาราง
    for ($i = 1; $i <= $num_days; $i++) {
        echo '<th>วันที่ ' . $i . '</th>';
    }
    echo '</tr>';

    // ข้อมูลพนักงานและการเข้าออก
    foreach ($attendance_data as $emp_code => $data) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($data['emp_name']) . '</td>';
        echo '<td>' . htmlspecialchars($data['dept_name']) . '</td>';

        // ข้อมูลการเข้าออกตามจำนวนวัน
        for ($i = 1; $i <= $num_days; $i++) {
            echo '<td>';
            // ตรวจสอบว่ามีข้อมูลในวันนั้นหรือไม่
            if (isset($data['days'][$i])) {
                $records = []; // รวบรวมข้อมูลทั้งหมดในวันนั้น
                foreach ($data['days'][$i] as $record) {
                    // ตรวจสอบสถานะและเวลา
                    $status = htmlspecialchars($record['status']);
                    $create_time = date('H:i', strtotime($record['create_time'])); // เวลาเข้า
                    $date_out = !empty($record['date_out']) ? date('H:i', strtotime($record['date_out'])) : ''; // เวลาออก

                    // รวบรวมสถานะและเวลาในรูปแบบ "สถานะ (เวลาเข้า - เวลาออก)"
                    if ($date_out) {
                        // ถ้ามีเวลาออกงาน แสดงในรูปแบบ "สถานะ (เวลาเข้า - เวลาออก)"
                        $records[] = "$status ($create_time - $date_out)";
                    } else {
                        // ถ้าไม่มีเวลาออกงาน แสดงเฉพาะเวลาเข้า
                        $records[] = "$status ($create_time)";
                    }
                }
                // แสดงสถานะทั้งหมดโดยใช้จุลภาคคั่น
                echo implode(', ', $records);
            } else {
                echo ''; // ถ้าไม่มีข้อมูล แสดงช่องว่าง
            }
            echo '</td>';
        }
        echo '</tr>';
    }

    // ปิดตาราง
    echo '</table>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการเข้าออกงานโรงพยาบาลน้ำยืน</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/locales/bootstrap-datepicker.th.min.js"></script>
    <!-- Google Fonts: Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            /* margin: 0; */
            /* padding: 20px; */
            /* เพิ่ม padding ทั้งสี่ด้าน */
            /* background-color: #f4f4f4; */
        }

        h1 {
            font-weight: 700;
            margin-left: 20px;
            /* เพิ่มระยะห่างด้านซ้าย */
        }

        p {
            font-weight: 400;
            margin-left: 20px;
            /* เพิ่มระยะห่างด้านซ้าย */
        }

        /* ปรับแต่งตารางให้ responsive */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* การตั้งค่า CSS สำหรับการพิมพ์ */
        @media print {
            @page {
                size: A4 landscape;
                /* ขนาดกระดาษ A4 แนวนอน */
                margin: 2cm;
                /* กำหนดขนาดมาร์จิ้น */
            }

            body {
                -webkit-print-color-adjust: exact;
                /* รักษาสี */
                margin: 0;
                /* ปรับมาร์จิ้นให้เป็น 0 */
            }

            .header-title {
                display: block;
                /* แสดงหัวข้อ */
                margin-bottom: 10px;
                /* ระยะห่างด้านล่าง */
                font-size: 20px;
                /* ขนาดฟอนต์ */
                text-align: center;
                /* จัดให้อยู่กลาง */
                page-break-after: avoid;
                /* หลีกเลี่ยงการตัดหน้า */
                position: relative;
                /* ใช้ relative แทน fixed */
                width: 100%;
                /* ให้เต็มหน้ากระดาษ */
                z-index: 1000;
                /* อยู่เหนือเนื้อหาอื่น */
            }

            /* เพิ่มระยะห่างด้านบนสำหรับตาราง */
            .table {
                margin-top: 20px;
                /* เพิ่มระยะห่างด้านบนเพื่อหลีกเลี่ยงการทับซ้อน */
                width: 100%;
                /* ปรับขนาดตารางตามความกว้างของหน้า */
                table-layout: auto;
                /* ปรับขนาดคอลัมน์ตามเนื้อหา */
            }

            thead {
                display: table-header-group;
                /* ทำให้หัวตารางแสดงตลอด */
            }

            h2 {
                margin-top: 0;
                /* ไม่มีระยะห่างด้านบน */
                font-size: 24px;
                /* ขนาดฟอนต์สำหรับหัวข้อ */
                text-align: center;
                /* จัดกลางหัวข้อ */
                page-break-after: avoid;
                /* หลีกเลี่ยงการตัดหน้า */
            }

            .table-bordered {
                border: 1px solid #000;
                /* เส้นขอบของตาราง */
            }

            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000;
                /* เส้นขอบเซลล์ */
                padding: 4px;
                /* Padding ของเซลล์ */
                text-align: center;
                /* จัดข้อความในเซลล์ให้กึ่งกลาง */
                font-size: 10px;
                /* ขนาดฟอนต์สำหรับเซลล์ */
                word-wrap: break-word;
                /* ให้ข้อความที่ยาวขึ้นลงบรรทัดใหม่ */
            }

            .emp-name,
            .dept-name {
                font-size: 12px;
                /* ขนาดฟอนต์สำหรับชื่อพนักงานและแผนก */
                width: 20%;
                /* ปรับขนาดช่องให้เล็กลง */
            }

            /* ปรับขนาดของหัวตารางให้ใหญ่ขึ้น */
            .table-bordered th {
                font-size: 12px;
                /* ขนาดฟอนต์ของหัวตาราง */
                background-color: #f0f0f0;
                /* สีพื้นหลังของหัวตาราง */
            }

            /* ซ่อนองค์ประกอบที่ไม่ต้องการในการพิมพ์ */
            form,
            .mt-4>button {
                display: none;
                /* ซ่อนฟอร์มและปุ่ม */
            }

            /* ยกเลิกการทำให้ตาราง responsive เมื่อพิมพ์ */
            .table-responsive {
                overflow: visible;
                /* ยกเลิกการ overflow */
            }

            /* เพิ่มการจัดการแยกหน้าเพื่อให้ header-title แสดงในทุกหน้า */
            .page-break {
                page-break-before: always;
                /* แบ่งหน้าใหม่ */
            }
        }
    </style>
</head>

<body>
    <!-- <div class="container-fluid"> -->
        <div class="header-title">
            <h2>รายงานการเข้าออกงานโรงพยาบาลน้ำยืน ประจำวันที่ <?php echo thaiDate($date_from); ?> ถึง <?php echo thaiDate($date_to); ?></h2>
        </div>

        <form action="" method="get">
            <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>"> <!-- รักษาค่า page -->

            <div class="form-row">
                <!-- เลือกแผนก -->
                <div class="form-group col-md-3">
                    <label for="dept_code">เลือกแผนก</label>
                    <select class="form-control" name="dept_code" id="dept_code" onchange="this.form.submit();">
                        <option value="">เลือกแผนก</option>
                        <?php foreach ($department as $dept): ?>
                            <option value="<?= $dept['dept_code']; ?>" <?= ($dept_code == $dept['dept_code']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- เลือกพนักงาน -->
                <div class="form-group col-md-3">
                    <label for="emp_code">เลือกพนักงาน</label>
                    <select class="form-control" name="emp_code" id="emp_code" <?= empty($dept_code) ? 'disabled' : '' ?>>
                        <option value="">เลือกพนักงาน</option>
                        <?php if (!empty($dept_code)): ?>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['emp_code']; ?>" <?= ($emp_code == $employee['emp_code']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($employee['emp_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- ตั้งแต่วันที่ -->
                <div class="form-group col-md-3">
                    <label for="date_from">ตั้งแต่วันที่</label>
                    <input type="text" class="form-control datepicker" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from); ?>" autocomplete="off" required>
                </div>

                <!-- ถึงวันที่ -->
                <div class="form-group col-md-3">
                    <label for="date_to">ถึงวันที่</label>
                    <input type="text" class="form-control datepicker" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to); ?>" autocomplete="off" required>
                </div>
            </div>

            <!-- ปุ่มสำหรับการดำเนินการต่าง ๆ -->
            <button type="submit" class="btn btn-primary">ดูรายงาน</button>
            <button id="printButton" class="btn btn-info">พิมพ์รายงาน</button>
            <button type="submit" name="export_excel" class="btn btn-success">ส่งออกเป็น Excel</button>
        </form>
        <div class="table-responsive mt-4">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th rowspan="2" class="emp-name">ชื่อพนักงาน</th>
                        <th rowspan="2" class="emp-name">แผนก</th>
                        <th colspan="<?php echo $num_days; ?>">วันที่</th>
                    </tr>
                    <tr>
                        <?php for ($i = 1; $i <= $num_days; $i++) : ?>
                            <th><?php echo $i; ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_data as $emp_code => $data) : ?>
                        <tr>
                            <td class="emp-name"><?php echo htmlspecialchars($data['emp_name']); ?></td>
                            <td class="dept-name"><?php echo htmlspecialchars($data['dept_name']); ?></td>
                            <?php for ($i = 1; $i <= $num_days; $i++) : ?>
                                <td>
                                    <?php
                                    // ตรวจสอบว่ามีข้อมูลในวันนั้นหรือไม่
                                    if (isset($data['days'][$i])) {
                                        $records = []; // รวบรวมข้อมูลทั้งหมดในวันนั้น
                                        foreach ($data['days'][$i] as $record) {
                                            // ตรวจสอบสถานะและเวลา
                                            $status = htmlspecialchars($record['status']);
                                            $create_time = date('H:i', strtotime($record['create_time'])); // เวลาเข้า
                                            $date_out = !empty($record['date_out']) ? date('H:i', strtotime($record['date_out'])) : ''; // เวลาออก

                                            // รวบรวมสถานะและเวลาในรูปแบบ "สถานะ (เวลาเข้า - เวลาออก)"
                                            if ($date_out) {
                                                // ถ้ามีเวลาออกงาน แสดงในรูปแบบ "สถานะ (เวลาเข้า - เวลาออก)"
                                                $records[] = "$status ($create_time - $date_out)";
                                            } else {
                                                // ถ้าไม่มีเวลาออกงาน แสดงเฉพาะเวลาเข้า
                                                $records[] = "$status ($create_time)";
                                            }
                                        }
                                        // แสดงสถานะทั้งหมดโดยใช้จุลภาคคั่น
                                        echo implode(', ', $records);
                                    } else {
                                        echo ''; // ถ้าไม่มีข้อมูล แสดงช่องว่าง
                                    }
                                    ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>




                <!-- <tbody>
                    <?php //foreach ($attendance_data as $emp_code => $data) : 
                    ?>
                        <tr>
                            <td class="emp-name"><?php echo htmlspecialchars($data['emp_name']); ?></td>
                            <td class="emp-name"><?php echo htmlspecialchars($data['dept_name']); ?></td>
                            <?php // for ($i = 1; $i <= $num_days; $i++) : 
                            ?>
                                <td>
                                    <?php
                                    // ตรวจสอบว่ามีข้อมูลในวันนั้นหรือไม่
                                    // if (isset($data['days'][$i])) {
                                    //     $statuses = [];
                                    //     // รวบรวมสถานะทั้งหมดในวันนั้น
                                    //     foreach ($data['days'][$i] as $record) {
                                    //         $statuses[] = htmlspecialchars($record['status']); // ใช้ htmlspecialchars เพื่อป้องกัน XSS
                                    //     }
                                    //     // แสดงสถานะทั้งหมดโดยใช้จุลภาคคั่น
                                    //     echo implode(', ', $statuses);
                                    // } else {
                                    //     echo ''; // ถ้าไม่มีข้อมูล แสดงช่องว่าง
                                    // }
                                    ?>
                                </td>
                            <?php //endfor; 
                            ?>
                        </tr>
                    <?php //endforeach; 
                    ?>
                </tbody> -->
            </table>
        </div>
    <!-- </div> -->

    <script>
        // ตั้งค่าสำหรับ datepicker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            language: 'th',
            autoclose: true,
            todayHighlight: true
        });

        // บังคับให้หน้าต่างการพิมพ์เปิดขึ้นเมื่อผู้ใช้กดปุ่ม "พิมพ์รายงาน"
        document.getElementById('printButton').addEventListener('click', function(event) {
            event.preventDefault(); // ป้องกันการส่งฟอร์ม
            window.print();
        });
    </script>
</body>

</html>