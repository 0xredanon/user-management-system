<?php
/**
 * Logout Handler
 *
 * Properly destroys the session and redirects to login.
 */

require_once __DIR__ . '/configs/app.php';

// Log activity before destroying session
if (isset($_SESSION['user_id'])) {
    log_activity($_SESSION['user_id'], 'logout', 'خروج از حساب کاربری');
}

// Clear session data
$_SESSION = [];

// Delete session cookie
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

// Destroy session
session_destroy();

// Redirect to login
redirect_with_flash('views/login.php', 'success', 'با موفقیت خارج شدید.');
