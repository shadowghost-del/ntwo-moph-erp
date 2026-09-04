<?php
// config.php - ระบบตั้งค่าและเชื่อมต่อฐานข้อมูล MOPH ERP Dashboard
// รองรับ PHP 7.4.33 และ MySQL 8.0.42 บน CentOS Linux 7

date_default_timezone_set('Asia/Bangkok');
error_reporting(E_ALL);
ini_set('display_errors', 0); // ซ่อน Error เมื่อรัน Production

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 วัน
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'dbname');
define('DB_USER', 'user');
define('DB_PASS', ''); // เปลี่ยนรหัสผ่านให้ตรงกับฐานข้อมูลจริง
define('DB_CHARSET', 'utf8mb4');

/**
 * ฟังก์ชันสร้างและคืนค่าวัตถุ PDO สำหรับเชื่อมต่อ MySQL Database
 * @return PDO
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=%s", DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาตรวจสอบการตั้งค่าเซิร์ฟเวอร์");
        }
    }
    
    return $pdo;
}

/**
 * ฟังก์ชันป้องกัน XSS สำหรับแสดงผลใน HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * ฟังก์ชันแปลงรูปแบบ DateTime จาก MySQL (YYYY-MM-DD HH:MM:SS) เป็น (DD/MM/YYYY HH:MM)
 */
function formatThaiDateTime($dateTimeStr) {
    if (empty($dateTimeStr) || $dateTimeStr === '0000-00-00 00:00:00') {
        return '-';
    }
    $timestamp = strtotime($dateTimeStr);
    if (!$timestamp) return $dateTimeStr;
    
    return date('d/m/Y H:i', $timestamp);
}

/**
 * ฟังก์ชันตรวจสอบสิทธิ์ Super Admin
 */
function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'SUPER_ADMIN';
}

/**
 * ตั้งค่าข้อความแจ้งเตือนข้าม Request (Flash Message)
 */
function setFlashMessage($msg, $type = 'success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}

/**
 * อ่านข้อความแจ้งเตือน Flash Message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        return ['message' => $msg, 'type' => $type];
    }
    return null;
}