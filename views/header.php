<?php
/**
 * Shared Header
 * Includes authentication check, security headers, and notification system.
 */

require_once __DIR__ . '/../configs/app.php';

// Require authentication
$user = require_login();

// Get flash message for notifications
$flash = get_flash();

// Generate CSRF token for AJAX requests
$csrfToken = get_csrf_token();

$page_title = $page_title ?? 'داشبورد';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($page_title) ?> — <?= e(APP_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">

    <!-- CSRF token for AJAX requests -->
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">

    <!-- Chart.js for dashboard charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body>

<div class="glow" aria-hidden="true"></div>

<div class="dashboard-container">

<!-- Pass flash message to the notification system -->
<script>
    window._flashMessage = <?= json_encode($flash ?: [], JSON_UNESCAPED_UNICODE) ?>;
</script>
