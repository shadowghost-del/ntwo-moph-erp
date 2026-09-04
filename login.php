<?php
require_once 'config.php';

// 1. อนุญาตเฉพาะ HTTP POST Request เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); //
    exit; //
}

$username = trim($_POST['username'] ?? ''); //
$password = trim($_POST['password'] ?? ''); //[cite: 5]

// 2. ตรวจสอบการกรอกข้อมูลเบื้องต้น[cite: 5]
if (empty($username) || empty($password)) { //[cite: 5]
    setFlashMessage('กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน', 'error'); //[cite: 5]
    header('Location: index.php'); //[cite: 5]
    exit; //[cite: 5]
}

try {
    $pdo = getDbConnection(); //[cite: 5]
    
    // 3. ป้องกัน SQL Injection ด้วย Prepared Statement แบบ Parameterized Query[cite: 5]
    $stmt = $pdo->prepare("SELECT id, username, password, fullname, role FROM users WHERE username = :username LIMIT 1"); //[cite: 5]
    $stmt->execute(['username' => $username]); //[cite: 5]
    $user = $stmt->fetch(); //[cite: 5]

    // 4. ยืนยันตัวตนผ่าน password_verify() เท่านั้น (ตัดเงื่อนไข Hardcoded Password ออกทั้งหมด)[cite: 5]
    if ($user && password_verify($password, $user['password'])) { //[cite: 5]
        
        // 🔒 ป้องกันช่องโหว่ Session Fixation โดยการสร้าง Session ID ใหม่ทันทีเมื่อล็อกอินผ่าน
        session_regenerate_id(true);

        // บันทึกข้อมูลสิทธิ์ลงใน Session[cite: 5]
        $_SESSION['user_id']   = $user['id']; //[cite: 5]
        $_SESSION['username']  = $user['username']; //[cite: 5]
        $_SESSION['fullname']  = $user['fullname']; //[cite: 5]
        $_SESSION['user_role'] = $user['role']; //[cite: 5]
        
        setFlashMessage('เข้าสู่ระบบในฐานะ Super Admin สำเร็จ', 'success'); //[cite: 5]
    } else {
        // 🔒 แสดงข้อความกว้างๆ เพื่อป้องกันช่องโหว่ Account Enumeration (ไม่ระบุว่า Username หรือ Password ที่ผิด)
        setFlashMessage('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง', 'error'); //[cite: 5]
    }
} catch (Exception $e) {
    // 🔒 Log Error ไปยัง System Log เพื่อความปลอดภัย และไม่แสดง Raw Exception แก่ผู้ใช้
    error_log("Login Exception: " . $e->getMessage()); //[cite: 5]
    setFlashMessage('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error'); //[cite: 5]
}

// 5. ส่งกลับไปหน้า index.php เสมอ (Post-Redirect-Get Pattern)[cite: 5]
header('Location: index.php'); //[cite: 5]
exit; //[cite: 5]