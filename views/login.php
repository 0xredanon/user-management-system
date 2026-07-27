<?php
require_once __DIR__ . '/../configs/app.php';

// If already logged in, redirect to dashboard
$user = current_user();
if ($user) {
    redirect('dashboard.php');
}

// Get flash message
$flash = get_flash();

$page_title = 'ورود';
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
    <div class="glow" aria-hidden="true"></div>

    <form class="login-card" action="../configs/login-db.php" method="POST" novalidate>
        <?= csrf_field() ?>

        <div class="header">
            <h1 class="title">خوش برگشتی</h1>
            <p class="subtitle">برای ادامه وارد حساب‌ت شو</p>
        </div>

        <div class="field">
            <label for="username-or-email">نام کاربری یا ایمیل</label>
            <input
                type="text"
                id="username-or-email"
                name="username-or-email"
                placeholder="you@example.com"
                autocomplete="username"
                required
                minlength="1"
                maxlength="255"
            >
        </div>

        <div class="field">
            <label for="password">رمز عبور</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
                minlength="1"
            >
        </div>

        <button type="submit" id="login-btn" name="login-btn" class="submit-btn">ورود</button>

        <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted); font-size: 0.95rem;">
            حساب کاربری نداری؟
            <a href="register.php" style="color: var(--amber); text-decoration: none; font-weight: 500;">
                ثبت‌نام کن
            </a>
        </p>
    </form>

    <script>
        // Pass flash message to the notification system
        window._flashMessage = <?= json_encode($flash ?: [], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
