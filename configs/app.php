<?php
/**
 * Application Bootstrap
 *
 * Loads all core infrastructure in the correct order.
 * Include this file at the top of every controller and view.
 *
 * Usage:
 *   require_once 'app.php';
 *   $user = require_login();
 *   $db = Database::getInstance();
 */

// ─── Environment ─────────────────────────────────────────────
require_once __DIR__ . '/env.php';

// ─── Helpers ─────────────────────────────────────────────────
require_once __DIR__ . '/helpers.php';

// ─── Database ────────────────────────────────────────────────
require_once __DIR__ . '/Database.php';

// ─── Session & Auth ─────────────────────────────────────────
require_once __DIR__ . '/middleware.php';

// ─── CSRF ────────────────────────────────────────────────────
require_once __DIR__ . '/csrf.php';

// ─── Rate Limiter ────────────────────────────────────────────
require_once __DIR__ . '/rate_limiter.php';

// ─── Validator ───────────────────────────────────────────────
require_once __DIR__ . '/Validator.php';

// ─── Response ────────────────────────────────────────────────
require_once __DIR__ . '/Response.php';

// ─── Error Handler ───────────────────────────────────────────
require_once __DIR__ . '/ErrorHandler.php';

// ─── Start secure session ────────────────────────────────────
start_secure_session();

// ─── Set security headers ────────────────────────────────────
set_security_headers();

/**
 * Send HTTP security headers on every response.
 *
 * @return void
 */
function set_security_headers(): void
{
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME-type sniffing
    header('X-Content-Type-Options: nosniff');

    // Enable XSS protection in browsers
    header('X-XSS-Protection: 1; mode=block');

    // Control referrer information
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Prevent search engines from indexing (for admin panels)
    if (APP_ENV !== 'development') {
        header('X-Robots-Tag: noindex, nofollow');
    }

    // Content Security Policy
    $csp = "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "img-src 'self' data: https:; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "connect-src 'self'; "
        . "frame-ancestors 'self'; "
        . "base-uri 'self'; "
        . "form-action 'self'";

    header("Content-Security-Policy: {$csp}");

    // Permissions Policy (formerly Feature Policy)
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // HSTS (only over HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

