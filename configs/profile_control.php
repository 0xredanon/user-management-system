<?php
/**
 * Profile Controller
 *
 * Handles profile updates with:
 * - CSRF protection
 * - Rate limiting
 * - Input validation
 * - Avatar upload with image validation
 * - Password change with strength validation
 * - Email uniqueness check
 * - Session refresh
 * - Activity logging
 */

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/csrf.php';

// Require authentication
$user = require_login();

// Rate limiting for profile updates
$limiter = profile_rate_limiter();
if (!$limiter->attempt()) {
    $retryAfter = $limiter->retryAfter();
    redirect_with_flash('../views/profile.php', 'error', "درخواست بیش از حد. لطفاً {$retryAfter} ثانیه صبر کنید.");
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/profile.php');
}

// ─── CSRF Validation ────────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    redirect_with_flash('../views/profile.php', 'error', 'نشست شما منقضی شده است.');
}

// ─── Input ──────────────────────────────────────────────────
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
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
    ->image('avatar', 'فقط فایل‌های تصویری مجاز هستند.');

// Password validation (only if changing)
$changingPassword = !empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword);

if ($changingPassword) {
    $validator
        ->required('current_password', 'رمز عبور فعلی الزامی است.')
        ->required('new_password', 'رمز عبور جدید الزامی است.')
        ->min('new_password', 8, 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.')
        ->passwordStrength('new_password', 'رمز عبور جدید باید شامر حروف بزرگ، کوچک، عدد و نماد باشد.')
        ->required('confirm_password', 'تکرار رمز عبور الزامی است.')
        ->matches('confirm_password', 'new_password', 'تکرار رمز عبور صحیح نیست.');
}

if ($validator->fails()) {
    $errors = $validator->allErrors();
    redirect_with_flash('../views/profile.php', 'error', $errors[0]);
}

// ─── Database Operations ────────────────────────────────────
try {
    $db = Database::getInstance();

    // Get current user data
    $currentUser = $db->fetch(
        "SELECT id, username, email, password FROM users WHERE id = ? AND deleted_at IS NULL",
        [$user['id']]
    );

    if (!$currentUser) {
        session_destroy();
        redirect_with_flash('../views/login.php', 'error', 'حساب کاربری یافت نشد.');
    }

    // Check for duplicate username/email (excluding current user)
    $existing = $db->fetch(
        "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1",
        [$username, $email, $user['id']]
    );

    if ($existing) {
        redirect_with_flash('../views/profile.php', 'error', 'این نام کاربری یا ایمیل قبلاً ثبت شده است.');
    }

    // Handle avatar upload
    $avatar = $currentUser['avatar']; // Keep existing avatar by default

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['avatar'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate image using getimagesize (always available, no extension needed)
            $imgInfo = @getimagesize($file['tmp_name']);
            if ($imgInfo === false) {
                redirect_with_flash('../views/profile.php', 'error', 'فقط فایل‌های تصویری مجاز هستند.');
            }
            $mime = $imgInfo['mime'];

            if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
                redirect_with_flash('../views/profile.php', 'error', 'فقط فایل‌های تصویری مجاز هستند.');
            }

            if ($file['size'] > MAX_UPLOAD_SIZE) {
                redirect_with_flash('../views/profile.php', 'error', 'اندازه فایل بیش از حد مجاز است.');
            }

            // Generate unique filename
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $avatarName = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $avatarPath = UPLOADS_PROFILES_PATH . '/' . $avatarName;

            // Ensure directory exists
            if (!is_dir(UPLOADS_PROFILES_PATH)) {
                mkdir(UPLOADS_PROFILES_PATH, 0755, true);
            }

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $avatarPath)) {
                // Delete old avatar
                if (!empty($currentUser['avatar'])) {
                    $oldAvatarPath = UPLOADS_PROFILES_PATH . '/' . $currentUser['avatar'];
                    if (file_exists($oldAvatarPath)) {
                        @unlink($oldAvatarPath);
                    }
                }

                $avatar = $avatarName;
            } else {
                redirect_with_flash('../views/profile.php', 'error', 'خطا در آپلود آواتار.');
            }
        }
    }

    // Handle password change
    if ($changingPassword) {
        // Verify current password
        if (!password_verify($currentPassword, $currentUser['password'])) {
            redirect_with_flash('../views/profile.php', 'error', 'رمز عبور فعلی اشتباه است.');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update with new password
        $db->prepare(
            "UPDATE users SET username = ?, email = ?, password = ?, avatar = ? WHERE id = ?"
        )->execute([$username, $email, $hashedPassword, $avatar, $user['id']]);

        log_activity($user['id'], 'profile_update', "پروفایل و رمز عبور به‌روزرسانی شد");
    } else {
        // Update without password change
        $db->prepare(
            "UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?"
        )->execute([$username, $email, $avatar, $user['id']]);

        log_activity($user['id'], 'profile_update', "پروفایل به‌روزرسانی شد");
    }

    // Refresh session
    $_SESSION['username'] = $username;

    // Clear rate limiter on success
    $limiter->clear();

    redirect_with_flash('../views/profile.php', 'success', 'پروفایل با موفقیت به‌روزرسانی شد.');

} catch (Throwable $e) {
    error_log('Profile update error: ' . $e->getMessage());
    redirect_with_flash('../views/profile.php', 'error', 'خطا در به‌روزرسانی پروفایل. لطفاً دوباره تلاش کنید.');
}

