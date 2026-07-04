<?php
require_once __DIR__ . '/../includes/functions.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a fresh session to set the logout success message
session_start();
setFlash('success', 'Anda telah berhasil keluar.');
redirect('/Amimi/index.php');
