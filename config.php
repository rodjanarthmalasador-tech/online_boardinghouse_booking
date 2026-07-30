<?php
// config.php - Database connection & authentication helpers

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'boardinghouse_db');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo->exec("USE `" . DB_NAME . "`;");
            } catch (PDOException $e2) {
                die("<div style='font-family:sans-serif; padding:30px; color:#ef4444;'><h2>Database Connection Error</h2><p>" . htmlspecialchars($e2->getMessage()) . "</p></div>");
            }
        }
    }
    return $pdo;
}

// Session initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Authentication Helpers ───────────────────────────────────────────────────

function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function isAdmin() {
    return isLoggedIn() && (currentUser()['role'] ?? '') === 'admin';
}

function isTenant() {
    return isLoggedIn() && (currentUser()['role'] ?? '') === 'tenant';
}

function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        $_SESSION['flash_error'] = "Access denied! Admin privileges required.";
        header('Location: index.php');
        exit;
    }
}

// Helper: Escape HTML string safely
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: Format price in PHP Pesos
function formatMoney($amount) {
    return '₱' . number_format((float)$amount, 2);
}
