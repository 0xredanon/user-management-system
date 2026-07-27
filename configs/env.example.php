<?php
/**
 * Environment Configuration — TEMPLATE
 *
 * Copy this file to env.php and fill in your actual values:
 *   cp configs/env.example.php configs/env.php
 *
 * ⚠️  NEVER commit the real env.php file — it contains secrets!
 *
 * This file defines all application constants and configuration.
 * It is the single source of truth for environment-specific settings.
 */

// ─── Application ─────────────────────────────────────────────
define('APP_NAME', 'User Management System');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'development'); // 'development' | 'production'
define('APP_DEBUG', APP_ENV === 'development');

// ─── Paths ───────────────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));
define('CONFIGS_PATH', BASE_PATH . '/configs');
define('VIEWS_PATH', BASE_PATH . '/views');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('UPLOADS_PROFILES_PATH', UPLOADS_PATH . '/profiles');
define('LOGS_PATH', BASE_PATH . '/logs');

// ─── Database ────────────────────────────────────────────────
// Replace these with your actual MySQL credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'U_Management_Sys');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// ─── Session ─────────────────────────────────────────────────
define('SESSION_NAME', 'ums_session');
define('SESSION_LIFETIME', 1800); // 30 minutes
define('SESSION_SECURE', isset($_SERVER['HTTPS']));
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

// ─── Rate Limiting ───────────────────────────────────────────
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_LOGIN_MAX', 5);
define('RATE_LIMIT_LOGIN_WINDOW', 300);     // 5 min
define('RATE_LIMIT_REGISTER_MAX', 3);
define('RATE_LIMIT_REGISTER_WINDOW', 3600);  // 1 hour
define('RATE_LIMIT_RESET_MAX', 3);
define('RATE_LIMIT_RESET_WINDOW', 3600);     // 1 hour
define('RATE_LIMIT_API_MAX', 60);
define('RATE_LIMIT_API_WINDOW', 60);         // 1 min
define('RATE_LIMIT_PROFILE_MAX', 10);
define('RATE_LIMIT_PROFILE_WINDOW', 60);     // 1 min

// ─── File Upload ─────────────────────────────────────────────
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ─── Pagination ──────────────────────────────────────────────
define('PAGINATION_PER_PAGE', 15);

// ─── Roles ───────────────────────────────────────────────────
define('ROLE_USER', 'user');
define('ROLE_ADMIN', 'admin');
define('ROLE_SUPER_ADMIN', 'super_admin');

// ─── Status ──────────────────────────────────────────────────
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');

// ─── Security ────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
