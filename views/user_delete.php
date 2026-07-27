<?php
/**
 * Delete User Confirmation Page
 * Uses POST form for deletion (CSRF-safe)
 */

$page_title = 'حذف کاربر';

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

    $userToDelete = $db->fetch(
        "SELECT id, username, email, role, created_at
         FROM users
         WHERE id = ? AND deleted_at IS NULL",
        [$id]
    );

    if (!$userToDelete) {
        redirect_with_flash('users.php', 'error', 'کاربر یافت نشد.');
    }

    // Prevent self-deletion
    if ($id === (int)$_SESSION['user_id']) {
        redirect_with_flash('users.php', 'error', 'نمی‌توانید خود را حذف کنید.');
    }

} catch (Throwable $e) {
    error_log('Delete user page error: ' . $e->getMessage());
    redirect_with_flash('users.php', 'error', 'خطا در بارگذاری کاربر.');
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="../assets/css/users.css">

<div class="main-content users-page">

    <div class="page-header">
        <h1>حذف کاربر</h1>
    </div>

    <div class="card" style="max-width: 560px; margin: 0 auto;">

        <div style="text-align: center; padding: 20px;">

            <div style="
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                border-radius: 50%;
                background: rgba(214, 91, 91, 0.18);
                border: 1px solid rgba(214, 91, 91, 0.4);
                color: #d65b5b;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 32px;
                font-weight: 700;
            ">
                !
            </div>

            <h2 style="color: var(--text); margin-bottom: 12px;">
                آیا مطمئن هستید؟
            </h2>

            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                کاربر
                <strong style="color: var(--text);"><?= e($userToDelete['username']) ?></strong>
                (<?= e($userToDelete['email']) ?>)
                به‌صورت نرم حذف خواهد شد. این عمل قابل بازگشت نیست.
            </p>

            <form
                action="../configs/users_control.php"
                method="POST"
                style="display: flex; gap: 12px; justify-content: center;"
            >
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$userToDelete['id'] ?>">

                <button type="submit" class="btn btn-danger">
                    حذف کاربر
                </button>

                <a href="users.php" class="btn" style="padding: 10px 18px; font-size: 0.95rem;">
                    انصراف
                </a>
            </form>

        </div>

    </div>

</div>

</div>

<script src="../assets/js/notifications.js"></script>
</body>
</html>
