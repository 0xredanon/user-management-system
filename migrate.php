<?php
/**
 * Database Migration Script
 *
 * Run this script to update the database schema to the latest version.
 * This adds new columns to the existing users table and creates
 * the additional tables (sessions, activity_log, rate_limits).
 *
 * Usage: php -c php.ini migrate.php
 */

require_once __DIR__ . '/configs/env.php';

echo "============================================\n";
echo "  User Management System — Database Migration\n";
echo "============================================\n\n";

try {
    // Connect to MySQL (without selecting a database first)
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Create database if it doesn't exist
    echo "[1/6] Creating database...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "  ✓ Database '" . DB_NAME . "' ready.\n\n";

    // Add new columns to users table
    echo "[2/6] Updating users table...\n";

    // Check if columns exist before adding
    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER role");
        echo "  ✓ Added column: status\n";
    }

    if (!in_array('avatar', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL AFTER status");
        echo "  ✓ Added column: avatar\n";
    }

    if (!in_array('updated_at', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "  ✓ Added column: updated_at\n";
    }

    if (!in_array('deleted_at', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
        echo "  ✓ Added column: deleted_at\n";
    }

    // Add indexes (check if they already exist to avoid duplicates)
    echo "  ✓ Ensuring indexes...\n";
    $existingIndexes = [];
    $stmt = $pdo->query("SHOW INDEX FROM users");
    while ($row = $stmt->fetch()) {
        $existingIndexes[] = $row['Key_name'];
    }

    $indexes = [
        'idx_role'       => "ALTER TABLE users ADD INDEX idx_role (role)",
        'idx_status'     => "ALTER TABLE users ADD INDEX idx_status (status)",
        'idx_created_at' => "ALTER TABLE users ADD INDEX idx_created_at (created_at)",
        'uk_username'    => "ALTER TABLE users ADD UNIQUE KEY uk_username (username)",
        'uk_email'       => "ALTER TABLE users ADD UNIQUE KEY uk_email (email)",
    ];

    foreach ($indexes as $name => $sql) {
        if (!in_array($name, $existingIndexes)) {
            $pdo->exec($sql);
            echo "  ✓ Added index: {$name}\n";
        }
    }
    echo "  ✓ Indexes ready.\n\n";

    // Create sessions table
    echo "[3/6] Creating sessions table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ sessions table ready.\n\n";

    // Create activity_log table
    echo "[4/6] Creating activity_log table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_log (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_action (action),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ activity_log table ready.\n\n";

    // Create rate_limits table
    echo "[5/6] Creating rate_limits table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(255) NOT NULL,
            count INT UNSIGNED NOT NULL DEFAULT 1,
            expires_at TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_key (`key`),
            KEY idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ rate_limits table ready.\n\n";

    // Create default super_admin if no admin exists
    echo "[6/6] Checking for admin users...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin') AND deleted_at IS NULL");
    $adminCount = $stmt->fetch()['count'];

    if ($adminCount === 0) {
        $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO users (username, email, password, role, status)
            VALUES (?, ?, ?, ?, ?)
        ")->execute(['admin', 'admin@example.com', $hashedPassword, 'super_admin', 'active']);

        echo "  ✓ Created default super_admin user:\n";
        echo "    Username: admin\n";
        echo "    Email: admin@example.com\n";
        echo "    Password: Admin@123\n";
        echo "    (Please change this password immediately!)\n";
    } else {
        echo "  ✓ Admin users already exist ($adminCount found).\n";
    }

    echo "\n============================================\n";
    echo "  Migration completed successfully!\n";
    echo "============================================\n";

} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
