<?php
require_once __DIR__ . '/../configs/app.php';

// If already logged in, redirect to dashboard
$user = current_user();
if ($user) {
    redirect('dashboard.php');
}

// Get flash message
$flash = get_flash();

$page_title = 'ثبت‌نام';
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — <?= e(APP_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
</head>

<body class="login-page">

<div class="glow"></div>

<form class="login-card" action="../configs/register-db.php" method="POST" novalidate>
    <?= csrf_field() ?>

    <div class="header">
        <h1 class="title">ثبت‌نام</h1>
        <p class="subtitle">حساب کاربری جدید بساز</p>
    </div>

    <div class="field">
        <label for="username">نام کاربری</label>
        <input
            type="text"
            id="username"
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
            type="email"
            id="email"
            name="email"
            required
            maxlength="255"
        >
    </div>

    <div class="field">
        <label for="password">رمز عبور</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            minlength="8"
            autocomplete="new-password"
        >
        <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px; display: block;">
            حداقل ۸ کاراکتر، شامر حروف بزرگ، کوچک، عدد و نماد
        </small>
    </div>

    <div class="field">
        <label for="confirm_password">تکرار رمز عبور</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            required
            minlength="8"
            autocomplete="new-password"
        >
    </div>

    <button type="submit" class="submit-btn">
        ثبت‌نام
    </button>

    <p style="text-align:center;margin-top:20px;">
        قبلاً حساب داری؟
        <a href="login.php">ورود</a>
    </p>

</form>

<script>
    // Pass flash message to the notification system
    window._flashMessage = <?= json_encode($flash ?: [], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="../assets/js/notifications.js"></script>

</body>
</html>
