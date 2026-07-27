<?php
/**
 * CSRF Protection
 *
 * Usage:
 *   // In a form:
 *   echo csrf_field();
 *
 *   // In a controller:
 *   require_once 'csrf.php';
 *   validate_csrf_token($_POST['csrf_token'] ?? '');
 */

require_once __DIR__ . '/env.php';

/**
 * Generate a CSRF token and store it in the session.
 *
 * @return string
 */
function generate_csrf_token(): string
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_expires'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token_expires'] = time() + CSRF_TOKEN_EXPIRY;
    }

    return $_SESSION['csrf_token'];
}

/**
 * Get the current CSRF token (generates one if none exists).
 *
 * @return string
 */
function get_csrf_token(): string
{
    return generate_csrf_token();
}

/**
 * Validate a CSRF token.
 *
 * @param string $token
 * @return bool
 */
function validate_csrf_token(string $token): bool
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_expires'])) {
        return false;
    }

    // Check expiry
    if (time() > $_SESSION['csrf_token_expires']) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_expires']);
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF token from POST data and exit on failure.
 *
 * @param string $token
 * @return void
 */
function validate_csrf_or_die(string $token): void
{
    if (!validate_csrf_token($token)) {
        if (is_ajax()) {
            header('Content-Type: application/json');
            http_response_code(419);
            echo json_encode([
                'success'   => false,
                'message'   => 'The page has expired. Please try again.',
                'code'      => 'csrf_token_invalid',
                'timestamp' => time(),
            ]);
            exit();
        }
        redirect_with_flash('../views/login.php', 'error', 'The page has expired. Please try again.');
    }
}

/**
 * Generate a hidden CSRF input field for forms.
 *
 * @return string
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(get_csrf_token()) . '">';
}

/**
 * Generate a CSRF meta tag for AJAX requests.
 *
 * @return string
 */
function csrf_meta_tag(): string
{
    return '<meta name="csrf-token" content="' . e(get_csrf_token()) . '">';
}

