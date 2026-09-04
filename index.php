<?php
require_once 'config.php';

$pdo = getDbConnection();

// 1. จัดการการบันทึก/แก้ไข/ลบข้อมูลสถานพยาบาล (เฉพาะ Super Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'save_hospital' && isSuperAdmin()) {
        $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $his_name = trim($_POST['his_name'] ?? '');
        $api_version = trim($_POST['api_version'] ?? '');
        $api_status = trim($_POST['api_status'] ?? 'NORMAL');
        
        // แปลงรูปแบบวันที่จาก datetime-local (YYYY-MM-DDTHH:MM) ให้เป็น MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
        $last_sent_raw = trim($_POST['last_sent_at'] ?? '');
        if (!empty($last_sent_raw)) {
            $last_sent_at = str_replace('T', ' ', $last_sent_raw);
            if (strlen($last_sent_at) === 16) {
                $last_sent_at .= ':00';
            }
        } else {
            $last_sent_at = date('Y-m-d H:i:s');
        }

        $days_count = intval($_POST['days_count'] ?? 0);

        // 🟢 อัปเดต SQL ให้เพิ่ม updated_at = NOW() เพื่อบันทึกเวลาประมวลผลล่าสุดจริงเมื่อกดบันทึกข้อมูล
        if ($id) {
            $stmt = $pdo->prepare("UPDATE hospitals SET code = :code, name = :name, level = :level, his_name = :his_name, api_version = :api_version, api_status = :api_status, last_sent_at = :last_sent_at, days_count = :days_count, updated_at = NOW() WHERE id = :id");
            $stmt->execute(['code' => $code, 'name' => $name, 'level' => $level, 'his_name' => $his_name, 'api_version' => $api_version, 'api_status' => $api_status, 'last_sent_at' => $last_sent_at, 'days_count' => $days_count, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO hospitals (code, name, level, his_name, api_version, api_status, last_sent_at, days_count, updated_at) VALUES (:code, :name, :level, :his_name, :api_version, :api_status, :last_sent_at, :days_count, NOW())");
            $stmt->execute(['code' => $code, 'name' => $name, 'level' => $level, 'his_name' => $his_name, 'api_version' => $api_version, 'api_status' => $api_status, 'last_sent_at' => $last_sent_at, 'days_count' => $days_count]);
        }

        $downDate = date('Y-m-d', strtotime($last_sent_at));

        if ($api_status === 'DOWN') {
            $stmtLog = $pdo->prepare("INSERT INTO downtime_logs (hospital_code, down_date, last_sent_at, days_offline, notes) 
                VALUES (:code, :down_date, :last_sent_at, 1, 'บันทึกอัตโนมัติโดยระบบ Admin') 
                ON DUPLICATE KEY UPDATE last_sent_at = VALUES(last_sent_at), days_offline = 1");
            $stmtLog->execute([
                'code' => $code,
                'down_date' => $downDate,
                'last_sent_at' => $last_sent_at
            ]);
        } else {
            $stmtDelLog = $pdo->prepare("DELETE FROM downtime_logs WHERE hospital_code = :code AND down_date = :down_date");
            $stmtDelLog->execute([
                'code' => $code,
                'down_date' => $downDate
            ]);
        }

        setFlashMessage('อัปเดตข้อมูลสถานพยาบาลและประวัติการ Down เรียบร้อยแล้ว', 'success');
        header("Location: index.php");
        exit;
    }

    elseif ($_POST['action'] === 'delete_hospital' && isSuperAdmin()) {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM hospitals WHERE id = :id");
            $stmt->execute(['id' => $id]);
            setFlashMessage('ลบข้อมูลสถานพยาบาลเรียบร้อยแล้ว', 'success');
        }
        header("Location: index.php");
        exit;
    }
}

// อ่าน Flash Message
$flash = getFlashMessage();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';

// 2. ดึงข้อมูลรายการสถานพยาบาลจากตาราง hospitals
$stmt = $pdo->query("SELECT * FROM hospitals ORDER BY id ASC");
$hospitals = $stmt->fetchAll();

// 3. ดึงข้อมูลประวัติ Downtime จากตาราง downtime_logs โดยตรง
$stmtDowntime = $pdo->query("SELECT * FROM downtime_logs ORDER BY down_date ASC");
$downtimeLogs = $stmtDowntime->fetchAll();

// คำนวณ KPI ภาพรวม
$totalHospitals = count($hospitals);
$normalCount = 0;
$normalLateCount = 0;
$downCount = 0;

// 🟢 ดึงเวลาที่มีการอัปเดตล่าสุดจากการบันทึกข้อมูล (updated_at) หรือเวลาการส่งล่าสุด (last_sent_at)
$stmtLatest = $pdo->query("SELECT MAX(updated_at) AS latest_update, MAX(last_sent_at) AS latest_sent FROM hospitals");
$latestRow = $stmtLatest->fetch();

$rawProcessTime = !empty($latestRow['latest_update']) ? $latestRow['latest_update'] : ($latestRow['latest_sent'] ?? null);

