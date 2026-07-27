<?php
/**
 * Users Controller
 *
 * Handles CRUD operations for users with:
 * - CSRF protection
 * - Admin authorization
 * - Input validation
 * - Duplicate prevention
 * - Password hashing
 * - Soft delete
 * - Transactions
 * - Activity logging
 */

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/csrf.php';

// Require admin access
$user = require_admin();

// Only accept POST for add/edit, GET for delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ==================== Add User ====================
    case 'add':
        handleAddUser();
        break;

    // ==================== Edit User ====================
    case 'edit':
        handleEditUser();
        break;

    // ==================== Delete User (soft) ====================
    case 'delete':
        handleDeleteUser();
        break;

    // ==================== Toggle Status ====================
    case 'toggle_status':
        handleToggleStatus();
        break;

    default:
        redirect('../views/users.php');
        break;
}

exit();

// ─── Handler Functions ─────────────────────────────────────

/**
 * Handle adding a new user.
 */
function handleAddUser(): void
{
    // CSRF validation
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        redirect_with_flash('../views/user_add.php', 'error', 'نشست شما منقضی شده است.');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ROLE_USER;
    $status = $_POST['status'] ?? STATUS_ACTIVE;

    // Validation
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
        ->in('role', [ROLE_USER, ROLE_ADMIN, ROLE_SUPER_ADMIN], 'نقش نامعتبر است.')
        ->in('status', [STATUS_ACTIVE, STATUS_INACTIVE], 'وضعیت نامعتبر است.');

    if ($validator->fails()) {
        $errors = $validator->allErrors();
        redirect_with_flash('../views/user_add.php', 'error', $errors[0]);
    }

    // Prevent privilege escalation: only super_admin can create admins/super_admins
    if ($role !== ROLE_USER && !has_role(ROLE_SUPER_ADMIN)) {
        $role = ROLE_USER;
    }

    try {
        $db = Database::getInstance();

        // Check for duplicates
        $existing = $db->fetch(
            "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1",
            [$username, $email]
        );

        if ($existing) {
            redirect_with_flash('../views/user_add.php', 'error', 'این نام کاربری یا ایمیل قبلاً ثبت شده است.');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $db->prepare(
            "INSERT INTO users (username, email, password, role, status)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $username,
            $email,
            $hashedPassword,
            $role,
            $status,
        ]);

        $newUserId = (int) $db->lastInsertId();

        log_activity($_SESSION['user_id'], 'user_create', "کاربر جدید ایجاد شد: {$username} (ID: {$newUserId})");

        redirect_with_flash('../views/users.php', 'success', 'کاربر جدید با موفقیت افزوده شد.');

    } catch (Throwable $e) {
        error_log('Add user error: ' . $e->getMessage());
        redirect_with_flash('../views/user_add.php', 'error', 'خطا در افزودن کاربر. لطفاً دوباره تلاش کنید.');
    }
}

/**
 * Handle editing a user.
 */
function handleEditUser(): void
{
    // CSRF validation
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        redirect_with_flash('../views/users.php', 'error', 'نشست شما منقضی شده است.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ROLE_USER;
    $status = $_POST['status'] ?? STATUS_ACTIVE;

    if ($id <= 0) {
        redirect_with_flash('../views/users.php', 'error', 'شناسه کاربر نامعتبر است.');
    }

    // Validation
    $validator = new Validator($_POST);
    $validator
        ->required('username', 'نام کاربری الزامی است.')
        ->min('username', 3, 'نام کاربری باید حداقل ۳ کاراکتر باشد.')
        ->max('username', 50, 'نام کاربری نمی‌تواند بیش از ۵۰ کاراکتر باشد.')
        ->regex('username', '/^[a-zA-Z0-9_]+$/u', 'نام کاربری فقط شامر حروف، عدد و _ می‌تواند باشد.')
        ->required('email', 'ایمیل الزامی است.')
        ->email('email', 'ایمیل وارد شده معتبر نیست.')
        ->max('email', 255, 'ایمیل نمی‌تواند بیش از ۲۵۵ کاراکتر باشد.')
        ->in('role', [ROLE_USER, ROLE_ADMIN, ROLE_SUPER_ADMIN], 'نقش نامعتبر است.')
        ->in('status', [STATUS_ACTIVE, STATUS_INACTIVE], 'وضعیت نامعتبر است.');

    // Password is optional (only if changing)
    if (!empty($password)) {
        $validator
            ->min('password', 8, 'رمز عبور باید حداقل ۸ کاراکتر باشد.')
            ->passwordStrength('password', 'رمز عبور باید شامر حروف بزرگ، کوچک، عدد و نماد باشد.');
    }

    if ($validator->fails()) {
        $errors = $validator->allErrors();
        redirect_with_flash('../views/user_edit.php?id={$id}', 'error', $errors[0]);
    }

    // Prevent privilege escalation: only super_admin can assign admin/super_admin roles
    if ($role !== ROLE_USER && !has_role(ROLE_SUPER_ADMIN)) {
        // If not super_admin, check if the user being edited is already an admin
        // If so, allow keeping the role but don't allow escalating
        $db = Database::getInstance();
        $currentUser = $db->fetch("SELECT role FROM users WHERE id = ?", [$id]);
        if (!$currentUser || ($currentUser['role'] !== ROLE_USER && !has_role(ROLE_SUPER_ADMIN))) {
            $role = $currentUser['role'] ?? ROLE_USER;
        }
    }

    try {
        $db = Database::getInstance();

        // Check for duplicates (excluding current user)
        $existing = $db->fetch(
            "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1",
            [$username, $email, $id]
        );

        if ($existing) {
            redirect_with_flash('../views/user_edit.php?id={$id}', 'error', 'این نام کاربری یا ایمیل قبلاً ثبت شده است.');
        }

        // Update user
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare(
                "UPDATE users SET username = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?"
            )->execute([$username, $email, $hashedPassword, $role, $status, $id]);
        } else {
            $db->prepare(
                "UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?"
            )->execute([$username, $email, $role, $status, $id]);
        }

        log_activity($_SESSION['user_id'], 'user_update', "کاربر ویرایش شد: {$username} (ID: {$id})");

        redirect_with_flash('../views/users.php', 'success', 'کاربر با موفقیت ویرایش شد.');

    } catch (Throwable $e) {
        error_log('Edit user error: ' . $e->getMessage());
        redirect_with_flash('../views/user_edit.php?id={$id}', 'error', 'خطا در ویرایش کاربر. لطفاً دوباره تلاش کنید.');
    }
}

