<?php
/**
 * Edit User Page
 */

$page_title = 'ویرایش کاربر';

require_once __DIR__ . '/../configs/app.php';
require_once __DIR__ . '/../configs/ErrorHandler.php';
ErrorHandler::register();

// Require admin access
$user = require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    redirect('users.php');
}

try {
    $db = Database::getInstance();

    $userToEdit = $db->fetch(
        "SELECT id, username, email, role, status
         FROM users
         WHERE id = ? AND deleted_at IS NULL",
        [$id]
    );

    if (!$userToEdit) {
        redirect_with_flash('users.php', 'error', 'کاربر یافت نشد.');
    }

} catch (Throwable $e) {
    error_log('Edit user page error: ' . $e->getMessage());
    redirect_with_flash('users.php', 'error', 'خطا در بارگذاری کاربر.');
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="../assets/css/users.css">

<div class="main-content users-page">

    <div class="page-header">
        <h1>
            ویرایش
            <?= e($userToEdit['username']) ?>
        </h1>
    </div>

    <form class="user-form" action="../configs/users_control.php" method="POST" novalidate>

        <?= csrf_field() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= (int)$userToEdit['id'] ?>">

        <div class="field">
            <label>نام کاربری</label>
            <input
                type="text"
                name="username"
                required
                minlength="3"
                maxlength="50"
                pattern="[a-zA-Z0-9_]+"
                title="فقط حروف، عدد و _ مجاز است"
                value="<?= e($userToEdit['username']) ?>"
            >
        </div>

        <div class="field">
            <label>ایمیل</label>
            <input
                type="email"
                name="email"
                required
                maxlength="255"
                value="<?= e($userToEdit['email']) ?>"
            >
        </div>

        <div class="field">
            <label>رمز عبور جدید</label>
            <input
                type="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                placeholder="در صورت عدم تغییر خالی بگذارید"
            >
            <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 4px;">
                حداقل ۸ کاراکتر، شامر حروف بزرگ، کوچک، عدد و نماد
            </small>
        </div>

        <div class="field">
            <label>نقش</label>
            <select name="role">
                <option value="user" <?= $userToEdit['role'] === 'user' ? 'selected' : '' ?>>
                    کاربر
                </option>
                <option value="admin" <?= $userToEdit['role'] === 'admin' ? 'selected' : '' ?>>
                    ادمین
                </option>
                <?php if (has_role(ROLE_SUPER_ADMIN)): ?>
                    <option value="super_admin" <?= $userToEdit['role'] === 'super_admin' ? 'selected' : '' ?>>
                        سوپر ادمین
                    </option>
                <?php endif; ?>
            </select>
        </div>

        <div class="field">
            <label>وضعیت</label>
            <select name="status">
                <option value="active" <?= $userToEdit['status'] === 'active' ? 'selected' : '' ?>>
                    فعال
                </option>
                <option value="inactive" <?= $userToEdit['status'] === 'inactive' ? 'selected' : '' ?>>
                    غیرفعال
                </option>
            </select>
        </div>

        <button class="btn btn-primary" type="submit">
            ذخیره تغییرات
        </button>

    </form>

</div>

</div>

<script src="../assets/js/notifications.js"></script>
</body>
</html>
