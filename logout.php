<?php
require_once 'config.php';

// ล้างค่าตัวแปรทั้งหมดใน Session
$_SESSION = array();

// ทำลาย Session Cookie บนเบราว์เซอร์
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// เริ่ม Session ใหม่สั้นๆ เพื่อส่ง Flash Message แจ้งเตือน
session_start();
setFlashMessage('คุณได้ออกจากระบบเรียบร้อยแล้ว', 'success');

header('Location: index.php');
exit;