/**
 * Handle soft-deleting a user.
 */
function handleDeleteUser(): void
{
    // CSRF validation
    $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        redirect_with_flash('../views/users.php', 'error', 'نشست شما منقضی شده است.');
    }

    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        redirect_with_flash('../views/users.php', 'error', 'شناسه کاربر نامعتبر است.');
    }

    // Prevent self-deletion
    if ($id === (int)$_SESSION['user_id']) {
        redirect_with_flash('../views/users.php', 'error', 'نمی‌توانید خود را حذف کنید.');
    }

    try {
        $db = Database::getInstance();

        // Check if user exists
        $user = $db->fetch("SELECT username FROM users WHERE id = ? AND deleted_at IS NULL", [$id]);

        if (!$user) {
            redirect_with_flash('../views/users.php', 'error', 'کاربر یافت نشد.');
        }

        // Soft delete
        $db->prepare("UPDATE users SET deleted_at = NOW(), status = ? WHERE id = ?")
            ->execute([STATUS_INACTIVE, $id]);

        log_activity($_SESSION['user_id'], 'user_delete', "کاربر حذف شد: {$user['username']} (ID: {$id})");

        redirect_with_flash('../views/users.php', 'success', 'کاربر با موفقیت حذف شد.');

    } catch (Throwable $e) {
        error_log('Delete user error: ' . $e->getMessage());
        redirect_with_flash('../views/users.php', 'error', 'خطا در حذف کاربر. لطفاً دوباره تلاش کنید.');
    }
}

/**
 * Handle toggling user status.
 */
function handleToggleStatus(): void
{
    // CSRF validation
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        redirect_with_flash('../views/users.php', 'error', 'نشست شما منقضی شده است.');
    }

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        redirect_with_flash('../views/users.php', 'error', 'شناسه کاربر نامعتبر است.');
    }

    // Prevent self-deactivation
    if ($id === (int)$_SESSION['user_id']) {
        redirect_with_flash('../views/users.php', 'error', 'نمی‌توانید وضعیت خود را تغییر دهید.');
    }

    try {
        $db = Database::getInstance();

        $user = $db->fetch("SELECT status FROM users WHERE id = ? AND deleted_at IS NULL", [$id]);

        if (!$user) {
            redirect_with_flash('../views/users.php', 'error', 'کاربر یافت نشد.');
        }

        $newStatus = $user['status'] === STATUS_ACTIVE ? STATUS_INACTIVE : STATUS_ACTIVE;

        $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);

        log_activity($_SESSION['user_id'], 'user_status_toggle', "وضعیت کاربر تغییر یافت: ID {$id} به {$newStatus}");

        redirect_with_flash('../views/users.php', 'success', 'وضعیت کاربر با موفقیت تغییر یافت.');

    } catch (Throwable $e) {
        error_log('Toggle status error: ' . $e->getMessage());
        redirect_with_flash('../views/users.php', 'error', 'خطا در تغییر وضعیت کاربر.');
    }
}

