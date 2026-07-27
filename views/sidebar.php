<?php
/**
 * Shared Sidebar
 * Uses the authenticated user's role to show/hide navigation items.
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

    <div class="logo">
        <h2>مدیریت کاربران</h2>
    </div>

    <nav>

        <a href="dashboard.php"
           class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            داشبورد
        </a>

        <?php if (has_any_role([ROLE_ADMIN, ROLE_SUPER_ADMIN])): ?>

            <a href="users.php"
               class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">
                مدیریت کاربران
            </a>

        <?php endif; ?>

        <?php if (has_role(ROLE_SUPER_ADMIN)): ?>

            <a href="admin.php"
               class="<?= $currentPage === 'admin.php' ? 'active' : '' ?>">
                مدیریت ادمین‌ها
            </a>

        <?php endif; ?>

        <a href="profile.php"
           class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            پروفایل
        </a>

    </nav>

    <div class="sidebar-footer">

        <div class="user-info">
            <strong><?= e($user['username'] ?? '') ?></strong>
            <small><?= e(ucfirst($user['role'] ?? '')) ?></small>
        </div>

        <a href="../logout.php" class="logout-btn">
            خروج
        </a>

    </div>

</div>
