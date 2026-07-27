<?php
/**
 * Admin Management Page
 * Only accessible by super_admin
 * Features: list admins, create admin, delete admin, privilege escalation protection
 */

$page_title = 'مدیریت ادمین‌ها';

require_once __DIR__ . '/../configs/app.php';
require_once __DIR__ . '/../configs/ErrorHandler.php';
ErrorHandler::register();

// Require super admin access
$user = require_super_admin();

// ─── Handle Admin Actions ───────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        redirect_with_flash('admin.php', 'error', 'نشست شما منقضی شده است.');
    }
}

if ($action === 'create_admin') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $validator = new Validator($_POST);
    $validator
        ->required('username', 'نام کاربری الزامی است.')
        ->min('username', 3, 'نام کاربری باید حداقل ۳ کاراکتر باشد.')
        ->max('username', 50, 'نام کاربری نمی‌تواند بیش از ۵۰ کاراکتر باشد.')
        ->regex('username', '/^[a-zA-Z0-9_]+$/u', 'نام کاربری فقط شامر حروف، عدد و _ می‌تواند باشد.')
        ->required('email', 'ایمیل الزامی است.')
        ->email('email', 'ایمیل وارد شده معتبر نیست.')
        ->required('password', 'رمز عبور الزامی است.')
        ->min('password', 8, 'رمز عبور باید حداقل ۸ کاراکتر باشد.')
        ->passwordStrength('password', 'رمز عبور باید شامر حروف بزرگ، کوچک، عدد و نماد باشد.');

    if ($validator->fails()) {
        $errors = $validator->allErrors();
        redirect_with_flash('admin.php', 'error', $errors[0]);
    }

    try {
        $db = Database::getInstance();

        // Check for duplicates
        $existing = $db->fetch(
            "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1",
            [$username, $email]
        );

        if ($existing) {
            redirect_with_flash('admin.php', 'error', 'این نام کاربری یا ایمیل قبلاً ثبت شده است.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $db->prepare(
            "INSERT INTO users (username, email, password, role, status)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $username,
            $email,
            $hashedPassword,
            ROLE_ADMIN,
            STATUS_ACTIVE,
        ]);

        $newAdminId = (int) $db->lastInsertId();
        log_activity($_SESSION['user_id'], 'admin_create', "ادمین جدید ایجاد شد: {$username} (ID: {$newAdminId})");

        redirect_with_flash('admin.php', 'success', 'ادمین جدید با موفقیت ایجاد شد.');

    } catch (Throwable $e) {
        error_log('Create admin error: ' . $e->getMessage());
        redirect_with_flash('admin.php', 'error', 'خطا در ایجاد ادمین. لطفاً دوباره تلاش کنید.');
    }
}

if ($action === 'delete_admin') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        redirect_with_flash('admin.php', 'error', 'شناسه کاربر نامعتبر است.');
    }

    // Prevent self-deletion
    if ($id === (int)$_SESSION['user_id']) {
        redirect_with_flash('admin.php', 'error', 'نمی‌توانید خود را حذف کنید.');
    }

    try {
        $db = Database::getInstance();

        // Only allow deleting admins (not super_admins)
        $admin = $db->fetch(
            "SELECT username, role FROM users WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        if (!$admin) {
            redirect_with_flash('admin.php', 'error', 'کاربر یافت نشد.');
        }

        if ($admin['role'] === ROLE_SUPER_ADMIN) {
            redirect_with_flash('admin.php', 'error', 'نمی‌توانید سوپر ادمین را حذف کنید.');
        }

        // Soft delete
        $db->prepare("UPDATE users SET deleted_at = NOW(), status = ? WHERE id = ?")
            ->execute([STATUS_INACTIVE, $id]);

        log_activity($_SESSION['user_id'], 'admin_delete', "ادمین حذف شد: {$admin['username']} (ID: {$id})");

        redirect_with_flash('admin.php', 'success', 'ادمین با موفقیت حذف شد.');

    } catch (Throwable $e) {
        error_log('Delete admin error: ' . $e->getMessage());
        redirect_with_flash('admin.php', 'error', 'خطا در حذف ادمین.');
    }
}

// ─── Fetch Admins ───────────────────────────────────────────
try {
    $db = Database::getInstance();

    $admins = $db->fetchAll(
        "SELECT id, username, email, role, status, created_at
         FROM users
         WHERE role IN (?, ?) AND deleted_at IS NULL
         ORDER BY id DESC",
        [ROLE_ADMIN, ROLE_SUPER_ADMIN]
    );

} catch (Throwable $e) {
    error_log('Admin page error: ' . $e->getMessage());
    $admins = [];
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="../assets/css/users.css">

<div class="main-content users-page">

    <div class="users-header">
        <h1>مدیریت ادمین‌ها</h1>
    </div>

    <!-- Create Admin Form -->
    <div class="card">
        <h2 style="margin-bottom: 20px;">ایجاد ادمین جدید</h2>

        <form class="user-form" action="admin.php" method="POST" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_admin">

            <div class="field">
                <label for="username">نام کاربری</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    required
                    minlength="3"
                    maxlength="50"
                    pattern="[a-zA-Z0-9_]+"
                    title="فقط حروف، عدد و _ مجاز است"
                >
            </div>

            <div class="field">
                <label for="email">ایمیل</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    maxlength="255"
                >
            </div>

            <div class="field">
                <label for="password">رمز عبور</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
                <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 4px;">
                    حداقل ۸ کاراکتر، شامر حروف بزرگ، کوچک، عدد و نماد
                </small>
            </div>

            <button class="btn btn-primary" type="submit">
                ایجاد ادمین
            </button>
        </form>
    </div>

    <!-- Admins List -->
    <div class="card users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>نام کاربری</th>
                    <th>ایمیل</th>
                    <th>نقش</th>
                    <th>وضعیت</th>
                    <th>تاریخ ثبت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="7" class="users-empty">
                            هیچ ادمینی یافت نشد.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= (int)$admin['id'] ?></td>
                            <td><?= e($admin['username']) ?></td>
                            <td><?= e($admin['email']) ?></td>
                            <td>
                                <span class="role-badge role-<?= e($admin['role']) ?>">
                                    <?= e(ucfirst(str_replace('_', ' ', $admin['role']))) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($admin['status'] === STATUS_ACTIVE): ?>
                                    <span class="role-badge status-active">
                                        فعال
                                    </span>
                                <?php else: ?>
                                    <span class="role-badge status-inactive">
                                        غیرفعال
                                    </span>
                                <?php endif; ?>

                            </td>
                            <td><?= e(format_date($admin['created_at'])) ?></td>
                            <td>
                                <?php if ($admin['id'] !== (int)$_SESSION['user_id'] && $admin['role'] !== ROLE_SUPER_ADMIN): ?>
                                    <form
                                        action="admin.php"
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirmDeleteAdmin(this)"
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_admin">
                                        <input type="hidden" name="id" value="<?= (int)$admin['id'] ?>">
                                        <button type="submit" class="action-btn btn-delete">
                                            حذف
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="action-btn" style="color: var(--text-muted);">
                                        سوپر ادمین
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</div>

<script src="../assets/js/notifications.js"></script>
<script>
    function confirmDeleteAdmin(form) {
        showConfirmation('آیا از حذف این ادمین مطمئن هستید؟', {
            onConfirm: () => form.submit(),
            confirmText: 'حذف',
            cancelText: 'لغو',
        });
        return false;
    }
</script>
</body>
</html>
