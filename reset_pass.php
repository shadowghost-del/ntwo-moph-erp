<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // สร้าง BCRYPT Hash จริงสำหรับรหัสผ่าน 03@Feb#2568
    $realHash = password_hash('03@Feb#2568', PASSWORD_BCRYPT);
    
    // อัปเดตลงตาราง users
    $stmt = $pdo->prepare("UPDATE users SET password = :hash WHERE username = 'nexushealth'");
    $stmt->execute(['hash' => $realHash]);
    
    echo "<h2 style='color:green;'>✅ อัปเดตรหัสผ่านเรียบร้อยแล้ว!</h2>";
    echo "<p>Hash ใหม่ของคุณคือ: <code>" . $realHash . "</code></p>";
    echo "<p><a href='index.php'>กลับหน้าล็อกอิน</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</h2>";
}