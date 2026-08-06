<?php
// config.php - Database connection & authentication helpers

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'sql');
define('DB_NAME', 'boardinghouse_db');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $attempts = [
            ['host' => DB_HOST, 'user' => DB_USER, 'pass' => DB_PASS],
            ['host' => 'localhost', 'user' => DB_USER, 'pass' => DB_PASS],
            ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
            ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
        ];

        foreach ($attempts as $attempt) {
            try {
                $dsn = 'mysql:host=' . $attempt['host'] . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, $attempt['user'], $attempt['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo->exec("USE `" . DB_NAME . "`;");

                $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(100) UNIQUE NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `role` ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',
                    `phone` VARCHAR(30) NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                $pdo->exec("CREATE TABLE IF NOT EXISTS `rooms` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `type` VARCHAR(50) NOT NULL,
                    `price` DECIMAL(10,2) NOT NULL,
                    `capacity` INT NOT NULL DEFAULT 1,
                    `floor` VARCHAR(20) DEFAULT '1st Floor',
                    `status` ENUM('Available', 'Occupied', 'Maintenance') DEFAULT 'Available',
                    `image` VARCHAR(255) NULL,
                    `description` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                $pdo->exec("CREATE TABLE IF NOT EXISTS `bookings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `room_id` INT NOT NULL,
                    `user_id` INT NULL,
                    `tenant_name` VARCHAR(100) NOT NULL,
                    `tenant_phone` VARCHAR(30) NOT NULL,
                    `tenant_email` VARCHAR(100) NOT NULL,
                    `check_in_date` DATE NOT NULL,
                    `notes` TEXT,
                    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_bookings_room` (`room_id`),
                    INDEX `idx_bookings_user` (`user_id`),
                    INDEX `idx_bookings_status` (`status`),
                    CONSTRAINT `fk_bookings_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                $pdo->exec("CREATE TABLE IF NOT EXISTS `payments` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `booking_id` INT NOT NULL,
                    `payment_method` VARCHAR(50) NOT NULL,
                    `reference_number` VARCHAR(100) NULL,
                    `proof_image` VARCHAR(255) NULL,
                    `amount` DECIMAL(10,2) NOT NULL,
                    `status` ENUM('Pending Verification', 'Verified', 'Rejected') DEFAULT 'Pending Verification',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_payments_booking` (`booking_id`),
                    INDEX `idx_payments_status` (`status`),
                    CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                return $pdo;
            } catch (PDOException $e) {
                continue;
            }
        }

        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `" . DB_NAME . "`;");
            return $pdo;
        } catch (PDOException $e2) {
            die("<div style='font-family:sans-serif; padding:30px; color:#ef4444;'><h2>Database Connection Error</h2><p>" . htmlspecialchars($e2->getMessage()) . "</p><p>Please verify that MySQL is running and that the database user/password in this file is correct.</p></div>");
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
