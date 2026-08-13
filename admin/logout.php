<?php
session_start();

// kosongin semua session
$_SESSION = [];

// hapus cookie session kalo ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// hancurkan session
session_destroy();

// balik ke halaman login + kirim pesan logout
header("Location: login.php?pesan=logout");
exit();
?>