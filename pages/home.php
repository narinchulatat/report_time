<?php
// ไฟล์: pages/home.php
require './db.php';

// ดึงข้อมูลสถิติต่างๆ
try {
    // จำนวนพนักงานทั้งหมด
    $stmt = $conn->prepare("SELECT COUNT(*) as total_employees FROM employee");
    $stmt->execute();
    $total_employees = $stmt->fetch(PDO::FETCH_ASSOC)['total_employees'];

    // จำนวนแผนกทั้งหมด
    $stmt = $conn->prepare("SELECT COUNT(*) as total_departments FROM department");
    $stmt->execute();
    $total_departments = $stmt->fetch(PDO::FETCH_ASSOC)['total_departments'];

    // จำนวนการเข้างานวันนี้
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT emp_code) as today_attendance FROM calendar WHERE DATE(create_date) = :today");
    $stmt->execute(['today' => $today]);
    $today_attendance = $stmt->fetch(PDO::FETCH_ASSOC)['today_attendance'];

    // จำนวนการเข้างานเดือนนี้
    $this_month = date('Y-m');
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT CONCAT(emp_code, DATE(create_date))) as month_attendance FROM calendar WHERE DATE_FORMAT(create_date, '%Y-%m') = :this_month");
    $stmt->execute(['this_month' => $this_month]);
    $month_attendance = $stmt->fetch(PDO::FETCH_ASSOC)['month_attendance'];

    // พนักงานที่เข้างานวันนี้ (รายชื่อ 5 คนล่าสุด)
    $stmt = $conn->prepare("
        SELECT DISTINCT e.emp_code, CONCAT(e.emp_pname, e.emp_fname, ' ', e.emp_lname) as emp_name, 
               d.dept_name, MIN(c.create_date) as first_check_in
        FROM calendar c
        JOIN employee e ON c.emp_code = e.emp_code
        JOIN department d ON e.dept_code = d.dept_code
        WHERE DATE(c.create_date) = :today
        GROUP BY e.emp_code, e.emp_fname, e.emp_lname, d.dept_name
        ORDER BY first_check_in ASC
        LIMIT 5
    ");
    $stmt->execute(['today' => $today]);
    $recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สถิติการเข้างานรายแผนก
    $stmt = $conn->prepare("
        SELECT d.dept_name, COUNT(DISTINCT c.emp_code) as dept_attendance
        FROM calendar c
        JOIN employee e ON c.emp_code = e.emp_code
        JOIN department d ON e.dept_code = d.dept_code
        WHERE DATE(c.create_date) = :today
        GROUP BY d.dept_code, d.dept_name
        ORDER BY dept_attendance DESC
    ");
    $stmt->execute(['today' => $today]);
    $dept_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สถิติการเข้างาน 7 วันที่ผ่านมา
    $stmt = $conn->prepare("
        SELECT DATE(create_date) as attendance_date, COUNT(DISTINCT emp_code) as daily_count
        FROM calendar 
        WHERE create_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(create_date)
        ORDER BY attendance_date DESC
    ");
    $stmt->execute();
    $weekly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาด
    $total_employees = 0;
    $total_departments = 0;
    $today_attendance = 0;
    $month_attendance = 0;
    $recent_attendance = [];
    $dept_stats = [];
    $weekly_stats = [];
}

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
    $year = date('Y', strtotime($date)) + 543;

    return $day . ' ' . $month . ' ' . $year;
}
?>

<div class="modern-dashboard">
    <!-- Header Section with Current Time -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="hospital-icon">
                <i class="fas fa-hospital-alt"></i>
            </div>
            <h1 class="hero-title">ระบบลงเวลาเข้าออกงาน</h1>
            <h3 class="hero-subtitle">โรงพยาบาลน้ำยืน</h3>
            <div class="date-time-display">
                <div class="current-date"><?php echo thaiDate(date('Y-m-d')); ?></div>
                <div class="current-time" id="current-time"></div>
            </div>
        </div>
        <div class="hero-wave">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M0,0V7.23C0,65.52,268.63,112.77,600,112.77S1200,65.52,1200,7.23V0Z"></path>
            </svg>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($total_employees); ?></div>
                <div class="stat-label">พนักงานทั้งหมด</div>
            </div>
            <div class="stat-trend">
                <i class="fas fa-arrow-up"></i>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($today_attendance); ?></div>
                <div class="stat-label">เข้างานวันนี้</div>
            </div>
            <div class="stat-trend">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($total_departments); ?></div>
                <div class="stat-label">แผนกทั้งหมด</div>
            </div>
            <div class="stat-trend">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($month_attendance); ?></div>
                <div class="stat-label">เข้างานเดือนนี้</div>
            </div>
            <div class="stat-trend">
                <i class="fas fa-calendar"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Grid - ใหม่ เต็มหน้าจอ -->
    <div class="full-width-content">
        <!-- Weekly Chart และ Recent Activity - เต็มหน้าจอ -->
        <div class="dual-section">
            <div class="chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> สถิติการเข้างาน 7 วันที่ผ่านมา</h3>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>

            <div class="activity-card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> พนักงานที่เข้างานวันนี้ล่าสุด</h3>
                </div>
                <div class="activity-content">
                    <?php if (!empty($recent_attendance)): ?>
                        <div class="activity-grid">
                            <?php foreach ($recent_attendance as $emp): ?>
                                <div class="activity-item">
                                    <div class="employee-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="employee-info">
                                        <div class="employee-name"><?php echo htmlspecialchars($emp['emp_name']); ?></div>
                                        <div class="employee-code"><?php echo htmlspecialchars($emp['emp_code']); ?></div>
                                        <div class="employee-dept"><?php echo htmlspecialchars($emp['dept_name']); ?></div>
                                    </div>
                                    <div class="check-time">
                                        <div class="time"><?php echo date('H:i', strtotime($emp['first_check_in'])); ?></div>
                                        <div class="status">
                                            <?php
                                            $check_time = date('H:i', strtotime($emp['first_check_in']));
                                            if ($check_time <= '08:15') {
                                                echo '<span class="status-badge success">ตรงเวลา</span>';
                                            } else {
                                                echo '<span class="status-badge warning">สาย</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-clock"></i>
                            <p>ยังไม่มีพนักงานเข้างานวันนี้</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Department Stats - เต็มหน้าจอ -->
        <div class="dept-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> การเข้างานวันนี้ตามแผนก</h3>
            </div>
            <div class="dept-stats">
                <?php if (!empty($dept_stats)): ?>
                    <?php foreach ($dept_stats as $index => $dept): ?>
                        <div class="dept-item">
                            <div class="dept-info">
                                <div class="dept-name"><?php echo htmlspecialchars($dept['dept_name']); ?></div>
                                <div class="dept-count"><?php echo $dept['dept_attendance']; ?> คน</div>
                            </div>
                            <div class="dept-progress">
                                <div class="progress-bar" style="width: <?php echo ($dept['dept_attendance'] / max(1, $today_attendance)) * 100; ?>%"></div>
                            </div>
                            <div class="dept-percentage"><?php echo round(($dept['dept_attendance'] / max(1, $today_attendance)) * 100); ?>%</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>ยังไม่มีข้อมูลการเข้างานวันนี้</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions - เต็มหน้าจอ -->
        <div class="actions-card">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> การดำเนินการด่วน</h3>
            </div>
            <div class="actions-grid">
                <a href="index.php?page=add_employee" class="action-btn primary">
                    <div class="action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="action-text">เพิ่มพนักงาน</div>
                </a>
                <a href="index.php?page=report" class="action-btn success">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="action-text">ดูรายงาน</div>
                </a>
                <a href="index.php?page=add_user" class="action-btn info">
                    <div class="action-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="action-text">จัดการสมาชิก</div>
                </a>
                <a href="index.php?page=contact" class="action-btn secondary">
                    <div class="action-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="action-text">ติดต่อเรา</div>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-color: #667eea;
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-color: #48bb78;
        --success-gradient: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        --info-color: #4299e1;
        --info-gradient: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        --warning-color: #ed8936;
        --warning-gradient: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        --secondary-color: #718096;
        --secondary-gradient: linear-gradient(135deg, #718096 0%, #4a5568 100%);
        --dark-color: #2d3748;
        --light-color: #f7fafc;
        --border-radius: 20px;
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
        --shadow-md: 0 8px 25px rgba(0, 0, 0, 0.15);
        --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .modern-dashboard {
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 0;
        margin: 0;
    }

    /* Hero Section */
    .hero-section {
        background: var(--primary-gradient);
        color: white;
        padding: 3rem 2rem 6rem;
        position: relative;
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .hero-content {
        max-width: 1200px;
        margin: 0 auto;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .hospital-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.9;
    }

    .hero-title {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .hero-subtitle {
        font-size: 1.5rem;
        font-weight: 400;
        margin-bottom: 2rem;
        opacity: 0.9;
    }

    .date-time-display {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .current-date,
    .current-time {
        background: rgba(255, 255, 255, 0.2);
        padding: 1rem 2rem;
        border-radius: 50px;
        backdrop-filter: blur(10px);
        font-size: 1.1rem;
        font-weight: 500;
    }

    .hero-wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
    }

    .hero-wave svg {
        position: relative;
        display: block;
        width: calc(100% + 1.3px);
        height: 60px;
        fill: var(--light-color);
    }

    /* Statistics Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        max-width: 1200px;
        margin: -4rem auto 3rem;
        padding: 0 2rem;
        position: relative;
        z-index: 10;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
    }

    .stat-card.success::before {
        background: var(--success-gradient);
    }

    .stat-card.info::before {
        background: var(--info-gradient);
    }

    .stat-card.warning::before {
        background: var(--warning-gradient);
    }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        background: var(--primary-gradient);
    }

    .stat-card.success .stat-icon {
        background: var(--success-gradient);
    }

    .stat-card.info .stat-icon {
        background: var(--info-gradient);
    }

    .stat-card.warning .stat-icon {
        background: var(--warning-gradient);
    }

    .stat-content {
        flex: 1;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 1rem;
        color: var(--secondary-color);
        font-weight: 500;
    }

    .stat-trend {
        font-size: 1.2rem;
        color: var(--success-color);
    }

    /* Full Width Content */
    .full-width-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    /* Dual Section for Chart and Activity */
    .dual-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .chart-card,
    .activity-card,
    .dept-card,
    .actions-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem 2rem;
        background: var(--light-color);
        border-bottom: 1px solid #e2e8f0;
    }

    .card-header h3 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .chart-container {
        padding: 2rem;
        height: 350px;
    }

    /* Activity Content */
    .activity-content {
        padding: 2rem;
    }

    .activity-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        background: var(--light-color);
        border-radius: 15px;
        transition: all 0.3s ease;
    }

    .activity-item:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }

    .employee-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }

    .employee-info {
        flex: 1;
    }

    .employee-name {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 0.25rem;
    }

    .employee-code {
        font-size: 0.9rem;
        color: var(--secondary-color);
        margin-bottom: 0.25rem;
    }

    .employee-dept {
        font-size: 0.8rem;
        color: var(--info-color);
    }

    .check-time {
        text-align: right;
    }

    .time {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .status-badge.success {
        background: #c6f6d5;
        color: #22543d;
    }

    .status-badge.warning {
        background: #fed7aa;
        color: #9c4221;
    }

    /* Department Stats */
    .dept-stats {
        padding: 1.5rem 2rem;
    }

    .dept-item {
        display: grid;
        grid-template-columns: 1fr auto auto;
        align-items: center;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .dept-item:last-child {
        border-bottom: none;
    }

    .dept-name {
        font-weight: 600;
        color: var(--dark-color);
    }

    .dept-count {
        font-size: 0.9rem;
        color: var(--secondary-color);
    }

    .dept-progress {
        width: 100px;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: var(--info-gradient);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .dept-percentage {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--info-color);
        min-width: 40px;
        text-align: right;
    }

    /* Actions Grid */
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        padding: 2rem;
    }

    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 2rem 1rem;
        border-radius: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
        background: var(--light-color);
        border: 2px solid transparent;
    }

    .action-btn:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        text-decoration: none;
    }

    .action-btn.primary:hover {
        border-color: var(--primary-color);
    }

    .action-btn.success:hover {
        border-color: var(--success-color);
    }

    .action-btn.info:hover {
        border-color: var(--info-color);
    }

    .action-btn.secondary:hover {
        border-color: var(--secondary-color);
    }

    .action-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        background: var(--primary-gradient);
    }

    .action-btn.success .action-icon {
        background: var(--success-gradient);
    }

    .action-btn.info .action-icon {
        background: var(--info-gradient);
    }

    .action-btn.secondary .action-icon {
        background: var(--secondary-gradient);
    }

    .action-text {
        font-weight: 600;
        color: var(--dark-color);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--secondary-color);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2rem;
        }

        .hero-subtitle {
            font-size: 1.2rem;
        }

        .date-time-display {
            flex-direction: column;
            gap: 1rem;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            margin-top: -3rem;
        }

        .dual-section {
            grid-template-columns: 1fr;
        }

        .actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .stat-number {
            font-size: 2rem;
        }

        .chart-container {
            height: 250px;
        }
    }

    @media (max-width: 480px) {
        .hero-section {
            padding: 2rem 1rem 4rem;
        }

        .stats-grid,
        .full-width-content {
            margin-left: 1rem;
            margin-right: 1rem;
        }

        .actions-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Animations */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stat-card {
        animation: slideInUp 0.6s ease forwards;
    }

    .stat-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .stat-card:nth-child(2) {
        animation-delay: 0.2s;
    }

    .stat-card:nth-child(3) {
        animation-delay: 0.3s;
    }

    .stat-card:nth-child(4) {
        animation-delay: 0.4s;
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
    // Real-time clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('th-TH', {
            timeZone: 'Asia/Bangkok',
            hour12: false
        });

        const clockElement = document.getElementById('current-time');
        if (clockElement) {
            clockElement.textContent = timeString;
        }
    }

    // อัพเดทเวลาทุกวินาที
    setInterval(updateClock, 1000);
    updateClock();

    // Weekly Attendance Chart
    const ctx = document.getElementById('weeklyChart').getContext('2d');
    const weeklyData = <?php echo json_encode(array_reverse($weekly_stats)); ?>;

    const labels = weeklyData.map(item => {
        const date = new Date(item.attendance_date);
        const options = {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            timeZone: 'Asia/Bangkok'
        };
        return date.toLocaleDateString('th-TH', options);
    });

    const data = weeklyData.map(item => parseInt(item.daily_count));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'จำนวนการเข้างาน',
                data: data,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#667eea',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#667eea',
                    borderWidth: 1,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            return 'เข้างาน: ' + context.parsed.y + ' คน';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                        drawTicks: false
                    },
                    ticks: {
                        font: {
                            family: 'Kanit, sans-serif',
                            size: 12
                        },
                        color: '#718096',
                        padding: 10
                    },
                    border: {
                        display: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Kanit, sans-serif',
                            size: 12
                        },
                        color: '#718096',
                        padding: 10
                    },
                    border: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    });

    // Auto refresh page every 5 minutes to update data
    setTimeout(function() {
        location.reload();
    }, 300000); // 5 minutes

    // Add smooth scrolling for action buttons
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Add loading animation for stats cards
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value.toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Animate statistics numbers on page load
    window.addEventListener('load', function() {
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const finalValue = parseInt(stat.textContent.replace(/,/g, ''));
            if (finalValue > 0) {
                stat.textContent = '0';
                setTimeout(() => {
                    animateValue(stat, 0, finalValue, 1500);
                }, 500);
            }
        });
    });

    // Add hover effects for activity items
    document.querySelectorAll('.activity-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.02)';
        });

        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements for scroll animation
    document.querySelectorAll('.chart-card, .dept-card, .activity-card, .actions-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
</script>