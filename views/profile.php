<?php
/**
 * Profile Page
 * Features: profile info, avatar upload, password change, email change
 */

$page_title = 'پروفایل';

require_once __DIR__ . '/../configs/app.php';
require_once __DIR__ . '/../configs/ErrorHandler.php';
ErrorHandler::register();

// Require authentication
$user = require_login();

// Get flash message
$flash = get_flash();

require_once 'header.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="../assets/css/profile.css">

<div class="main-content profile-page">

    <div class="profile-header">
        <div>
            <h1>پروفایل</h1>
            <p class="subtitle">اطلاعات حساب کاربری شما</p>
        </div>
    </div>

    <div class="profile-grid">

        <!-- Profile Card -->
        <div class="card profile-card">

            <div class="profile-avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="../uploads/profiles/<?= e($user['avatar']) ?>"
                         alt="Avatar"
                         style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <?= strtoupper(substr($user['username'], 0, 1)); ?>
                <?php endif; ?>
            </div>

            <h2><?= e($user['username']) ?></h2>

            <span class="role-badge role-<?= e($user['role']) ?>">
                <?= e(ucfirst(str_replace('_', ' ', $user['role']))) ?>
            </span>

            <div class="profile-meta">

                <div>
                    <span>ایمیل</span>
                    <strong><?= e($user['email']) ?></strong>
                </div>

                <div>
                    <span>وضعیت</span>
                    <strong>
                        <?php if ($user['status'] === STATUS_ACTIVE): ?>
                            فعال
                        <?php else: ?>
                            غیرفعال
                        <?php endif; ?>
                    </strong>
                </div>

                <div>
                    <span>عضویت</span>
                    <strong><?= e(format_date($user['created_at'])) ?></strong>
                </div>

                <div>
                    <span>شناسه</span>
                    <strong>#<?= (int)$user['id'] ?></strong>
                </div>

            </div>

        </div>

        <!-- Edit Form -->
        <div class="card profile-form-card">

            <h2>ویرایش اطلاعات</h2>

            <form
                id="profileForm"
                class="profile-form"
                action="../configs/profile_control.php"
                method="POST"
                enctype="multipart/form-data"
            >

                <?= csrf_field() ?>

                <!-- Avatar Upload -->
                <div class="field">
                    <label>آواتار</label>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div class="profile-avatar" style="width: 60px; height: 60px; font-size: 24px;">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="../uploads/profiles/<?= e($user['avatar']) ?>"
                                     alt="Avatar"
                                     style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <?= strtoupper(substr($user['username'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <input
                            type="file"
                            name="avatar"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            style="flex: 1;"
                        >
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 4px;">
                        حداکثر ۲ مگابایت. فقط jpg, png, gif, webp
                    </small>
                </div>

                <hr>

                <div class="field">
                    <label>نام کاربری</label>
                    <input
                        type="text"
                        name="username"
                        value="<?= e($user['username']) ?>"
                        required
                        minlength="3"
                        maxlength="50"
                        pattern="[a-zA-Z0-9_]+"
                        title="فقط حروف، عدد و _ مجاز است"
                    >
                </div>

                <div class="field">
                    <label>ایمیل</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= e($user['email']) ?>"
                        required
                        maxlength="255"
                    >
                </div>

                <hr>

                <div class="field">
                    <label>رمز عبور فعلی</label>
                    <input
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                    >
                </div>

                <div class="field">
                    <label>رمز عبور جدید</label>
                    <input
                        type="password"
                        name="new_password"
                        autocomplete="new-password"
                        minlength="8"
                    >
                </div>

                <div class="field">
                    <label>تکرار رمز عبور جدید</label>
                    <input
                        type="password"
                        name="confirm_password"
                        autocomplete="new-password"
                        minlength="8"
                    >
                </div>

                <button
                    class="btn btn-primary"
                    type="submit"
                    id="saveBtn"
                >
                    ذخیره تغییرات
                </button>

            </form>

        </div>

    </div>

</div>

<script src="../assets/js/notifications.js"></script>
<script src="../assets/js/profile.js"></script>
</body>
</html>
