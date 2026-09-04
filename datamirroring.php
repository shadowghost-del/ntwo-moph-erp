<?php
require_once 'config.php';

// 🔴 ตรวจสอบสิทธิ์เข้าถึง: เฉพาะ Super Admin เท่านั้น
if (!isSuperAdmin()) {
    setFlashMessage('คุณไม่มีสิทธิ์เข้าถึงหน้าสำรองข้อมูลระบบ (สำหรับผู้ดูแลระบบเท่านั้น)', 'danger');
    header("Location: index.php");
    exit;
}

$pdo = getDbConnection();

// กำหนดโฟลเดอร์จัดเก็บไฟล์สำรอง Data Mirroring
$backupDir = __DIR__ . '/DataMirroring/';

// หากยังไม่มีโฟลเดอร์ ให้สร้างขึ้นอัตโนมัติ
if (!file_exists($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

// สร้างไฟล์ .htaccess เพื่อป้องกันการดึงไฟล์ตรงจากเว็บบราวเซอร์
$htaccessFile = $backupDir . '.htaccess';
if (!file_exists($htaccessFile) && is_writable($backupDir)) {
    @file_put_contents($htaccessFile, "Deny from all\n");
}

// ---------------------------------------------------------
// 1. จัดการ ACTION: ดำเนินการสำรองข้อมูล (Backup Database)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    try {
        if (!is_writable($backupDir)) {
            throw new Exception("โฟลเดอร์ DataMirroring ไม่มีสิทธิ์เขียนไฟล์ (Permission Denied / SELinux Blocked)");
        }

        $dbName = DB_NAME;
        $filename = $dbName . '_' . date('Ymd-His') . '.sql';
        $filePath = $backupDir . $filename;

        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlScript = "-- ========================================================\n";
        $sqlScript .= "-- MOPH ERP Narathiwat - Database Backup (Data Mirroring)\n";
        $sqlScript .= "-- Host: " . DB_HOST . " | Database: " . DB_NAME . "\n";
        $sqlScript .= "-- Backup Date: " . date('Y-m-d H:i:s') . "\n";
        $sqlScript .= "-- Created by: " . ($_SESSION['fullname'] ?? 'Super Admin') . "\n";
        $sqlScript .= "-- ========================================================\n\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sqlScript .= "-- --------------------------------------------------------\n";
            $sqlScript .= "-- Table structure for `$table`\n";
            $sqlScript .= "-- --------------------------------------------------------\n";
            $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlScript .= $row['Create Table'] . ";\n\n";

            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($rows) > 0) {
                $sqlScript .= "-- Records of `$table`\n";
                foreach ($rows as $r) {
                    $values = array_map(function($val) use ($pdo) {
                        if ($val === null) return 'NULL';
                        return $pdo->quote($val);
                    }, array_values($r));

                    $sqlScript .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sqlScript .= "\n";
            }
        }

        $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if (file_put_contents($filePath, $sqlScript) !== false) {
            setFlashMessage("สร้างไฟล์สำรองข้อมูล ($filename) เรียบร้อยแล้ว", 'success');
        } else {
            setFlashMessage("เกิดข้อผิดพลาด ไม่สามารถเขียนไฟล์สำรองข้อมูลได้ กรุณาเช็ก Permission ของโฟลเดอร์", 'danger');
        }

    } catch (Exception $e) {
        setFlashMessage("เกิดข้อผิดพลาดในการสำรองข้อมูล: " . $e->getMessage(), 'danger');
    }

    header("Location: datamirroring.php");
    exit;
}

// ---------------------------------------------------------
// 2. จัดการ ACTION: ดาวน์โหลดไฟล์สำรองข้อมูล (Download SQL)
// ---------------------------------------------------------
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $downloadFile = basename($_GET['download']);
    $filePath = $backupDir . $downloadFile;

    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadFile . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        setFlashMessage("ไม่พบไฟล์ที่ต้องการดาวน์โหลด", 'danger');
        header("Location: datamirroring.php");
        exit;
    }
}

