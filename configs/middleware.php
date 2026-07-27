<?php
/**
 * Middleware — Authentication & Authorization helpers.
 *
 * Usage:
 *   require_once 'middleware.php';
 *   require_login();           // Redirects to login if not authenticated
 *   require_admin();           // Redirects to dashboard if not admin
 *   $user = current_user();    // Returns the current user array or null
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/Database.php';

/**
 * Start a secure session if not already started.
 *
 * @return void
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);

        $cookieParams = [
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => '',
            'secure'   => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => SESSION_SAMESITE,
        ];

        session_set_cookie_params($cookieParams);
        session_start();

        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['initiated'])) {
            $_SESSION['initiated'] = true;
            $_SESSION['ip'] = get_client_ip();
            $_SESSION['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        }
    }
}

/**
 * Validate the current session.
 * Checks IP and user agent to prevent session hijacking.
 *
 * @return bool
 */
function validate_session(): bool
{
    if (!isset($_SESSION['ip'], $_SESSION['user_agent'])) {
        return false;
    }

    // Allow IP to change (mobile users) but flag drastic changes
    if ($_SESSION['ip'] !== get_client_ip()) {
        // Log potential session hijacking attempt
        error_log('Session IP mismatch: ' . $_SESSION['ip'] . ' vs ' . get_client_ip());
    }

    if ($_SESSION['user_agent'] !== substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)) {
        return false;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Get the current authenticated user.
 *
 * @return array|null
 */
function current_user(): ?array
{
    start_secure_session();

    if (!validate_session()) {
        session_unset();
        session_destroy();
        return null;
    }

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    try {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, username, email, role, status, avatar, created_at
             FROM users
             WHERE id = ? AND deleted_at IS NULL",
            [$_SESSION['user_id']]
        );

        if (!$user) {
            // User no longer exists — clear session
            unset($_SESSION['user_id']);
            return null;
        }

        return $user;
    } catch (Throwable $e) {
        error_log('Failed to fetch current user: ' . $e->getMessage());
        return null;
    }
}

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 *
 * @return array The current user data
 */
function require_login(): array
{
    $user = current_user();

    if (!$user) {
        redirect_with_flash('../views/login.php', 'error', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
    }

    return $user;
}

/**
 * Require the user to be an admin.
 * Redirects to dashboard if not admin.
 *
 * @return array The current user data
 */
function require_admin(): array
{
    $user = require_login();

    if ($user['role'] !== ROLE_ADMIN && $user['role'] !== ROLE_SUPER_ADMIN) {
        redirect_with_flash('../views/dashboard.php', 'error', 'دسترسی شما به این بخش محدود شده است.');
    }

    return $user;
}

/**
 * Require the user to be a super admin.
 * Redirects to dashboard if not super admin.
 *
 * @return array The current user data
 */
function require_super_admin(): array
{
    $user = require_login();

    if ($user['role'] !== ROLE_SUPER_ADMIN) {
        redirect_with_flash('../views/dashboard.php', 'error', 'دسترسی شما به این بخش محدود شده است.');
    }

    return $user;
}

/**
 * Check if the current user has a specific role.
 *
 * @param string $role
 * @return bool
 */
function has_role(string $role): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === $role;
}

/**
 * Check if the current user has any of the given roles.
 *
 * @param array $roles
 * @return bool
 */
function has_any_role(array $roles): bool
{
    $user = current_user();
    return $user !== null && in_array($user['role'], $roles, true);
}

