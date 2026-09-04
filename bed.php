<?php
require_once 'config.php';

$pdo = getDbConnection();

// 1. จัดการบันทึก/แก้ไขข้อมูล (เฉพาะ Super Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_bed' && isSuperAdmin()) {
        $hospital_code = trim($_POST['hospital_code'] ?? '');
        $hcode_old = trim($_POST['hcode_old'] ?? '');
        $beds = intval($_POST['beds'] ?? 0);
        $icu = intval($_POST['icu'] ?? 0);
        $or_rooms = intval($_POST['or_rooms'] ?? 0);
        $doctors = intval($_POST['doctors'] ?? 0);

        if (!empty($hospital_code)) {
            $stmt = $pdo->prepare("INSERT INTO hospital_beds (hospital_code, hcode_old, beds, icu, or_rooms, doctors)
                VALUES (:code, :hcode_old, :beds, :icu, :or_rooms, :doctors)
                ON DUPLICATE KEY UPDATE 
                    hcode_old = VALUES(hcode_old),
                    beds = VALUES(beds),
                    icu = VALUES(icu),
                    or_rooms = VALUES(or_rooms),
                    doctors = VALUES(doctors)");
            $stmt->execute([
                'code' => $hospital_code,
                'hcode_old' => $hcode_old,
                'beds' => $beds,
                'icu' => $icu,
                'or_rooms' => $or_rooms,
                'doctors' => $doctors
            ]);
            setFlashMessage('อัปเดตข้อมูลทรัพยากรสถานพยาบาลเรียบร้อยแล้ว', 'success');
        }
        header("Location: bed.php");
        exit;
    }
}

// 2. ดึงข้อมูลรายชื่อ รพ. เชื่อมกับข้อมูลเตียง
$sql = "SELECT h.code, h.name, 
               COALESCE(b.hcode_old, REPLACE(h.code, 'EA00', '')) as hcode_old,
               COALESCE(b.beds, 0) as beds,
               COALESCE(b.icu, 0) as icu,
               COALESCE(b.or_rooms, 0) as or_rooms,
               COALESCE(b.doctors, 0) as doctors,
               b.id as bed_id
        FROM hospitals h
        LEFT JOIN hospital_beds b ON h.code = b.hospital_code
        ORDER BY h.id ASC";

$bedsData = $pdo->query($sql)->fetchAll();

// คำนวณยอดรวม (Summary Totals)
$totalHospitals = count($bedsData);
$sumBeds = 0;
$sumIcu = 0;
$sumOr = 0;
$sumDoctors = 0;

// เตรียม Data สำหรับส่งให้ Chart.js (เรียงลำดับ: เตียง -> แพทย์ -> OR -> ICU)
$chartLabels = [];
$chartBeds = [];
$chartDoctors = [];
$chartOr = [];
$chartIcu = [];

$shortNamesMap = [
    'EA0010750' => 'รพ.นราธิวาสราชนครินทร์',
    'EA0010751' => 'รพ.สุไหงโก-ลก',
    'EA0011435' => 'รพ.ตากใบ',
    'EA0011436' => 'รพ.บาเจาะ',
    'EA0011437' => 'รพ.ระแงะ',
    'EA0011438' => 'รพ.รือเสาะ',
    'EA0011439' => 'รพ.ศรีสาคร',
    'EA0011440' => 'รพ.แว้ง',
    'EA0011441' => 'รพ.สุคิริน',
    'EA0011442' => 'รพ.สุไหงปาดี',
    'EA0013818' => 'รพ.จะแนะ',
    'EA0015010' => 'รพ.เจาะไอร้อง',
    'EA0023771' => 'รพ.ยี่งอ'
];

