<?php
/**
 * Legacy Database Configuration
 *
 * This file is kept for backward compatibility.
 * New code should use Database::getInstance() instead.
 *
 * @deprecated Use configs/Database.php instead.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/Database.php';

// Provide backward-compatible $conn variable
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die("Connection failed: " . $e->getMessage());
    }
    die("Connection failed. Please try again later.");
}

