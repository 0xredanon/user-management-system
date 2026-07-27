<?php
/**
 * Add User Page
 */

$page_title = 'افزودن کاربر';

require_once __DIR__ . '/../configs/app.php';
require_once __DIR__ . '/../configs/ErrorHandler.php';
ErrorHandler::register();

// Require admin access
$user = require_admin();

require_once 'header.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="../assets/css/users.css">

<div class="main-content users-page">

    <div class="page-header">
        <h1>افزودن کاربر جدید</h1>
    </div>

    <form class="user-form"
          action="../configs/users_control.php"
          method="POST"
          novalidate>

        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">

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

        <div class="field">
            <label for="role">نقش</label>
            <select
                id="role"
                name="role"
                required>
                <option value="user">کاربر</option>
                <option value="admin">ادمین</option>
                <?php if (has_role(ROLE_SUPER_ADMIN)): ?>
                    <option value="super_admin">سوپر ادمین</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="field">
            <label for="status">وضعیت</label>
            <select
                id="status"
                name="status"
                required>
                <option value="active">فعال</option>
                <option value="inactive">غیرفعال</option>
            </select>
        </div>

        <button class="btn btn-primary" type="submit">
            ثبت کاربر
        </button>

    </form>

</div>

</div>

<script src="../assets/js/notifications.js"></script>
</body>
</html>