foreach ($bedsData as $row) {
    $sumBeds += $row['beds'];
    $sumIcu += $row['icu'];
    $sumOr += $row['or_rooms'];
    $sumDoctors += $row['doctors'];

    $shortName = $shortNamesMap[$row['code']] ?? str_replace('โรงพยาบาล', 'รพ.', $row['name']);
    $chartLabels[] = $shortName;
    
    $chartBeds[] = $row['beds'];
    $chartDoctors[] = $row['doctors'];
    $chartOr[] = $row['or_rooms'];
    $chartIcu[] = $row['icu'];
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
    <title>จำนวนเตียงและทรัพยากรทางการแพทย์ - MOPH ERP Narathiwat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <!-- Sub-Header Navigation Tabs -->
    <nav class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-2 py-2 overflow-x-auto">
                <a href="index.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-chart-line mr-2"></i> Dashboard ภาพรวม
                </a>
                <a href="index.php?tab=downtime" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-chart-column mr-2 text-red-500"></i> สถิติการ Down รายเดือน (แยก รพ.)
                </a>
                <a href="bed.php" class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center bg-moph-600 text-white shadow-sm">
                    <i class="fa-solid fa-bed mr-2"></i> จำนวนเตียงรายสถานพยาบาล
                </a>
                <a href="about.php" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center">
                    <i class="fa-solid fa-circle-info mr-2 text-emerald-600"></i> About
                </a>
                <?php if (isSuperAdmin()): ?>
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

        <!-- 1. KPI Cards ยอดรวมทรัพยากรจังหวัดนราธิวาส -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">จำนวนเตียงทั้งหมด</p>
                    <h3 class="text-2xl font-black text-blue-700 mt-0.5"><?= number_format($sumBeds) ?> <span class="text-xs font-bold text-slate-600">เตียง</span></h3>
                    <p class="text-[11px] text-slate-400 mt-1"><i class="fa-solid fa-hospital mr-1"></i> ครอบคลุม <?= $totalHospitals ?> สถานพยาบาล</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-bed"></i>
                </div>
            </div>

            <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">จำนวนแพทย์รวม</p>
                    <h3 class="text-2xl font-black text-emerald-600 mt-0.5"><?= number_format($sumDoctors) ?> <span class="text-xs font-bold text-slate-600">คน</span></h3>
                    <!-- 🟢 ปรับเปลี่ยนข้อความตามโจทย์: กำลังแพทย์ปฏิบัติหน้าที่ -->
                    <p class="text-[11px] text-emerald-700 font-medium mt-1"><i class="fa-solid fa-user-doctor mr-1"></i> กำลังแพทย์ปฏิบัติหน้าที่</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
            </div>

            <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">ห้องผ่าตัด (OR)</p>
                    <h3 class="text-2xl font-black text-purple-700 mt-0.5"><?= number_format($sumOr) ?> <span class="text-xs font-bold text-slate-600">ห้อง</span></h3>
                    <p class="text-[11px] text-slate-400 mt-1"><i class="fa-solid fa-hospital-user mr-1"></i> สำหรับการศัลยกรรม</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-user-nurse"></i>
                </div>
            </div>

            <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">เตียงวิกฤต (ICU)</p>
                    <h3 class="text-2xl font-black text-rose-600 mt-0.5"><?= number_format($sumIcu) ?> <span class="text-xs font-bold text-slate-600">เตียง</span></h3>
                    <p class="text-[11px] text-rose-600 font-semibold mt-1"><i class="fa-solid fa-heart-pulse mr-1"></i> รองรับเคสวิกฤต/ฉุกเฉิน</p>
                </div>
                <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-kit-medical"></i>
                </div>
            </div>
        </div>

        <!-- 2. Chart Section: สะอาด ตกแต่งอย่างมืออาชีพ -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-3 mb-4 border-b border-slate-100 gap-2">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-chart-column text-blue-600 mr-2 text-base"></i> กราฟเปรียบเทียบทรัพยากรทางการแพทย์ จำแนกตามสถานพยาบาล
                    </h3>
                </div>
                <span class="bg-blue-50 text-blue-700 border border-blue-200 text-xs px-3 py-1 rounded-full font-bold">
                    <i class="fa-solid fa-hospital mr-1"></i> รวม <?= $totalHospitals ?> รพ.
                </span>
            </div>

            <div class="relative h-72 sm:h-80 w-full">
                <canvas id="bedResourceChart"></canvas>
            </div>
        </div>

        <!-- 3. ตารางข้อมูลทรัพยากรรายสถานพยาบาล -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80 flex justify-between items-center">
                <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-table-list text-moph-600 mr-2"></i> ตารางสรุปข้อมูลจำนวนเตียงและทรัพยากรทางการแพทย์
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-100/90 text-slate-700 font-bold border-b border-slate-200 text-xs">
                            <th class="py-3.5 px-4">เขต/จังหวัด/สถานพยาบาล</th>
                            <th class="py-3.5 px-4 text-right text-blue-600">รพ.</th>
                            <th class="py-3.5 px-4 text-right text-blue-600">เตียง</th>
                            <th class="py-3.5 px-4 text-right text-emerald-600">แพทย์</th>
                            <th class="py-3.5 px-4 text-right text-purple-600">OR</th>
                            <th class="py-3.5 px-4 text-right text-rose-600">ICU</th>
                            <?php if (isSuperAdmin()): ?>
                            <th class="py-3.5 px-4 text-center">จัดการ</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- แถวสรุปรวมจังหวัด -->
                        <tr class="bg-slate-100/80 font-bold text-slate-900 border-b border-slate-200">
                            <td class="py-3.5 px-4 flex items-center">
                                <i class="fa-solid fa-caret-down text-slate-400 mr-2 text-xs"></i>
                                <i class="fa-solid fa-location-dot text-slate-500 mr-1.5 text-xs"></i>
                                นราธิวาส
                            </td>
                            <td class="py-3.5 px-4 text-right text-blue-600 font-bold"><?= number_format($totalHospitals) ?></td>
                            <td class="py-3.5 px-4 text-right text-blue-600 font-bold font-mono"><?= number_format($sumBeds) ?></td>
                            <td class="py-3.5 px-4 text-right text-emerald-600 font-bold font-mono"><?= number_format($sumDoctors) ?></td>
                            <td class="py-3.5 px-4 text-right text-purple-600 font-bold font-mono"><?= number_format($sumOr) ?></td>
                            <td class="py-3.5 px-4 text-right text-rose-600 font-bold font-mono"><?= number_format($sumIcu) ?></td>
                            <?php if (isSuperAdmin()): ?><td></td><?php endif; ?>
                        </tr>

                        <!-- รายการสถานพยาบาล -->
                        <?php foreach ($bedsData as $row): ?>
                        <tr class="hover:bg-slate-50 transition text-slate-700">
                            <td class="py-2.5 px-4 pl-8">
                                <div class="flex items-center space-x-2">
                                    <i class="fa-solid fa-house-medical text-slate-400 text-xs"></i>
                                    <span class="font-bold text-slate-900 hover:text-blue-800 transition"><?= e($row['name']) ?></span>
                                    <span class="text-slate-400 font-mono text-xs">(<?= e($row['hcode_old']) ?>)</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-4 text-right text-slate-300">—</td>
                            <td class="py-2.5 px-4 text-right text-blue-700 font-bold font-mono"><?= number_format($row['beds']) ?></td>
                            <td class="py-2.5 px-4 text-right text-emerald-600 font-bold font-mono"><?= number_format($row['doctors']) ?></td>
                            <td class="py-2.5 px-4 text-right text-purple-700 font-bold font-mono"><?= number_format($row['or_rooms']) ?></td>
                            <td class="py-2.5 px-4 text-right text-rose-600 font-bold font-mono"><?= number_format($row['icu']) ?></td>
                            
                            <?php if (isSuperAdmin()): ?>
                            <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                <button onclick="openEditModal('<?= e($row['code']) ?>', '<?= e($row['name']) ?>', '<?= e($row['hcode_old']) ?>', <?= $row['beds'] ?>, <?= $row['icu'] ?>, <?= $row['or_rooms'] ?>, <?= $row['doctors'] ?>)" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                    <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ฟอร์มแก้ไขสำหรับ Super Admin Modal -->
        <?php if (isSuperAdmin()): ?>
        <div id="modal-edit-bed" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
                <div class="bg-moph-700 text-white p-4 flex justify-between items-center">
                    <h3 class="text-sm font-bold flex items-center">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> จัดการทรัพยากรสถานพยาบาล
                    </h3>
                    <button onclick="closeEditModal()" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" action="bed.php" class="p-6 space-y-4 text-xs">
                    <input type="hidden" name="action" value="save_bed">
                    <input type="hidden" name="hospital_code" id="modal-code">

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">ชื่อสถานพยาบาล</label>
                        <input type="text" id="modal-name" readonly class="w-full p-2 bg-slate-100 border border-slate-300 rounded-lg font-bold text-slate-800">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">รหัสเดิม (HCODE)</label>
                            <input type="text" name="hcode_old" id="modal-hcode" class="w-full p-2 border border-slate-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">จำนวนเตียง</label>
                            <input type="number" name="beds" id="modal-beds" min="0" required class="w-full p-2 border border-slate-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">จำนวนแพทย์ (คน)</label>
                            <input type="number" name="doctors" id="modal-doctors" min="0" required class="w-full p-2 border border-slate-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">ห้องผ่าตัด (OR)</label>
                            <input type="number" name="or_rooms" id="modal-or" min="0" required class="w-full p-2 border border-slate-300 rounded-lg">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">ICU (เตียง)</label>
                        <input type="number" name="icu" id="modal-icu" min="0" required class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-slate-300 rounded-lg font-bold text-slate-600 hover:bg-slate-100">ยกเลิก</button>
                        <button type="submit" class="px-4 py-2 bg-moph-600 text-white rounded-lg font-bold hover:bg-moph-700">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php require 'footer.php'; ?>

    </main>

    <!-- Admin Login Modal -->
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
                    <input type="password" name="password" required value="Username (ชื่อผู้ใช้)" class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-moph-500">
                </div>
                <button type="submit" class="w-full py-2.5 bg-moph-600 text-white rounded-lg font-bold text-sm shadow hover:bg-moph-700">ยืนยันเข้าสู่ระบบ</button>
            </form>
        </div>
    </div>

    <script>
        function toggleAuthModal() {
            document.getElementById('modal-auth').classList.toggle('hidden');
        }

        function openEditModal(code, name, hcode, beds, icu, or, doctors) {
            document.getElementById('modal-code').value = code;
            document.getElementById('modal-name').value = name;
            document.getElementById('modal-hcode').value = hcode;
            document.getElementById('modal-beds').value = beds;
            document.getElementById('modal-doctors').value = doctors;
            document.getElementById('modal-or').value = or;
            document.getElementById('modal-icu').value = icu;
            document.getElementById('modal-edit-bed').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('modal-edit-bed').classList.add('hidden');
        }

        // JavaScript Render Chart.js
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('bedResourceChart').getContext('2d');
            
            const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
            const bedsData = <?= json_encode($chartBeds) ?>;
            const doctorsData = <?= json_encode($chartDoctors) ?>;
            const orData = <?= json_encode($chartOr) ?>;
            const icuData = <?= json_encode($chartIcu) ?>;

            // ฟังก์ชัน Transformation ยกระดับค่า 1-9 ขึ้นมาเล็กน้อย เพื่อให้มองเห็นชัดแต่คงสเกลความสวยงาม
            function transformValue(val) {
                if (val === 0 || val === null) return 0;
                if (val > 0 && val < 10) return 12 + (val * 1.5);
                return val;
            }

            const transformedBeds = bedsData.map(transformValue);
            const transformedDoctors = doctorsData.map(transformValue);
            const transformedOr = orData.map(transformValue);
            const transformedIcu = icuData.map(transformValue);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'เตียง (เตียง)',
                            data: transformedBeds,
                            rawValues: bedsData,
                            backgroundColor: '#2563EB',
                            borderRadius: 4,
                            maxBarThickness: 14
                        },
                        {
                            label: 'แพทย์ (คน)',
                            data: transformedDoctors,
                            rawValues: doctorsData,
                            backgroundColor: '#16A34A',
                            borderRadius: 4,
                            maxBarThickness: 14
                        },
                        {
                            label: 'ห้องผ่าตัด (OR)',
                            data: transformedOr,
                            rawValues: orData,
                            backgroundColor: '#9333EA',
                            borderRadius: 4,
                            maxBarThickness: 14
                        },
                        {
                            label: 'ICU (เตียง)',
                            data: transformedIcu,
                            rawValues: icuData,
                            backgroundColor: '#E11D48',
                            borderRadius: 4,
                            maxBarThickness: 14
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 20 // เพิ่มพื้นที่ด้านบนเพื่อให้ตัวเลขบนแท่งสูงๆ ไม่ถูกตัดขอบ
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: { family: 'Sarabun', size: 11, weight: 'bold' },
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },
                        tooltip: {
                            titleFont: { family: 'Sarabun', size: 12, weight: 'bold' },
                            bodyFont: { family: 'Sarabun', size: 11 },
                            callbacks: {
                                label: function(context) {
                                    const rawVal = context.dataset.rawValues[context.dataIndex];
                                    return `${context.dataset.label}: ${rawVal}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: { family: 'Sarabun', size: 9 },
                                maxRotation: 45,
                                minRotation: 20
                            },
                            grid: { display: false }
                        },
                        y: {
                            type: 'linear',
                            beginAtZero: true,
                            max: 500,
                            ticks: {
                                font: { family: 'Sarabun', size: 10 },
                                stepSize: 100,
                                callback: function(value) {
                                    return value;
                                }
                            },
                            grid: {
                                color: '#E2E8F0'
                            }
                        }
                    }
                },
                // 🟢 Plugin วาดตัวเลขแสดงจำนวนบนหัวแท่งกราฟ (Data Labels)
                plugins: [{
                    id: 'barValueLabels',
                    afterDatasetsDraw(chart) {
                        const { ctx } = chart;
                        ctx.save();
                        ctx.font = 'bold 9px Sarabun, sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';

                        chart.data.datasets.forEach((dataset, datasetIndex) => {
                            const meta = chart.getDatasetMeta(datasetIndex);
                            meta.data.forEach((bar, index) => {
                                const rawVal = dataset.rawValues[index];
                                // แสดงตัวเลขเฉพาะแท่งที่มีค่ามากกว่า 0
                                if (rawVal > 0) {
                                    ctx.fillStyle = '#475569'; // สีเทาเข้ม อ่านง่าย สมดุลกับตัวกราฟ
                                    ctx.fillText(rawVal, bar.x, bar.y - 2);
                                }
                            });
                        });
                        ctx.restore();
                    }
                }]
            });
        });
    </script>
</body>
</html>