<?php
/**
 * Login Handler
 *
 * Handles user authentication with:
 * - Rate limiting (prevents brute force)
 * - CSRF protection
 * - Session regeneration (prevents session fixation)
 * - Secure session management
 * - Proper redirect to dashboard
 * - Structured error handling
 */

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/csrf.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/login.php');
}

// ─── CSRF Validation ────────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    redirect_with_flash('../views/login.php', 'error', 'نشست شما منقضی شده است. لطفاً دوباره تلاش کنید.');
}

// ─── Rate Limiting ──────────────────────────────────────────
$limiter = login_rate_limiter();

if ($limiter->isExceeded()) {
    $retryAfter = $limiter->retryAfter();
    redirect_with_flash(
        'login.php',
        'error',
        "تلاش بیش از حد برای ورود. لطفاً {$retryAfter} ثانیه صبر کنید."
    );
}

// ─── Input ──────────────────────────────────────────────────
$usernameOrEmail = trim($_POST['username-or-email'] ?? '');
$password = $_POST['password'] ?? '';

// ─── Validation ─────────────────────────────────────────────
$validator = new Validator($_POST);
$validator
    ->required('username-or-email', 'نام کاربری یا ایمیل الزامی است.')
    ->required('password', 'رمز عبور الزامی است.');

if ($validator->fails()) {
    $limiter->attempt();
    redirect_with_flash('../views/login.php', 'error', $validator->firstError('username-or-email') ?? $validator->firstError('password'));
}

// ─── Authentication ─────────────────────────────────────────
try {
    $db = Database::getInstance();

    $user = $db->fetch(
        "SELECT id, username, email, password, role, status
         FROM users
         WHERE (username = ? OR email = ?) AND deleted_at IS NULL
         LIMIT 1",
        [$usernameOrEmail, $usernameOrEmail]
    );

    if (!$user) {
        $limiter->attempt();
        redirect_with_flash('../views/login.php', 'error', 'نام کاربری یا رمز عبور اشتباه است.');
    }

    // Check if user is inactive
    if ($user['status'] !== STATUS_ACTIVE) {
        $limiter->attempt();
        redirect_with_flash('../views/login.php', 'error', 'حساب کاربری شما غیرفعال است.');
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        $limiter->attempt();
        redirect_with_flash('../views/login.php', 'error', 'نام کاربری یا رمز عبور اشتباه است.');
    }

    // ─── Success ─────────────────────────────────────────────
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Store user data in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['ip'] = get_client_ip();
    $_SESSION['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $_SESSION['last_activity'] = time();
    $_SESSION['initiated'] = true;

    // Clear rate limiter on successful login
    $limiter->clear();

    // Log activity
    log_activity($user['id'], 'login', 'ورود موفق به حساب کاربری ' . $user['username']);

    // Redirect to dashboard
    redirect_with_flash('../views/dashboard.php', 'success', 'ورود موفقیت‌آمیز. خوش آمدید!');

} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());
    redirect_with_flash('../views/login.php', 'error', 'خطایی در سرور رخ داده است. لطفاً دوباره تلاش کنید.');
}

