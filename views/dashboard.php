<?php
/**
 * Dashboard
 * Shows real statistics, charts, and widgets.
 */

$page_title = 'داشبورد';

require_once __DIR__ . '/../configs/app.php';
require_once __DIR__ . '/../configs/ErrorHandler.php';
ErrorHandler::register();

// Require authentication
$user = require_login();

// ─── Fetch Statistics ───────────────────────────────────────
try {
    $db = Database::getInstance();

    // Total users
    $totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL")['count'];

    // Active users
    $activeUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = ? AND deleted_at IS NULL", [STATUS_ACTIVE])['count'];

    // Inactive users
    $inactiveUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = ? AND deleted_at IS NULL", [STATUS_INACTIVE])['count'];

    // Admins
    $adminCount = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role IN (?, ?) AND deleted_at IS NULL", [ROLE_ADMIN, ROLE_SUPER_ADMIN])['count'];

    // Users registered this week
    $weekUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND deleted_at IS NULL")['count'];

    // Users registered this month
    $monthUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND deleted_at IS NULL")['count'];

    // Role distribution for chart
    $roleDistribution = $db->fetchAll(
        "SELECT role, COUNT(*) as count FROM users WHERE deleted_at IS NULL GROUP BY role"
    );

    // User growth (last 7 days)
    $growthData = $db->fetchAll(
        "SELECT DATE(created_at) as date, COUNT(*) as count
         FROM users
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND deleted_at IS NULL
         GROUP BY DATE(created_at)
         ORDER BY date ASC"
    );

} catch (Throwable $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
    $totalUsers = $activeUsers = $inactiveUsers = $adminCount = $weekUsers = $monthUsers = 0;
    $roleDistribution = [];
    $growthData = [];
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="../assets/css/dashboard.css">

<div class="main-content dashboard-page">

    <div class="dashboard-header">
        <h1>
            خوش آمدی،
            <?= e($user['username'] ?? '') ?>
            👋
        </h1>
        <p class="subtitle">
            نقش:
            <?= e(ucfirst($user['role'] ?? '')) ?>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-card__icon">
                <span>👥</span>
            </div>
            <div class="stat-card__content">
                <h3>کل کاربران</h3>
                <h2><?= number_format($totalUsers) ?></h2>
            </div>
        </div>

        <div class="stat-card stat-card--success">
            <div class="stat-card__icon">
                <span>✓</span>
            </div>
            <div class="stat-card__content">
                <h3>کاربران فعال</h3>
                <h2><?= number_format($activeUsers) ?></h2>
            </div>
        </div>

        <div class="stat-card stat-card--warning">
            <div class="stat-card__icon">
                <span>!</span>
            </div>
            <div class="stat-card__content">
                <h3>غیرفعال</h3>
                <h2><?= number_format($inactiveUsers) ?></h2>
            </div>
        </div>

        <div class="stat-card stat-card--info">
            <div class="stat-card__icon">
                <span>★</span>
            </div>
            <div class="stat-card__content">
                <h3>ادمین‌ها</h3>
                <h2><?= number_format($adminCount) ?></h2>
            </div>
        </div>

        <div class="stat-card stat-card--amber">
            <div class="stat-card__icon">
                <span>📈</span>
            </div>
            <div class="stat-card__content">
                <h3>این هفته</h3>
                <h2>+<?= number_format($weekUsers) ?></h2>
            </div>
        </div>

        <div class="stat-card stat-card--amber">
            <div class="stat-card__icon">
                <span>📊</span>
            </div>
            <div class="stat-card__content">
                <h3>این ماه</h3>
                <h2>+<?= number_format($monthUsers) ?></h2>
            </div>
        </div>

    </div>

    <!-- Charts (Admin only) -->
    <?php if (has_any_role([ROLE_ADMIN, ROLE_SUPER_ADMIN])): ?>
    <div class="charts-grid">

        <div class="card">
            <h2>رشد کاربران (۷ روز اخیر)</h2>
            <canvas id="growthChart" height="200"></canvas>
        </div>

        <div class="card">
            <h2>توزیع نقش‌ها</h2>
            <canvas id="roleChart" height="200"></canvas>
        </div>

    </div>
    <?php else: ?>
    <div class="card">
        <h2>نمودارها</h2>
        <p class="empty-state">نمایش نمودارها فقط برای ادمین‌ها در دسترس است.</p>
    </div>
    <?php endif; ?>



    <!-- Recent Activity -->
    <div class="card">
        <h2>فعالیت‌های اخیر</h2>
        <div class="activity-list">
            <?php
            try {
                $recentActivity = $db->fetchAll(
                    "SELECT action, description, created_at
                     FROM activity_log
                     ORDER BY created_at DESC
                     LIMIT 10"
                );

                if (empty($recentActivity)):
            ?>
                    <p class="empty-state">هیچ فعالیتی ثبت نشده است.</p>
                <?php else: ?>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <span class="activity-action"><?= e($activity['action']) ?></span>
                            <span class="activity-desc"><?= e($activity['description']) ?></span>
                            <span class="activity-time"><?= e(format_date($activity['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif;
            } catch (Throwable $e) {
                echo '<p class="empty-state">خطا در بارگذاری فعالیت‌ها.</p>';
            }
            ?>
        </div>
    </div>

</div>

</div>

<script src="../assets/js/notifications.js"></script>
<script>
    // ─── Growth Chart ────────────────────────────────────────
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($growthData, 'date')) ?>,
            datasets: [{
                label: 'کاربران جدید',
                data: <?= json_encode(array_column($growthData, 'count')) ?>,
                borderColor: '#e8a659',
                backgroundColor: 'rgba(232, 166, 89, 0.15)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#e8a659',
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#a89a86' },
                    grid: { color: 'rgba(58, 51, 44, 0.3)' },
                },
                x: {
                    ticks: { color: '#a89a86' },
                    grid: { display: false },
                },
            },
        }
    });

    // ─── Role Distribution Chart ─────────────────────────────
    const roleCtx = document.getElementById('roleChart').getContext('2d');
    new Chart(roleCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map(function($r) {
                return $r['role'] === 'super_admin' ? 'سوپر ادمین' : ($r['role'] === 'admin' ? 'ادمین' : 'کاربر');
            }, $roleDistribution)) ?>,
            datasets: [{
                data: <?= json_encode(array_column($roleDistribution, 'count')) ?>,
                backgroundColor: ['#e8a659', '#3bb273', '#4facfd'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#a89a86', padding: 20 },
                },
            },
        }
    });
</script>

</body>
</html>
