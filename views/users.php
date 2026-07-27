<?php
/**
 * Users Management Page
 * Features: search, filter, pagination, soft delete, status toggle
 */

$page_title = 'مدیریت کاربران';

require_once __DIR__ . '/../configs/app.php';
require_once __DIR__ . '/../configs/ErrorHandler.php';
ErrorHandler::register();

// Require admin access
$user = require_admin();

// ─── Search & Filter Parameters ─────────────────────────────
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// ─── Pagination ─────────────────────────────────────────────
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_PER_PAGE;
$offset = ($page - 1) * $perPage;

// ─── Build Query ────────────────────────────────────────────
try {
    $db = Database::getInstance();

    // Base query
    $where = ["deleted_at IS NULL"];
    $params = [];

    if ($search !== '') {
        $where[] = "(username LIKE ? OR email LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    if ($roleFilter !== '' && in_array($roleFilter, [ROLE_USER, ROLE_ADMIN, ROLE_SUPER_ADMIN], true)) {
        $where[] = "role = ?";
        $params[] = $roleFilter;
    }

    if ($statusFilter !== '' && in_array($statusFilter, [STATUS_ACTIVE, STATUS_INACTIVE], true)) {
        $where[] = "status = ?";
        $params[] = $statusFilter;
    }

    $whereClause = implode(' AND ', $where);

    // Get total count for pagination
    $totalUsers = $db->fetch(
        "SELECT COUNT(*) as count FROM users WHERE {$whereClause}",
        $params
    )['count'];

    $totalPages = max(1, (int)ceil($totalUsers / $perPage));

    // Fetch users
    $users = $db->fetchAll(
        "SELECT id, username, email, role, status, created_at
         FROM users
         WHERE {$whereClause}
         ORDER BY id DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$perPage, $offset])
    );

} catch (Throwable $e) {
    error_log('Users page error: ' . $e->getMessage());
    $users = [];
    $totalUsers = 0;
    $totalPages = 1;
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="../assets/css/users.css">

<div class="main-content users-page">

    <div class="users-header">
        <h1>مدیریت کاربران</h1>
        <a href="user_add.php" class="btn btn-primary">
            + افزودن کاربر جدید
        </a>
    </div>

    <!-- Search & Filter -->
    <form class="search-bar" method="GET" action="users.php">
        <input
            type="text"
            name="search"
            placeholder="جستجو بر اساس نام کاربری یا ایمیل..."
            value="<?= e($search) ?>"
        >

        <select name="role" onchange="this.form.submit()">
            <option value="">همه نقش‌ها</option>
            <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>کاربر</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>ادمین</option>
            <option value="super_admin" <?= $roleFilter === 'super_admin' ? 'selected' : '' ?>>سوپر ادمین</option>
        </select>

        <select name="status" onchange="this.form.submit()">
            <option value="">همه وضعیت‌ها</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>فعال</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
        </select>

        <?php if ($search !== '' || $roleFilter !== '' || $statusFilter !== ''): ?>
            <a href="users.php" class="btn btn-danger" style="padding: 8px 14px; font-size: 0.85rem;">
                پاک‌سازی فیلتر
            </a>
        <?php endif; ?>
    </form>

    <!-- Users Table -->
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

                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="users-empty">
                            هیچ کاربری یافت نشد.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $row): ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>

                            <td>
                                <?= e($row['username']) ?>
                            </td>

                            <td>
                                <?= e($row['email']) ?>
                            </td>

                            <td>
                                <span class="role-badge role-<?= e($row['role']) ?>">
                                    <?= e(ucfirst(str_replace('_', ' ', $row['role']))) ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($row['status'] === STATUS_ACTIVE): ?>
                                    <span class="role-badge role-user" style="background: rgba(59,178,115,.16); color: #3bb273;">
                                        فعال
                                    </span>
                                <?php else: ?>
                                    <span class="role-badge" style="background: rgba(214,91,91,.16); color: #d65b5b;">
                                        غیرفعال
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e(format_date($row['created_at'])) ?>
                            </td>

                            <td>
                                <a href="user_edit.php?id=<?= (int)$row['id'] ?>"
                                   class="action-btn btn-edit">
                                    ویرایش
                                </a>

                                <?php if ($row['id'] !== (int)$_SESSION['user_id']): ?>
                                    <form
                                        class="delete-form"
                                        action="../configs/users_control.php"
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirmDelete(this)"
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="action-btn btn-delete">
                                            حذف
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryParams = $_GET;
            unset($queryParams['page']);
            $queryString = http_build_query($queryParams);
            ?>

            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&<?= $queryString ?>" class="btn" style="padding: 8px 14px; font-size: 0.85rem;">
                    قبلی
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&<?= $queryString ?>"
                   class="<?= $i === $page ? 'btn btn-primary' : 'btn' ?>"
                   style="padding: 8px 14px; font-size: 0.85rem;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&<?= $queryString ?>" class="btn" style="padding: 8px 14px; font-size: 0.85rem;">
                    بعدی
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</div>

<script src="../assets/js/notifications.js"></script>
<script>
    function confirmDelete(form) {
        showConfirmation('آیا از حذف این کاربر مطمئن هستید؟', {
            onConfirm: () => form.submit(),
            confirmText: 'حذف',
            cancelText: 'لغو',
        });
        return false;
    }
</script>

</body>
</html>
