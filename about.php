<?php
require_once 'config.php';

$pdo = getDbConnection();

// ฟังก์ชันสำหรับ Masking อีเมล (แสดง 3 ตัวแรกตามด้วย xxxxxx แล้วปิดด้วย @domain)
function maskEmail($email) {
    if (empty($email) || !strpos($email, '@')) return $email;
    list($username, $domain) = explode('@', $email, 2);
    $prefix = mb_substr($username, 0, 3, 'UTF-8');
    return $prefix . 'xxxxxx@' . $domain;
}

// ฟังก์ชันสำหรับ Masking เบอร์โทรศัพท์ (แสดง 3 ตัวแรกตามด้วย xxxxxxx)
function maskPhone($phone) {
    if (empty($phone)) return $phone;
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) >= 3) {
        return substr($cleanPhone, 0, 3) . 'xxxxxxx';
    }
    return $phone;
}

// ดึงรายชื่อหน่วยงาน/สถานพยาบาลทั้งหมดจากฐานข้อมูล hospitals
$stmtHosp = $pdo->query("SELECT DISTINCT name FROM hospitals ORDER BY id ASC");
$hospitalList = $stmtHosp->fetchAll(PDO::FETCH_COLUMN);

// เพิ่ม สสจ. นราธิวาส เข้าไปในรายการหลักถ้ายังไม่มี
if (!in_array('สำนักงานสาธารณสุขจังหวัดนราธิวาส', $hospitalList)) {
    array_unshift($hospitalList, 'สำนักงานสาธารณสุขจังหวัดนราธิวาส');
}

// จัดการ เพิ่ม/แก้ไข/ลบ ข้อมูลเจ้าหน้าที่ IT (เฉพาะผู้ดูแลระบบ Super Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isSuperAdmin()) {
    $action = $_POST['action'];

    if ($action === 'save_officer') {
        $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
        $email = trim($_POST['email'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = trim($_POST['role'] ?? 'IT รพ.');
        
        $agency_select = trim($_POST['agency_select'] ?? '');
        $agency_custom = trim($_POST['agency_custom'] ?? '');
        $agency = ($agency_select === 'CUSTOM') ? $agency_custom : $agency_select;

        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            setFlashMessage('เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลักเท่านั้น (ไม่มี - หรือเว้นวรรค)', 'error');
            header("Location: about.php");
            exit;
        }

        if (empty($agency)) {
            setFlashMessage('กรุณาระบุหน่วยงาน หรือสถานพยาบาล', 'error');
            header("Location: about.php");
            exit;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE it_officers SET email = :email, fullname = :fullname, phone = :phone, role = :role, agency = :agency WHERE id = :id");
            $stmt->execute(['email' => $email, 'fullname' => $fullname, 'phone' => $phone, 'role' => $role, 'agency' => $agency, 'id' => $id]);
            setFlashMessage('แก้ไขข้อมูลเจ้าหน้าที่เรียบร้อยแล้ว', 'success');
        } else {
            $stmt = $pdo->prepare("INSERT INTO it_officers (email, fullname, phone, role, agency) VALUES (:email, :fullname, :phone, :role, :agency)");
            $stmt->execute(['email' => $email, 'fullname' => $fullname, 'phone' => $phone, 'role' => $role, 'agency' => $agency]);
            setFlashMessage('เพิ่มข้อมูลเจ้าหน้าที่เรียบร้อยแล้ว', 'success');
        }
        header("Location: about.php");
        exit;
    }

    if ($action === 'delete_officer') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM it_officers WHERE id = :id");
            $stmt->execute(['id' => $id]);
            setFlashMessage('ลบข้อมูลเจ้าหน้าที่เรียบร้อยแล้ว', 'success');
        }
        header("Location: about.php");
        exit;
    }
}