if ($rawProcessTime) {
    $latestProcessTime = date('d/m/Y H:i:s', strtotime($rawProcessTime));
} else {
    $latestProcessTime = date('d/m/Y H:i:s');
}

$hisList = [];
foreach ($hospitals as $h) {
    if ($h['api_status'] === 'NORMAL') $normalCount++;
    if ($h['api_status'] === 'NORMAL-LATE') $normalLateCount++;
    if ($h['api_status'] === 'DOWN') $downCount++;
    if (!in_array($h['his_name'], $hisList)) {
        $hisList[] = $h['his_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการส่งข้อมูล MOPH ERP สถานพยาบาลในจังหวัดนราธิวาส</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Sarabun', 'sans-serif'] },
                    colors: {
                        moph: { 50: '#f0fdf4', 600: '#16a34a', 700: '#15803d', 800: '#166534' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
        .status-dot.green { background-color: #10B981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.5); }
        .status-dot.blue { background-color: #2563EB; box-shadow: 0 0 8px rgba(37, 99, 235, 0.6); }
        .status-dot.red { background-color: #EF4444; box-shadow: 0 0 8px rgba(239, 68, 68, 0.6); animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(0.95); opacity: 0.8; } 50% { transform: scale(1.15); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.8; } }
        .bg-highlight-yellow { background-color: #FEF08A; }
        .bg-highlight-green { background-color: #DCFCE7; }
        .bg-highlight-blue { background-color: #DBEAFE; }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 bg-slate-50 antialiased">

<header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-moph-600 text-white p-2.5 rounded-xl shadow-sm flex items-center justify-center">
                    <i class="fa-solid fa-hospital-user text-xl"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <img src="img/ntwo-logo.png" alt="MOPH Logo" width="24" height="27" decoding="async" loading="lazy" class="h-5 sm:h-6 w-auto object-contain shrink-0">
                        <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">MOPH ERP Narathiwat Dashboard</h1>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">ระบบติดตามการส่งข้อมูล MOPH ERP สถานพยาบาล จ.นราธิวาส</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <?php if (isSuperAdmin()): ?>
                    <div class="flex items-center bg-emerald-50 text-emerald-800 px-3 py-1.5 rounded-lg text-xs font-semibold border border-emerald-200">
                        <i class="fa-solid fa-user-shield mr-1.5 text-emerald-600"></i>
                        <?= e($_SESSION['fullname']) ?> (Super Admin)
                    </div>
                    <a href="logout.php" class="inline-flex items-center px-3.5 py-1.5 border border-transparent text-xs font-semibold rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 transition">
                        <i class="fa-solid fa-right-from-bracket mr-1.5"></i> ออกจากระบบ
                    </a>
                <?php else: ?>
                    <div class="hidden sm:flex items-center bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-200">
                        <i class="fa-solid fa-user mr-1.5 text-slate-400"></i> ผู้เยี่ยมชม (Guest)
                    </div>
                    <button onclick="toggleAuthModal()" class="inline-flex items-center px-3.5 py-1.5 border border-transparent text-xs font-semibold rounded-lg shadow-sm text-white bg-moph-600 hover:bg-moph-700 transition">
                        <i class="fa-solid fa-right-to-bracket mr-1.5"></i> เข้าสู่ระบบ Admin
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<nav class="bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex space-x-2 py-2 overflow-x-auto">
            <button id="tab-dashboard" onclick="switchTab('dashboard')" class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center bg-moph-600 text-white shadow-sm">
                <i class="fa-solid fa-chart-line mr-2"></i> Dashboard ภาพรวม
            </button>
            <button id="tab-downtime" onclick="switchTab('downtime')" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                <i class="fa-solid fa-chart-column mr-2 text-red-500"></i> สถิติการ Down รายเดือน (แยก รพ.)
            </button>
            <a href="bed.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                <i class="fa-solid fa-bed mr-2 text-blue-600"></i> จำนวนเตียงรายสถานพยาบาล
            </a>
            <a href="about.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                <i class="fa-solid fa-circle-info mr-2 text-emerald-600"></i> About
            </a>

            <?php if (isSuperAdmin()): ?>
            <a href="datamirroring.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                <i class="fa-solid fa-database mr-2 text-purple-600"></i> Data Mirroring
            </a>
            <button id="tab-admin" onclick="switchTab('admin')" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                <i class="fa-solid fa-sliders mr-2 text-moph-600"></i> บริหารจัดการข้อมูล (Admin)
            </button>
            <?php endif; ?>
        </div>
    </div>
</nav>

    <?php if ($message): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="p-4 rounded-xl border text-xs font-bold flex items-center justify-between <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200' ?>">
            <div class="flex items-center">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600' ?> text-base mr-2"></i>
                <?= e($message) ?>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
    </div>
    <?php endif; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full">

        <!-- TAB 1: OVERVIEW DASHBOARD -->
        <div id="view-dashboard" class="space-y-6">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500">สถานพยาบาลทั้งหมด</p>
                        <h3 class="text-2xl font-bold text-slate-900 mt-0.5"><?= $totalHospitals ?> <span class="text-sm font-semibold text-slate-700">แห่ง</span></h3>
                        <p class="text-[11px] text-slate-400 mt-1">ครอบคลุมทุก รพ. ใน จ.นราธิวาส</p>
                    </div>
                    <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center text-xl">
                        <i class="fa-solid fa-hospital-user"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500">สถานะปกติ (API OK)</p>
                        <h3 class="text-2xl font-bold text-emerald-600 mt-0.5"><?= $normalCount + $normalLateCount ?> <span class="text-sm font-semibold text-slate-700">แห่ง</span></h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-1 flex items-center">
                            🟢 ปกติ: <?= $normalCount ?> | 🔵 ล่าช้า: <?= $normalLateCount ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500">สถานะ DOWN (มีปัญหา)</p>
                        <h3 class="text-2xl font-bold text-red-600 mt-0.5"><?= $downCount ?> <span class="text-sm font-semibold text-slate-700">แห่ง</span></h3>
                        <p class="text-[11px] text-red-600 font-medium mt-1 flex items-center"><i class="fa-solid fa-triangle-exclamation mr-1"></i> API v. แสดงจุดสีแดง</p>
                    </div>
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-xl flex items-center justify-center text-xl">
                        <i class="fa-solid fa-server"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500">เวลาประมวลผลล่าสุด</p>
                        <h3 class="text-sm font-bold text-slate-800 font-mono mt-1"><?= $latestProcessTime ?></h3>
                        <p class="text-[11px] text-slate-400 mt-1">Process Update</p>
                    </div>
                    <div class="w-12 h-12 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-xl">
                        <i class="fa-solid fa-rotate-right"></i>
                    </div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
                    <div class="relative flex-1 min-w-[220px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="ค้นหารหัส หรือ ชื่อสถานพยาบาล..." class="w-full pl-9 pr-3 py-1.5 border border-slate-300 rounded-lg text-xs outline-none focus:ring-2 focus:ring-moph-500 bg-slate-50">
                    </div>

                    <select id="apiFilter" onchange="filterTable()" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 outline-none focus:ring-2 focus:ring-moph-500 bg-slate-50">
                        <option value="">ทุกสถานะ API</option>
                        <option value="NORMAL">ปกติ (🟢)</option>
                        <option value="NORMAL-LATE">รับข้อมูลช้ากว่า 1 ชม. (🔵)</option>
                        <option value="DOWN">Down / มีปัญหา (🔴)</option>
                    </select>

                    <select id="hisFilter" onchange="filterTable()" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 outline-none focus:ring-2 focus:ring-moph-500 bg-slate-50">
                        <option value="">ทุกระบบ HIS</option>
                        <?php foreach ($hisList as $his): ?>
                            <option value="<?= e($his) ?>"><?= e($his) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <button onclick="resetFilters()" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 transition flex items-center">
                        <i class="fa-solid fa-rotate mr-1.5 text-slate-500"></i> รีเฟรชข้อมูล
                    </button>
                    <button onclick="exportTableToCSV('moph_erp_narathiwat.csv')" class="px-3.5 py-1.5 bg-emerald-900 hover:bg-emerald-950 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center">
                        <i class="fa-solid fa-file-csv mr-1.5 text-emerald-400"></i> ส่งออก CSV
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50/80 flex justify-between items-center">
                    <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center">
                        <i class="fa-solid fa-table-list text-moph-600 mr-2"></i> รายงานการส่งข้อมูล MOPH ERP สถานพยาบาลในจังหวัด นราธิวาส
                    </h3>
                    <span id="visibleCountBadge" class="bg-slate-200 text-slate-700 text-xs px-2.5 py-0.5 rounded-full font-bold">
                        <?= $totalHospitals ?> รายการ
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table id="mophTable" class="w-full text-left border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="bg-slate-100/90 text-slate-700 font-bold border-b border-slate-200 text-xs">
                                <th class="py-3.5 px-4">รหัส</th>
                                <th class="py-3.5 px-4">สถานพยาบาล</th>
                                <th class="py-3.5 px-4">Level</th>
                                <th class="py-3.5 px-4">HIS</th>
                                <th class="py-3.5 px-4 text-center">API v.</th>
                                <th class="py-3.5 px-4 text-center">วันที่ส่งล่าสุด</th>
                                <th class="py-3.5 px-4 text-center">จำนวนวัน</th>
                                <?php if (isSuperAdmin()): ?>
                                <th class="py-3.5 px-4 text-center">จัดการ</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php 
                                $todayDate = date('Y-m-d'); 
                            ?>
                            <?php foreach ($hospitals as $h): ?>
                                <?php 
                                    $dotClass = 'green';
                                    $rowBgClass = 'hover:bg-slate-50/80';

                                    if ($h['api_status'] === 'DOWN') {
                                        $dotClass = 'red';
                                        $rowBgClass = 'bg-highlight-yellow font-semibold';
                                    } elseif ($h['api_status'] === 'NORMAL-LATE') {
                                        $dotClass = 'blue';
                                        $rowBgClass = 'bg-highlight-blue font-semibold';
                                    } elseif (strpos($h['last_sent_at'], '06:59') !== false) {
                                        $rowBgClass = 'bg-highlight-green';
                                    }

                                    $sentDate = !empty($h['last_sent_at']) ? date('Y-m-d', strtotime($h['last_sent_at'])) : '';
                                    $isToday = ($sentDate === $todayDate);

                                    if ($h['api_status'] === 'NORMAL' && !$isToday) {
                                        $dateStyleClass = 'bg-blue-100 text-blue-700 font-bold px-2.5 py-1 rounded-md inline-block';
                                    } else {
                                        $dateStyleClass = 'text-emerald-600 font-bold';
                                    }
                                ?>
                                <tr class="hospital-row <?= $rowBgClass ?> transition" 
                                    data-code="<?= e(strtolower($h['code'])) ?>" 
                                    data-name="<?= e(strtolower($h['name'])) ?>" 
                                    data-status="<?= e($h['api_status']) ?>" 
                                    data-his="<?= e($h['his_name']) ?>">
                                    
                                    <td class="py-3.5 px-4 font-mono font-medium text-slate-800"><?= e($h['code']) ?></td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900"><?= e($h['name']) ?></td>
                                    <td class="py-3.5 px-4 text-slate-700"><?= e($h['level']) ?></td>
                                    <td class="py-3.5 px-4 text-slate-700"><?= e($h['his_name']) ?></td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center font-mono font-bold">
                                            <span class="status-dot <?= $dotClass ?>"></span>
                                            <?= e($h['api_version']) ?>
                                        </span>
                                    </td>
                                    
                                    <td class="py-3.5 px-4 text-center font-mono text-xs whitespace-nowrap">
                                        <span class="<?= $dateStyleClass ?>">
                                            <?= formatThaiDateTime($h['last_sent_at']) ?>
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-red-600"><?= e($h['days_count']) ?></td>
                                    
                                    <?php if (isSuperAdmin()): ?>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <button onclick="editHospital(<?= $h['id'] ?>, '<?= e($h['code']) ?>', '<?= e($h['name']) ?>', '<?= e($h['level']) ?>', '<?= e($h['his_name']) ?>', '<?= e($h['api_version']) ?>', '<?= e($h['api_status']) ?>', '<?= e($h['last_sent_at']) ?>', <?= $h['days_count'] ?>)" class="text-blue-600 hover:text-blue-800 font-bold mr-2">
                                            <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                        </button>
                                        <button onclick="confirmDeleteHospital(<?= $h['id'] ?>)" class="text-red-600 hover:text-red-800 font-bold">
                                            <i class="fa-solid fa-trash-can"></i> ลบ
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php require 'footer.php'; ?>

        </div>

        <!-- TAB 2: MONTHLY DOWNTIME STATS -->
        <div id="view-downtime" class="space-y-6 hidden">
            
            <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-4 flex items-start space-x-3">
                <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5"></i>
                <div class="text-xs text-amber-950 leading-relaxed">
                    <p class="font-bold mb-0.5">เงื่อนไขการตรวจจับและคำนวณวัน Down ของระบบ MOPH ERP</p>
                    <p>• ตรวจจับเมื่อพบ <span class="inline-flex items-center font-semibold text-red-600"><span class="status-dot red inline-block"></span> API v. มีจุดสีแดง</span> หรือระยะเวลาหยุดส่งข้อมูลเกินเกณฑ์มาตรฐาน</p>
                    <p>• ระบบบันทึก <strong>"วันที่ส่งข้อมูลล่าสุด"</strong> ไว้เป็นหลักฐานเพื่อสรุปรายงานสถิติรายเดือนว่า แต่ละสถานพยาบาล Down วันใดบ้าง และคิดเป็นจำนวนกี่วัน</p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <label class="text-xs font-bold text-slate-700 flex items-center">
                        <i class="fa-solid fa-calendar-days text-moph-600 mr-1.5"></i> เลือกเดือนรายงาน:
                    </label>
                    <select id="selectMonth" onchange="renderDowntimeReport(this.value)" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-moph-500 bg-slate-50">
                    </select>
                </div>

                <div class="flex flex-wrap gap-2 text-xs font-medium w-full md:w-auto justify-end">
                    <span class="bg-red-50 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg flex items-center">
                        <i class="fa-solid fa-triangle-exclamation mr-1.5 text-red-500"></i>
                        รวมวัน Down ทั้งหมด: <strong id="kpiTotalDownDays" class="text-xs text-red-800 font-bold ml-1">... วัน</strong>
                    </span>
                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg flex items-center">
                        <i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i>
                        อัตราความพร้อม (Uptime Rate): <strong id="kpiUptimeRate" class="text-xs text-emerald-800 font-bold ml-1">...%</strong>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 pb-2 border-b border-slate-100 gap-2">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                    <i class="fa-solid fa-chart-bar text-red-500 mr-2"></i> สถิติจำนวนวันที่ Down จำแนกตามรายสถานพยาบาล
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">เปรียบเทียบจำนวนวันที่ระบบขัดข้องของแต่ละโรงพยาบาลในเดือนที่เลือก</p>
                            </div>
                            <button onclick="downloadChartImage('downtimeBarChart', 'สถิติจำนวนวันที่_Down_จำแนกตามรายสถานพยาบาล.png')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center shadow-sm">
                                <i class="fa-solid fa-download mr-1.5 text-slate-500"></i> บันทึกรูปภาพ
                            </button>
                        </div>

                        <div id="chart-header-title" class="text-center font-bold text-slate-900 text-xs sm:text-sm my-1">
                            จำนวนวันที่ Down(วัน) จำแนกตามรายสถานพยาบาล
                        </div>

                        <div class="relative h-72 w-full">
                            <canvas id="downtimeBarChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-100">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                    <i class="fa-solid fa-chart-line text-blue-500 mr-2"></i> แนวโน้มวัน Down รวม 3 เดือน
                                </h3>
                            </div>
                        </div>
                        <div class="relative h-56 w-full flex items-center justify-center">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 text-xs space-y-2 bg-slate-50/70 p-3 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600"><i class="fa-solid fa-trophy mr-1.5 text-amber-500"></i> รพ. ที่เสถียรที่สุด:</span>
                            <span id="kpiStableCount" class="font-bold text-emerald-600">... / 13 แห่ง</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600"><i class="fa-solid fa-triangle-exclamation mr-1.5 text-red-500"></i> รพ. ที่ Down มากที่สุด:</span>
                            <span id="kpiTopDownHosp" class="font-bold text-red-600">...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center">
                        <i class="fa-solid fa-calendar-check text-moph-600 mr-2"></i> ตารางรายละเอียดประวัติวันที่ Down รายสถานพยาบาล
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="bg-slate-100/90 text-slate-700 font-bold border-b border-slate-200 text-xs">
                                <th class="py-3.5 px-4">รหัส</th>
                                <th class="py-3.5 px-4">สถานพยาบาล</th>
                                <th class="py-3.5 px-4 text-center">สถานะปัจจุบัน</th>
                                <th class="py-3.5 px-4 text-center">วันที่ส่งล่าสุด</th>
                                <th class="py-3.5 px-4 text-center">จำนวนวัน Down สะสม</th>
                                <th class="py-3.5 px-4">ประวัติวันที่ Down (ระบุวันที่)</th>
                            </tr>
                        </thead>
                        <tbody id="apiDowntimeTableBody" class="divide-y divide-slate-200">
                        </tbody>
                    </table>
                </div>
            </div>

            <?php require 'footer.php'; ?>

        </div>

        <!-- TAB 3: ADMIN MANAGEMENT (SUPER ADMIN ONLY) -->
        <?php if (isSuperAdmin()): ?>
        <div id="view-admin" class="space-y-6 hidden">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 id="form-title" class="text-base font-bold text-slate-900 mb-4 flex items-center">
                    <i class="fa-solid fa-pen-to-square text-moph-600 mr-2"></i> บริหารจัดการข้อมูลสถานพยาบาล (Super Admin)
                </h3>
                <form method="POST" action="index.php" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <input type="hidden" name="action" value="save_hospital">
                    <input type="hidden" name="id" id="form-id">
                    
                    <div>
                        <label class="block font-bold mb-1">รหัสสถานพยาบาล</label>
                        <input type="text" name="code" id="form-code" required class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">ชื่อสถานพยาบาล</label>
                        <input type="text" name="name" id="form-name" required class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">Level</label>
                        <input type="text" name="level" id="form-level" required class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">HIS</label>
                        <input type="text" name="his_name" id="form-his" required class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">API Version</label>
                        <input type="text" name="api_version" id="form-api" required class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block font-bold mb-1">สถานะ API</label>
                        <select name="api_status" id="form-status" class="w-full p-2 border border-slate-300 rounded-lg bg-white">
                            <option value="NORMAL">ปกติ (🟢 จุดสีเขียว)</option>
                            <option value="NORMAL-LATE">ปกติ - ส่งช้ากว่า 1 ชม. (🔵 จุดสีน้ำเงิน)</option>
                            <option value="DOWN">Down / ปัญหา (🔴 จุดสีแดง)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold mb-1">วันที่ส่งล่าสุด <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="last_sent_at" id="form-last-sent" required class="w-full p-2 border border-slate-300 rounded-lg font-mono bg-white text-slate-800 outline-none focus:ring-2 focus:ring-moph-500">
                    </div>

                    <div>
                        <label class="block font-bold mb-1">จำนวนวัน</label>
                        <input type="number" name="days_count" id="form-days" required class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>
                    <div class="md:col-span-3 flex justify-end space-x-2 pt-2">
                        <button type="reset" class="px-4 py-2 border border-slate-300 rounded-lg font-bold text-slate-600 hover:bg-slate-100">ล้างข้อมูล</button>
                        <button type="submit" id="form-submit-btn" class="px-4 py-2 bg-moph-600 text-white rounded-lg font-bold hover:bg-moph-700">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>

            <?php require 'footer.php'; ?>

        </div>
        <?php endif; ?>

    </main>

    <form id="delete-form" method="POST" action="index.php" class="hidden">
        <input type="hidden" name="action" value="delete_hospital">
        <input type="hidden" name="id" id="delete-id">
    </form>

    <div id="modal-auth" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="bg-moph-700 text-white p-6 relative">
                <button onclick="toggleAuthModal()" class="absolute top-4 right-4 text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
                <h3 class="text-lg font-bold">เข้าสู่ระบบ Super Admin</h3>
                <p class="text-xs text-emerald-100 mt-1">จัดการข้อมูลสถานพยาบาลในระบบ MOPH ERP</p>
            </div>
            
            <form method="POST" action="login.php" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-bold mb-1">Username (ชื่อผู้ใช้)</label>
                    <input type="text" name="username" required value="Username (ชื่อผู้ใช้)" class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-moph-500">
                </div>
                <div>
                    <label class="block font-bold mb-1">Password (รหัสผ่าน)</label>
                    <input type="password" name="password" required value="Password (รหัสผ่าน)" class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-moph-500">
                </div>
                <button type="submit" class="w-full py-2.5 bg-moph-600 text-white rounded-lg font-bold text-sm shadow hover:bg-moph-700">ยืนยันเข้าสู่ระบบ</button>
            </form>
        </div>
    </div>

    <script>
        const phpHospitals = <?= json_encode($hospitals, JSON_UNESCAPED_UNICODE) ?>;
        const phpDowntimeLogs = <?= json_encode($downtimeLogs, JSON_UNESCAPED_UNICODE) ?>;

        const thaiMonths = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];

        const shortNamesMap = {
            'EA0010750': 'รพ.นราธิวาสราชนครินทร์',
            'EA0010751': 'รพ.สุไหงโก-ลก',
            'EA0011435': 'รพ.ตากใบ',
            'EA0011436': 'รพ.บาเจาะ',
            'EA0011437': 'รพ.ระแงะ',
            'EA0011438': 'รพ.รือเสาะ',
            'EA0011439': 'รพ.ศรีสาคร',
            'EA0011440': 'รพ.แว้ง',
            'EA0011441': 'รพ.สุคิริน',
            'EA0011442': 'รพ.สุไหงปาดี',
            'EA0013818': 'รพ.จะแนะ',
            'EA0015010': 'รพ.เจาะไอร้อง',
            'EA0023771': 'รพ.ยี่งอ'
        };

        let barChartInstance = null;
        let trendChartInstance = null;

        // Custom Plugin สำหรับวาดตัวเลขเหนือยอดแท่งกราฟแบบชัดเจนไม่โดนขอบตัด
        const barDataLabelsPlugin = {
            id: 'barDataLabels',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    meta.data.forEach((bar, index) => {
                        const value = dataset.data[index];
                        if (value > 0) {
                            ctx.save();
                            ctx.font = 'bold 13px Sarabun, sans-serif';
                            ctx.fillStyle = '#1e293b';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillText(value, bar.x, bar.y - 4);
                            ctx.restore();
                        }
                    });
                });
            }
        };

        function downloadChartImage(chartId, filename) {
            const canvas = document.getElementById(chartId);
            if (!canvas) return;

            const headerHeight = 45;
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height + headerHeight;
            const tempCtx = tempCanvas.getContext('2d');

            tempCtx.fillStyle = '#ffffff';
            tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

            tempCtx.font = 'bold 15px Sarabun, sans-serif';
            tempCtx.fillStyle = '#0f172a';
            tempCtx.textAlign = 'center';
            tempCtx.textBaseline = 'top';
            tempCtx.fillText('จำนวนวันที่ Down(วัน) จำแนกตามรายสถานพยาบาล', tempCanvas.width / 2, 12);

            tempCtx.drawImage(canvas, 0, headerHeight);

            const imageURI = tempCanvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = filename;
            link.href = imageURI;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function initMonthOptions() {
            const selectEl = document.getElementById('selectMonth');
            if (!selectEl) return;

            let monthsSet = new Set();

            monthsSet.add('2026-08');
            const now = new Date();
            const curY = now.getFullYear();
            const curM = String(now.getMonth() + 1).padStart(2, '0');
            monthsSet.add(`${curY}-${curM}`);

            phpDowntimeLogs.forEach(log => {
                if (log.down_date && log.down_date.length >= 7) {
                    monthsSet.add(log.down_date.substring(0, 7));
                }
            });

            phpHospitals.forEach(h => {
                if (h.last_sent_at && h.last_sent_at.length >= 7) {
                    monthsSet.add(h.last_sent_at.substring(0, 7));
                }
            });

            const sortedMonths = Array.from(monthsSet).sort().reverse();
            selectEl.innerHTML = '';

            sortedMonths.forEach(ym => {
                const parts = ym.split('-');
                if (parts.length === 2) {
                    const y = parseInt(parts[0]);
                    const m = parseInt(parts[1]);
                    const thYear = y + 543;
                    const thMonth = thaiMonths[m - 1];
                    const opt = document.createElement('option');
                    opt.value = ym;
                    opt.textContent = `${thMonth} ${thYear} (${ym})`;
                    selectEl.appendChild(opt);
                }
            });

            if (sortedMonths.length > 0) {
                selectEl.value = sortedMonths[0];
            }
        }

        function switchTab(tab) {
            const tabs = ['dashboard', 'downtime', 'admin'];
            tabs.forEach(t => {
                const el = document.getElementById('view-' + t);
                const btn = document.getElementById('tab-' + t);
                if (el) el.classList.toggle('hidden', t !== tab);
                if (btn) {
                    if (t === tab) {
                        btn.className = 'px-4 py-2 rounded-lg text-xs font-bold transition flex items-center bg-moph-600 text-white shadow-sm';
                    } else {
                        btn.className = 'px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center';
                    }
                }
            });

            if (tab === 'downtime') {
                const currentMonth = document.getElementById('selectMonth').value;
                renderDowntimeReport(currentMonth);
            }
        }

        function toggleAuthModal() {
            document.getElementById('modal-auth').classList.toggle('hidden');
        }

        function filterTable() {
            const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
            const apiVal = document.getElementById('apiFilter').value;
            const hisVal = document.getElementById('hisFilter').value;
            
            const rows = document.querySelectorAll('.hospital-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const code = row.getAttribute('data-code');
                const name = row.getAttribute('data-name');
                const status = row.getAttribute('data-status');
                const his = row.getAttribute('data-his');

                const matchesSearch = (code.includes(searchVal) || name.includes(searchVal));
                const matchesApi = (apiVal === '' || status === apiVal);
                const matchesHis = (hisVal === '' || his === hisVal);

                if (matchesSearch && matchesApi && matchesHis) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCountBadge').textContent = visibleCount + ' รายการ';
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('apiFilter').value = '';
            document.getElementById('hisFilter').value = '';
            filterTable();
        }

        function exportTableToCSV(filename) {
            const rows = document.querySelectorAll('#mophTable tr');
            let csv = [];
            
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    let rowData = [];
                    const cols = row.querySelectorAll('th, td');
                    cols.forEach(col => {
                        let text = col.innerText.replace(/\n/g, ' ').trim();
                        text = '"' + text.replace(/"/g, '""') + '"';
                        rowData.push(text);
                    });
                    csv.push(rowData.join(','));
                }
            });

            const csvString = '\uFEFF' + csv.join('\n');
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (navigator.msSaveBlob) {
                navigator.msSaveBlob(blob, filename);
            } else {
                link.href = URL.createObjectURL(blob);
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function renderDowntimeReport(selectedMonth) {
            const tbody = document.getElementById('apiDowntimeTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';

            let totalMonthDownDays = 0;
            let stableHospCount = 0;
            let topDownHospName = 'ไม่มี';
            let maxDownDays = 0;

            let chartLabels = [];
            let chartData = [];
            let chartColors = [];

            phpHospitals.forEach(h => {
                const code = h.code;
                const shortName = shortNamesMap[code] || h.name.replace('โรงพยาบาล', 'รพ.').replace('เฉลิมพระเกียรติ 80 พรรษา', '');
                
                const hospLogs = phpDowntimeLogs.filter(log => log.hospital_code === code && log.down_date.startsWith(selectedMonth));
                
                let downDays = 0;
                let downDates = [];

                if (hospLogs.length > 0) {
                    hospLogs.forEach(log => {
                        downDays += 1;
                        const dParts = log.down_date.split('-');
                        if (dParts.length === 3) {
                            downDates.push(`${dParts[2]}/${dParts[1]}/${dParts[0]}`);
                        }
                    });
                } else if (h.api_status === 'DOWN' && h.last_sent_at.startsWith(selectedMonth)) {
                    downDays = 1;
                    const dParts = h.last_sent_at.substring(0, 10).split('-');
                    if (dParts.length === 3) {
                        downDates.push(`${dParts[2]}/${dParts[1]}/${dParts[0]}`);
                    }
                }

                totalMonthDownDays += downDays;

                if (downDays > maxDownDays) {
                    maxDownDays = downDays;
                    topDownHospName = shortName + ` (${downDays} วัน)`;
                }

                if (downDays === 0) {
                    stableHospCount++;
                }

                chartLabels.push(shortName);
                chartData.push(downDays);
                chartColors.push(downDays > 0 ? '#EF4444' : '#E2E8F0');

                const tr = document.createElement('tr');
                tr.className = 'border-b border-slate-100 hover:bg-slate-50/80 transition';

                let statusBadge = `<span class="inline-flex items-center px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold"><span class="status-dot green"></span> ปกติ</span>`;
                if (h.api_status === 'DOWN') {
                    statusBadge = `<span class="inline-flex items-center px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-bold"><span class="status-dot red"></span> Down</span>`;
                } else if (h.api_status === 'NORMAL-LATE') {
                    statusBadge = `<span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-bold"><span class="status-dot blue"></span> ล่าช้า</span>`;
                }

                const daysTag = (downDays > 0)
                    ? `<span class="text-red-600 font-bold bg-red-50/80 px-2 py-1 rounded-md inline-block">${downDays} วัน</span>`
                    : `<span class="text-slate-400 font-normal">0 วัน</span>`;

                const datesStr = (downDates.length > 0) ? downDates.join(', ') : 'ส่งปกติ 100%';
                const datesClass = (downDays > 0) ? 'text-red-600 font-semibold' : 'text-slate-400 font-normal';

                tr.innerHTML = `
                    <td class="py-3 px-4 font-mono font-medium text-slate-700">${h.code}</td>
                    <td class="py-3 px-4 font-semibold text-slate-900">${h.name}</td>
                    <td class="py-3 px-4 text-center">${statusBadge}</td>
                    <td class="py-3 px-4 text-center font-mono text-xs text-slate-700">${h.last_sent_at}</td>
                    <td class="py-3 px-4 text-center font-mono">${daysTag}</td>
                    <td class="py-3 px-4 text-xs font-mono ${datesClass}">${datesStr}</td>
                `;
                tbody.appendChild(tr);
            });

            const totalHospitals = phpHospitals.length;
            const uptimeRate = totalHospitals > 0 ? (100 - ((totalMonthDownDays / (totalHospitals * 30)) * 100)).toFixed(1) + '%' : '100%';

            document.getElementById('kpiTotalDownDays').textContent = totalMonthDownDays + ' วัน';
            document.getElementById('kpiUptimeRate').textContent = uptimeRate;
            document.getElementById('kpiStableCount').textContent = stableHospCount + ' / ' + totalHospitals + ' แห่ง';
            document.getElementById('kpiTopDownHosp').textContent = topDownHospName;

            renderDowntimeCharts({ labels: chartLabels, data: chartData, colors: chartColors }, selectedMonth);
        }

        function renderDowntimeCharts(chartData, selectedMonth) {
            const ctxBar = document.getElementById('downtimeBarChart').getContext('2d');
            if (barChartInstance) barChartInstance.destroy();

            // 🟢 คำนวณค่าสูงสุด + เผื่อพื้นที่ด้านบนอีก 3 หน่วย เพื่อให้ตัวเลขลอยสวยงามโดยไม่โดนขอบบนตัด
            const maxVal = Math.max(...chartData.data, 0);
            const yAxisMax = Math.max(maxVal + 3, 5);

            barChartInstance = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'จำนวนวันที่ Down (วัน)',
                        data: chartData.data,
                        backgroundColor: chartData.colors,
                        borderRadius: 4,
                        maxBarThickness: 28
                    }]
                },
                plugins: [barDataLabelsPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 20 // 🟢 เพิ่มระยะ Padding ด้านบน
                        }
                    },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { 
                            ticks: { font: { family: 'Sarabun', size: 9 }, maxRotation: 45, minRotation: 25 }, 
                            grid: { display: false } 
                        },
                        y: { 
                            beginAtZero: true, 
                            max: yAxisMax, // 🟢 กำหนด Max Y แบบขยายพื้นที่
                            ticks: { precision: 0, stepSize: 1, font: { family: 'Sarabun', size: 10 } } 
                        }
                    }
                }
            });

            const selectEl = document.getElementById('selectMonth');
            const options = Array.from(selectEl.options);
            const selectedIndex = options.findIndex(opt => opt.value === selectedMonth);

            let last3Months = [];
            if (selectedIndex !== -1) {
                for (let i = Math.min(selectedIndex + 2, options.length - 1); i >= selectedIndex; i--) {
                    last3Months.push(options[i]);
                }
            } else {
                last3Months = options.slice(0, 3).reverse();
            }

            let trendLabels = [];
            let trendData = [];

            last3Months.forEach(opt => {
                const ym = opt.value;
                const monthName = opt.textContent.split(' (')[0];
                trendLabels.push(monthName);

                let mDownCount = 0;
                phpDowntimeLogs.forEach(log => {
                    if (log.down_date && log.down_date.startsWith(ym)) {
                        mDownCount += 1;
                    }
                });
                trendData.push(mDownCount);
            });

            const ctxTrend = document.getElementById('monthlyTrendChart').getContext('2d');
            if (trendChartInstance) trendChartInstance.destroy();

            trendChartInstance = new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'จำนวนวัน Down รวมทั้งจังหวัด',
                        data: trendData,
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 5,
                        pointBackgroundColor: '#2563EB'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: Math.max(...trendData, 5), ticks: { precision: 0, stepSize: 1, font: { family: 'Sarabun', size: 10 } } },
                        x: { ticks: { font: { family: 'Sarabun', size: 10 } } }
                    }
                }
            });
        }

        function editHospital(id, code, name, level, his, api, status, lastSent, days) {
            const formId = document.getElementById('form-id');
            if (!formId) return;

            formId.value = id;
            document.getElementById('form-code').value = code;
            document.getElementById('form-name').value = name;
            document.getElementById('form-level').value = level;
            document.getElementById('form-his').value = his;
            document.getElementById('form-api').value = api;
            document.getElementById('form-status').value = status;
            
            if (lastSent) {
                let formattedDt = lastSent.replace(' ', 'T');
                if (formattedDt.length > 16) {
                    formattedDt = formattedDt.substring(0, 16);
                }
                document.getElementById('form-last-sent').value = formattedDt;
            } else {
                document.getElementById('form-last-sent').value = '';
            }

            document.getElementById('form-days').value = days;
            
            switchTab('admin');
        }

        function confirmDeleteHospital(id) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-form').submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            initMonthOptions();
        });
    </script>
</body>
</html>