<?php
/**
 * Helper Functions
 * Centralized utility functions used across the application.
 */

require_once __DIR__ . '/env.php';

/**
 * Generate a unique request ID for tracing.
 *
 * @return string
 */
function request_id(): string
{
    // Use uuid extension if available (with proper constant check)
    if (function_exists('uuid_create') && defined('UUID_TYPE_RANDOM')) {
        return uuid_create(UUID_TYPE_RANDOM);
    }
    // Fallback: generate a random hex string
    return bin2hex(random_bytes(16));
}

/**
 * Get the client IP address.
 *
 * @return string
 */
function get_client_ip(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Redirect to a URL.
 *
 * @param string $url
 * @param int $code
 * @return void
 */
function redirect(string $url, int $code = 302): void
{
    if (!headers_sent()) {
        header("Location: {$url}", true, $code);
    }
    exit();
}

/**
 * Redirect with a flash message.
 *
 * @param string $url
 * @param string $type  'success' | 'error' | 'warning' | 'info'
 * @param string $message
 * @return void
 */
function redirect_with_flash(string $url, string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
    redirect($url);
}

/**
 * Get and clear a flash message.
 *
 * @return array|null
 */
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Escape HTML output.
 *
 * @param string|null $value
 * @return string
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random string.
 *
 * @param int $length
 * @return string
 */
function random_string(int $length = 16): string
{
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $result;
}

/**
 * Format a date in a human-readable way.
 *
 * @param string $date
 * @param string $format
 * @return string
 */
function format_date(string $date, string $format = 'Y/m/d H:i'): string
{
    if (empty($date)) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Check if the current request is an AJAX request.
 *
 * @return bool
 */
function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Log an activity.
 *
 * @param int|null $userId
 * @param string $action
 * @param string $description
 * @return void
 */
function log_activity(?int $userId, string $action, string $description): void
{
    try {
        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO activity_log (user_id, action, description, ip_address)
             VALUES (?, ?, ?, ?)"
        )->execute([
            $userId,
            $action,
            $description,
            get_client_ip(),
        ]);
    } catch (Throwable $e) {
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}

