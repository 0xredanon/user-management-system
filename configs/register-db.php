<?php
/**
 * Registration Handler
 *
 * Handles user registration with:
 * - Rate limiting
 * - CSRF protection
 * - Input validation (username, email, password strength)
 * - Duplicate prevention
 * - Password hashing
 * - Structured error handling
 */

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/csrf.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/register.php');
}

// ─── CSRF Validation ────────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    redirect_with_flash('../views/register.php', 'error', 'نشست شما منقضی شده است. لطفاً دوباره تلاش کنید.');
}

// ─── Rate Limiting ──────────────────────────────────────────
$limiter = register_rate_limiter();

if ($limiter->isExceeded()) {
    $retryAfter = $limiter->retryAfter();
    redirect_with_flash(
        'register.php',
        'error',
        "تلاش بیش از حد برای ثبت‌نام. لطفاً {$retryAfter} ثانیه صبر کنید."
    );
}

// ─── Input ──────────────────────────────────────────────────
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// ─── Validation ─────────────────────────────────────────────
$validator = new Validator($_POST);
$validator
    ->required('username', 'نام کاربری الزامی است.')
    ->min('username', 3, 'نام کاربری باید حداقل ۳ کاراکتر باشد.')
    ->max('username', 50, 'نام کاربری نمی‌تواند بیش از ۵۰ کاراکتر باشد.')
    ->regex('username', '/^[a-zA-Z0-9_]+$/u', 'نام کاربری فقط شامر حروف، عدد و _ می‌تواند باشد.')
    ->required('email', 'ایمیل الزامی است.')
    ->email('email', 'ایمیل وارد شده معتبر نیست.')
    ->max('email', 255, 'ایمیل نمی‌تواند بیش از ۲۵۵ کاراکتر باشد.')
    ->required('password', 'رمز عبور الزامی است.')
    ->min('password', 8, 'رمز عبور باید حداقل ۸ کاراکتر باشد.')
    ->passwordStrength('password', 'رمز عبور باید شامر حروف بزرگ، کوچک، عدد و نماد باشد.')
    ->required('confirm_password', 'تکرار رمز عبور الزامی است.')
    ->matches('confirm_password', 'password', 'تکرار رمز عبور صحیح نیست.');

if ($validator->fails()) {
    $limiter->attempt();
    $errors = $validator->allErrors();
    redirect_with_flash('../views/register.php', 'error', $errors[0]);
}

// ─── Duplicate Check ────────────────────────────────────────
try {
    $db = Database::getInstance();

    $existing = $db->fetch(
        "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1",
        [$username, $email]
    );

    if ($existing) {
        $limiter->attempt();
        redirect_with_flash('../views/register.php', 'error', 'این نام کاربری یا ایمیل قبلاً ثبت شده است.');
    }

    // ─── Create User ────────────────────────────────────────
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $role = ROLE_USER; // Always default to 'user' — no privilege escalation

    $db->prepare(
        "INSERT INTO users (username, email, password, role, status)
         VALUES (?, ?, ?, ?, ?)"
    )->execute([
        $username,
        $email,
        $hashedPassword,
        $role,
        STATUS_ACTIVE,
    ]);

    $userId = (int) $db->lastInsertId();

    // Log activity
    log_activity($userId, 'register', 'ثبت‌نام کاربر جدید: ' . $username);

    // Clear rate limiter on successful registration
    $limiter->clear();

    redirect_with_flash('../views/login.php', 'success', 'ثبت‌نام با موفقیت انجام شد. حالا وارد شوید.');

} catch (Throwable $e) {
    error_log('Registration error: ' . $e->getMessage());
    redirect_with_flash('../views/register.php', 'error', 'خطایی در ثبت‌نام رخ داده است. لطفاً دوباره تلاش کنید.');
}