// 🟢 ดึงข้อมูลเจ้าหน้าที่ IT ทั้งหมดแสดงผลเสมอโดยไม่ต้อง Login
$stmt = $pdo->query("SELECT * FROM it_officers ORDER BY agency ASC, id ASC");
$officers = $stmt->fetchAll();

$flash = getFlashMessage();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกี่ยวกับระบบ - MOPH ERP Narathiwat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    <!-- Header -->
    <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-moph-600 text-white p-2.5 rounded-xl shadow-sm flex items-center justify-center">
                        <i class="fa-solid fa-hospital-user text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <img src="img/ntwo-logo.png" alt="Logo" width="24" height="27" decoding="async" loading="lazy" class="h-5 sm:h-6 w-auto object-contain shrink-0">
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

    <!-- Navigation Tabs -->
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
                <a href="about.php" class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center bg-moph-600 text-white shadow-sm">
                    <i class="fa-solid fa-circle-info mr-2"></i> About
                </a>
                <?php if (isSuperAdmin()): ?>
                <a href="datamirroring.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-database mr-2 text-purple-600"></i> Data Mirroring
                </a>
                <a href="index.php?tab=admin" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-sliders mr-2 text-moph-600"></i> บริหารจัดการข้อมูล (Admin)
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Alert Messages -->
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

        <!-- 1. ส่วนรายละเอียดของระบบ (ปรับปรุงการจัดวางเพิ่ม Logo Digital Health) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-5 text-moph-800 pointer-events-none">
                <i class="fa-solid fa-hospital-user text-[220px]"></i>
            </div>

            <div class="space-y-6 relative z-10">
                <div>
                    <span class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold mb-3">
                        <i class="fa-solid fa-shield-halved mr-1.5"></i> MOPH ERP Narathiwat
                    </span>

                    <div class="flex items-center space-x-3.5 sm:space-x-4">
                        <img src="img/ntwo-logo.png" 
                             alt="MOPH Narathiwat Logo" 
                             width="64" 
                             height="71" 
                             decoding="async" 
                             loading="lazy" 
                             class="h-14 sm:h-16 w-auto object-contain shrink-0 drop-shadow-sm">
                        
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">
                                MOPH ERP Narathiwat Dashboard
                            </h2>
                            <p class="text-sm font-semibold text-moph-700 mt-0.5">
                                Narathiwat Provincial Public Health Office
                            </p>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- 🟢 Details & Logo Section Grid -->
                <!-- 🟢 Details & Logo Section Grid (ปรับสเกลขนาดกรอบให้เท่ากัน และขยายโลโก้) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
                    
                    <!-- ฝั่งซ้าย: Details / รายละเอียด -->
                    <div class="flex flex-col h-full">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5 flex items-center">
                            <i class="fa-solid fa-circle-info mr-1.5 text-slate-400"></i> DETAILS / รายละเอียด
                        </h3>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-4 shadow-sm flex-1 flex flex-col justify-center">
                            
                            <!-- 1. วัตถุประสงค์ระบบ -->
                            <div class="flex items-start space-x-3.5">
                                <div class="w-9 h-9 bg-moph-600 text-white rounded-xl shadow-sm flex items-center justify-center shrink-0 mt-0.5">
                                    <i class="fa-solid fa-chart-line text-base"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">วัตถุประสงค์ระบบ</p>
                                    <p class="text-sm font-bold text-slate-800 mt-0.5">ระบบติดตามการส่งข้อมูล MOPH ERP สถานพยาบาล จ.นราธิวาส</p>
                                </div>
                            </div>

                            <hr class="border-slate-200/60">

                            <!-- 2. Production / หน่วยงานรับผิดชอบการพัฒนา -->
                            <div class="flex items-start space-x-3.5">
                                <div class="w-9 h-9 bg-blue-600 text-white rounded-xl shadow-sm flex items-center justify-center shrink-0 mt-0.5">
                                    <i class="fa-solid fa-laptop-code text-base"></i>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide flex items-center">
                                        <i class="fa-solid fa-code-branch text-blue-500 mr-1.5"></i> PRODUCTION / หน่วยงานรับผิดชอบการพัฒนา
                                    </p>
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-bold text-slate-900">กลุ่มงานสุขภาพดิจิทัล</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            <i class="fa-solid fa-microchip mr-1 text-blue-500"></i> Digital Health
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-200/60">

                            <!-- 3. Organization -->
                            <div class="flex items-start space-x-3.5">
                                <div class="w-9 h-9 bg-emerald-700 text-white rounded-xl shadow-sm flex items-center justify-center shrink-0 mt-0.5">
                                    <i class="fa-solid fa-building-user text-base"></i>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide flex items-center">
                                        <i class="fa-solid fa-sitemap text-emerald-600 mr-1.5"></i> ORGANIZATION
                                    </p>
                                    <p class="text-sm font-bold text-slate-900">สำนักงานสาธารณสุขจังหวัดนราธิวาส</p>
                                    <p class="text-xs font-semibold text-moph-700 flex items-center">
                                        <i class="fa-solid fa-location-dot mr-1.5 text-moph-600"></i> Narathiwat Provincial Public Health Office
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- 🟢 ฝั่งขวา: กรอบขนาดเท่ากับฝั่งซ้ายเป๊ะ + รูปโลโก้ขนาดใหญ่ขึ้น -->
                    <div class="flex flex-col h-full">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5 flex items-center opacity-0 pointer-events-none">
                            <i class="fa-solid fa-image mr-1.5"></i> LOGO
                        </h3>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm flex-1 flex items-center justify-center group hover:border-emerald-300 transition-all duration-300">
                            <img src="img/LogoDigitalHealth.png" 
                                 alt="Digital Health Logo" 
                                 decoding="async" 
                                 loading="lazy" 
                                 class="h-48 sm:h-56 w-auto object-contain drop-shadow-md group-hover:scale-105 transition-transform duration-300">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- 2. ส่วนตารางข้อมูลเจ้าหน้าที่ไอที (แสดงสาธารณะแก่บุคคลทั่วไป) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-users-gear text-moph-600 mr-2 text-base"></i> รายชื่อและข้อมูลติดต่อเจ้าหน้าที่ IT แต่ละหน่วยงาน
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <?php if (isSuperAdmin()): ?>
                            <span class="text-emerald-700 font-bold"><i class="fa-solid fa-unlock mr-1"></i> แสดงข้อมูลเต็ม (สิทธิ์ Super Admin)</span>
                        <?php else: ?>
                            <span class="text-amber-700 font-medium"><i class="fa-solid fa-user-shield mr-1"></i> ข้อมูลถูกซ่อนบางส่วนเพื่อป้องกัน Spam/มิจฉาชีพ (เข้าสู่ระบบ Admin เพื่อดูข้อมูลเต็ม)</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if (isSuperAdmin()): ?>
                    <button onclick="openOfficerModal()" class="px-3.5 py-2 bg-moph-600 hover:bg-moph-700 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center">
                        <i class="fa-solid fa-user-plus mr-1.5"></i> เพิ่มรายชื่อเจ้าหน้าที่ IT
                    </button>
                <?php else: ?>
                    <button onclick="toggleAuthModal()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition flex items-center">
                        <i class="fa-solid fa-lock mr-1.5 text-slate-500"></i> เข้าสู่ระบบเพื่อดูข้อมูลเต็ม
                    </button>
                <?php endif; ?>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-100/90 text-slate-700 font-bold border-b border-slate-200 text-xs">
                            <th class="py-3.5 px-4">อีเมล</th>
                            <th class="py-3.5 px-4">ชื่อ-นามสกุล</th>
                            <th class="py-3.5 px-4 text-center">เบอร์โทรศัพท์</th>
                            <th class="py-3.5 px-4 text-center">บทบาท</th>
                            <th class="py-3.5 px-4">หน่วยงาน</th>
                            <?php if (isSuperAdmin()): ?>
                            <th class="py-3.5 px-4 text-center">จัดการ</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($officers) > 0): ?>
                            <?php foreach ($officers as $off): ?>
                            <?php 
                                $displayEmail = isSuperAdmin() ? $off['email'] : maskEmail($off['email']);
                                $displayPhone = isSuperAdmin() ? $off['phone'] : maskPhone($off['phone']);
                            ?>
                            <tr class="hover:bg-slate-50/80 transition text-slate-700">
                                <td class="py-3 px-4 font-mono font-medium text-blue-700"><?= e($displayEmail) ?></td>
                                <td class="py-3 px-4 font-bold text-slate-900"><?= e($off['fullname']) ?></td>
                                <td class="py-3 px-4 text-center font-mono font-semibold text-slate-800"><?= e($displayPhone) ?></td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $off['role'] === 'Admin สสจ/เขต.' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= e($off['role']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-800"><?= e($off['agency']) ?></td>
                                
                                <?php if (isSuperAdmin()): ?>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <button onclick="editOfficer(<?= $off['id'] ?>, '<?= e($off['email']) ?>', '<?= e($off['fullname']) ?>', '<?= e($off['phone']) ?>', '<?= e($off['role']) ?>', '<?= e($off['agency']) ?>')" class="text-blue-600 hover:text-blue-800 font-bold mr-2">
                                        <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                    </button>
                                    <button onclick="confirmDeleteOfficer(<?= $off['id'] ?>)" class="text-red-600 hover:text-red-800 font-bold">
                                        <i class="fa-solid fa-trash-can"></i> ลบ
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= isSuperAdmin() ? '6' : '5' ?>" class="py-6 text-center text-slate-400 font-medium">
                                    ยังไม่มีข้อมูลเจ้าหน้าที่ IT ในระบบ
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal เพิ่ม/แก้ไขเจ้าหน้าที่ IT (เฉพาะ Super Admin) -->
        <?php if (isSuperAdmin()): ?>
        <div id="modal-officer" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
                <div class="bg-moph-700 text-white p-4 flex justify-between items-center">
                    <h3 id="modal-officer-title" class="text-sm font-bold flex items-center">
                        <i class="fa-solid fa-user-gear mr-2"></i> จัดการข้อมูลเจ้าหน้าที่ IT
                    </h3>
                    <button onclick="closeOfficerModal()" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" action="about.php" class="p-6 space-y-4 text-xs">
                    <input type="hidden" name="action" value="save_officer">
                    <input type="hidden" name="id" id="off-id">

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">อีเมล <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="off-email" required class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-moph-500">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" name="fullname" id="off-fullname" required class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-moph-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">เบอร์โทรศัพท์ (10 หลัก) <span class="text-red-500">*</span></label>
                            <input type="text" name="phone" id="off-phone" required maxlength="10" pattern="[0-9]{10}" title="กรุณากรอกเบอร์โทรศัพท์ตัวเลข 10 หลักเท่านั้น" placeholder="08XXXXXXXX" class="w-full p-2.5 border border-slate-300 rounded-lg font-mono outline-none focus:ring-2 focus:ring-moph-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">บทบาท <span class="text-red-500">*</span></label>
                            <select name="role" id="off-role" class="w-full p-2.5 border border-slate-300 rounded-lg bg-white outline-none focus:ring-2 focus:ring-moph-500">
                                <option value="IT รพ.">IT รพ.</option>
                                <option value="Admin สสจ/เขต.">Admin สสจ/เขต.</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">หน่วยงาน <span class="text-red-500">*</span></label>
                        <select name="agency_select" id="off-agency-select" onchange="toggleCustomAgencyInput(this.value)" class="w-full p-2.5 border border-slate-300 rounded-lg bg-white outline-none focus:ring-2 focus:ring-moph-500 font-semibold">
                            <option value="">-- เลือกหน่วยงาน/สถานพยาบาล --</option>
                            <?php foreach ($hospitalList as $hName): ?>
                                <option value="<?= e($hName) ?>"><?= e($hName) ?></option>
                            <?php endforeach; ?>
                            <option value="CUSTOM" class="text-moph-700 font-bold">+ กรอกหน่วยงาน/สถานพยาบาลใหม่...</option>
                        </select>

                        <div id="custom-agency-container" class="mt-2 hidden">
                            <input type="text" name="agency_custom" id="off-agency-custom" placeholder="กรอกชื่อหน่วยงาน/สถานพยาบาลใหม่" class="w-full p-2.5 border border-emerald-300 bg-emerald-50/50 rounded-lg outline-none focus:ring-2 focus:ring-moph-500">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" onclick="closeOfficerModal()" class="px-4 py-2 border border-slate-300 rounded-lg font-bold text-slate-600 hover:bg-slate-100">ยกเลิก</button>
                        <button type="submit" class="px-4 py-2 bg-moph-600 text-white rounded-lg font-bold hover:bg-moph-700">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>

        <form id="delete-officer-form" method="POST" action="about.php" class="hidden">
            <input type="hidden" name="action" value="delete_officer">
            <input type="hidden" name="id" id="delete-officer-id">
        </form>
        <?php endif; ?>

        <!-- เรียกใช้ footer.php -->
        <?php require 'footer.php'; ?>

    </main>

    <!-- Modal Admin Login -->
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
        function toggleAuthModal() {
            document.getElementById('modal-auth').classList.toggle('hidden');
        }

        function toggleCustomAgencyInput(val) {
            const container = document.getElementById('custom-agency-container');
            const customInput = document.getElementById('off-agency-custom');
            if (val === 'CUSTOM') {
                container.classList.remove('hidden');
                customInput.required = true;
                customInput.focus();
            } else {
                container.classList.add('hidden');
                customInput.required = false;
                customInput.value = '';
            }
        }

        function openOfficerModal() {
            document.getElementById('off-id').value = '';
            document.getElementById('off-email').value = '';
            document.getElementById('off-fullname').value = '';
            document.getElementById('off-phone').value = '';
            document.getElementById('off-role').value = 'IT รพ.';
            
            document.getElementById('off-agency-select').value = '';
            toggleCustomAgencyInput('');

            document.getElementById('modal-officer-title').innerText = 'เพิ่มรายชื่อเจ้าหน้าที่ IT';
            document.getElementById('modal-officer').classList.remove('hidden');
        }

        function editOfficer(id, email, fullname, phone, role, agency) {
            document.getElementById('off-id').value = id;
            document.getElementById('off-email').value = email;
            document.getElementById('off-fullname').value = fullname;
            document.getElementById('off-phone').value = phone;
            document.getElementById('off-role').value = role;

            const selectEl = document.getElementById('off-agency-select');
            let hasOption = false;

            for (let i = 0; i < selectEl.options.length; i++) {
                if (selectEl.options[i].value === agency) {
                    hasOption = true;
                    break;
                }
            }

            if (hasOption) {
                selectEl.value = agency;
                toggleCustomAgencyInput(agency);
            } else {
                selectEl.value = 'CUSTOM';
                toggleCustomAgencyInput('CUSTOM');
                document.getElementById('off-agency-custom').value = agency;
            }

            document.getElementById('modal-officer-title').innerText = 'แก้ไขข้อมูลเจ้าหน้าที่ IT';
            document.getElementById('modal-officer').classList.remove('hidden');
        }

        function closeOfficerModal() {
            document.getElementById('modal-officer').classList.add('hidden');
        }

        function confirmDeleteOfficer(id) {
            if (confirm('คุณต้องการลบข้อมูลเจ้าหน้าที่รายนี้ใช่หรือไม่?')) {
                document.getElementById('delete-officer-id').value = id;
                document.getElementById('delete-officer-form').submit();
            }
        }
    </script>
</body>
</html>