// ---------------------------------------------------------
// 3. จัดการ ACTION: ลบไฟล์สำรองข้อมูล (Delete Backup File)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_backup') {
    $deleteFile = basename($_POST['filename'] ?? '');
    $filePath = $backupDir . $deleteFile;

    if (!empty($deleteFile) && file_exists($filePath)) {
        // 🟢 เช็กผลลัพธ์การลบไฟล์จาก unlink() จริง
        if (@unlink($filePath)) {
            setFlashMessage("ลบไฟล์สำรองข้อมูล ($deleteFile) เรียบร้อยแล้ว", 'success');
        } else {
            setFlashMessage("ไม่สามารถลบไฟล์ ($deleteFile) ได้ เนื่องจากติดสิทธิ์ Permission/SELinux บน Linux Server", 'danger');
        }
    } else {
        setFlashMessage("ไม่พบไฟล์ที่ต้องการลบ", 'danger');
    }
    header("Location: datamirroring.php");
    exit;
}

// ---------------------------------------------------------
// 4. อ่านรายการไฟล์สำรองทั้งหมดในโฟลเดอร์ DataMirroring
// ---------------------------------------------------------
$backupFiles = [];
$totalSize = 0;

if (file_exists($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && $f !== '.htaccess' && pathinfo($f, PATHINFO_EXTENSION) === 'sql') {
            $fPath = $backupDir . $f;
            $fSize = filesize($fPath);
            $totalSize += $fSize;

            $backupFiles[] = [
                'name' => $f,
                'size' => $fSize,
                'time' => filemtime($fPath)
            ];
        }
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

$flash = getFlashMessage();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mirroring - ระบบสำรองข้อมูลฐานข้อมูล MOPH ERP</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Sarabun', 'sans-serif'] },
                    colors: { moph: { 50: '#f0fdf4', 600: '#16a34a', 700: '#15803d', 800: '#166534' } }
                }
            }
        }
    </script>
    <style> body { font-family: 'Sarabun', sans-serif; } </style>
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
                    <div class="flex items-center bg-emerald-50 text-emerald-800 px-3 py-1.5 rounded-lg text-xs font-semibold border border-emerald-200">
                        <i class="fa-solid fa-user-shield mr-1.5 text-emerald-600"></i>
                        <?= e($_SESSION['fullname']) ?> (Super Admin)
                    </div>
                    <a href="logout.php" class="inline-flex items-center px-3.5 py-1.5 border border-transparent text-xs font-semibold rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 transition">
                        <i class="fa-solid fa-right-from-bracket mr-1.5"></i> ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-2 py-2 overflow-x-auto">
                <a href="index.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-chart-line mr-2"></i> Dashboard ภาพรวม
                </a>
                <a href="index.php?tab=downtime" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-chart-column mr-2 text-red-500"></i> สถิติการ Down รายเดือน (แยก รพ.)
                </a>
                <a href="bed.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-bed mr-2 text-blue-600"></i> จำนวนเตียงรายสถานพยาบาล
                </a>
                <a href="about.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-circle-info mr-2 text-emerald-600"></i> About
                </a>
                <a href="datamirroring.php" class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center bg-moph-600 text-white shadow-sm">
                    <i class="fa-solid fa-database mr-2"></i> Data Mirroring
                </a>
                <a href="index.php?tab=admin" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-sliders mr-2 text-moph-600"></i> บริหารจัดการข้อมูล (Admin)
                </a>
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-database text-purple-600 mr-2.5 text-xl"></i> Data Mirroring & Database Backup Management
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    ระบบสำรองข้อมูลฐานข้อมูล MySQL อัตโนมัติ (จัดเก็บในโฟลเดอร์ <code class="bg-slate-100 text-purple-700 px-1.5 py-0.5 rounded border border-slate-200 font-mono font-bold">DataMirroring/</code>)
                </p>
            </div>

            <form method="POST" action="datamirroring.php" onsubmit="return confirm('คุณต้องการสร้างไฟล์สำรองข้อมูลฐานข้อมูล ณ ตอนนี้ใช่หรือไม่?');">
                <input type="hidden" name="action" value="create_backup">
                <button type="submit" class="px-5 py-2.5 bg-moph-600 hover:bg-moph-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-2">
                    <i class="fa-solid fa-cloud-arrow-down text-sm"></i>
                    <span>สำรองข้อมูลตอนนี้ (Backup Now)</span>
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">จำนวนไฟล์สำรองข้อมูล</p>
                    <h3 class="text-2xl font-black text-purple-700 mt-0.5"><?= count($backupFiles) ?> <span class="text-xs font-bold text-slate-600">ไฟล์</span></h3>
                    <p class="text-[11px] text-slate-400 mt-1"><i class="fa-solid fa-folder mr-1"></i> DataMirroring Directory</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-file-code"></i>
                </div>
            </div>

            <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">พื้นที่จัดเก็บรวม</p>
                    <h3 class="text-2xl font-black text-blue-700 mt-0.5"><?= formatBytes($totalSize) ?></h3>
                    <p class="text-[11px] text-slate-400 mt-1"><i class="fa-solid fa-hard-drive mr-1"></i> ทั้งหมดในดิสก์</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-server"></i>
                </div>
            </div>

            <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">สำรองข้อมูลล่าสุด</p>
                    <h3 class="text-sm font-bold text-slate-800 font-mono mt-1">
                        <?= count($backupFiles) > 0 ? date('d/m/Y H:i:s', $backupFiles[0]['time']) : 'ยังไม่มีข้อมูล' ?>
                    </h3>
                    <p class="text-[11px] text-emerald-600 font-medium mt-1"><i class="fa-solid fa-circle-check mr-1"></i> สถานะการคุ้มครองข้อมูล</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80 flex justify-between items-center">
                <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-list-check text-moph-600 mr-2"></i> รายการไฟล์สำรองฐานข้อมูล (Backup History Log)
                </h3>
                <span class="bg-slate-200 text-slate-700 text-xs px-2.5 py-0.5 rounded-full font-bold">
                    <?= count($backupFiles) ?> รายการ
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-100/90 text-slate-700 font-bold border-b border-slate-200 text-xs">
                            <th class="py-3.5 px-4">ชื่อไฟล์สำรอง (Backup Name)</th>
                            <th class="py-3.5 px-4">ขนาดไฟล์</th>
                            <th class="py-3.5 px-4 text-center">วันที่และเวลาที่บันทึก</th>
                            <th class="py-3.5 px-4 text-center">สิทธิ์การเข้าถึง</th>
                            <th class="py-3.5 px-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($backupFiles) === 0): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-database text-3xl mb-2 block text-slate-300"></i>
                                    ยังไม่มีไฟล์สำรองข้อมูลในโฟลเดอร์ DataMirroring
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backupFiles as $file): ?>
                            <tr class="hover:bg-slate-50 transition text-slate-700">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 flex items-center space-x-2">
                                    <i class="fa-solid fa-file-lines text-purple-600 text-sm"></i>
                                    <span><?= e($file['name']) ?></span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 font-medium"><?= formatBytes($file['size']) ?></td>
                                <td class="py-3 px-4 text-center font-mono text-xs text-slate-800"><?= date('d/m/Y H:i:s', $file['time']) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded-full text-[11px] font-bold">
                                        <i class="fa-solid fa-lock mr-1"></i> Super Admin Only
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <a href="datamirroring.php?download=<?= urlencode($file['name']) ?>" class="inline-flex items-center px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-bold transition mr-1">
                                        <i class="fa-solid fa-download mr-1.5"></i> ดาวน์โหลด
                                    </a>

                                    <form method="POST" action="datamirroring.php" class="inline-block" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์สำรองนี้?');">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="filename" value="<?= e($file['name']) ?>">
                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-lg text-xs font-bold transition">
                                            <i class="fa-solid fa-trash-can mr-1.5"></i> ลบ
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php require 'footer.php'; ?>

    </main>
</body>
</html>