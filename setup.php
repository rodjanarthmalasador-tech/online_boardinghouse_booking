<?php
// setup.php - Automatic Database Table Creation & Schema Migration
require_once __DIR__ . '/config.php';

$pdo = getDB();

try {
    // 1. Create uploads directory if not exists
    $uploadDir = __DIR__ . '/uploads/payments';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $imgDir = __DIR__ . '/images';
    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0777, true);
    }

    // 2. Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',
        `phone` VARCHAR(30) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $defaultUsers = [
        [
            'name' => 'Admin User',
            'email' => 'admin@boardinghouse.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'phone' => '09100000001',
        ],
        [
            'name' => 'Tenant User',
            'email' => 'tenant@boardinghouse.com',
            'password' => password_hash('tenant123', PASSWORD_DEFAULT),
            'role' => 'tenant',
            'phone' => '09100000002',
        ],
    ];

    foreach ($defaultUsers as $user) {
        $check = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = ?");
        $check->execute([$user['email']]);
        if (!$check->fetch()) {
            $insert = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$user['name'], $user['email'], $user['password'], $user['role'], $user['phone']]);
        }
    }

    // 3. Create rooms table
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

    // Seed default rooms if the table is empty or missing standard room entries
    $defaultRooms = [
        ['Cozy Single Room', 'Single', '5500.00', 1, '2nd Floor', 'Available', 'images/single.png', 'A compact single room with air conditioning, free Wi-Fi, and a shared kitchen.'],
        ['Double Comfort Room', 'Double', '9000.00', 2, '3rd Floor', 'Available', 'images/double.png', 'A spacious double room with modern furnishings and a balcony view.'],
        ['Studio Suite', 'Studio', '13000.00', 2, '4th Floor', 'Available', 'images/studio.png', 'A studio room with private bathroom, kitchenette, and study area.'],
        ['Bedspace Dormitory', 'Dormitory', '3500.00', 1, '1st Floor', 'Available', 'images/dormitory.png', 'A budget-friendly bedspace with access to shared amenities and common areas.'],
        ['Family Suite', 'Double', '12000.00', 3, '5th Floor', 'Available', 'images/double.png', 'A family-friendly suite with extra space, seating area, and privacy.'],
        ['Executive Studio', 'Studio', '15000.00', 2, '6th Floor', 'Available', 'images/studio.png', 'A premium studio with a work desk, private bathroom, and modern decor.'],
        ['Private Loft', 'Studio', '17000.00', 2, '7th Floor', 'Available', 'images/studio.png', 'A stylish loft-style studio with a mezzanine sleeping area and city views.'],
        ['Economy Bedspace', 'Dormitory', '3200.00', 1, 'Ground Floor', 'Available', 'images/dormitory.png', 'A low-cost bedspace with essential amenities and shared common areas.'],
    ];

    $insertRoom = $pdo->prepare("INSERT INTO `rooms` (`name`, `type`, `price`, `capacity`, `floor`, `status`, `image`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $checkRoom = $pdo->prepare("SELECT `id` FROM `rooms` WHERE `name` = ? LIMIT 1");
    $updateRoom = $pdo->prepare("UPDATE `rooms` SET `type` = ?, `price` = ?, `capacity` = ?, `floor` = ?, `status` = ?, `image` = ?, `description` = ? WHERE `name` = ?");

    foreach ($defaultRooms as $roomData) {
        $checkRoom->execute([$roomData[0]]);
        if (!$checkRoom->fetch()) {
            $insertRoom->execute($roomData);
        } else {
            $updateRoom->execute([
                $roomData[1],
                $roomData[2],
                $roomData[3],
                $roomData[4],
                $roomData[5],
                $roomData[6],
                $roomData[7],
                $roomData[0],
            ]);
        }
    }

    // Check if `image` column exists in `rooms` table
    $roomCols = array_column($pdo->query("SHOW COLUMNS FROM `rooms`")->fetchAll(), 'Field');
    if (!in_array('image', $roomCols)) {
        $pdo->exec("ALTER TABLE `rooms` ADD `image` VARCHAR(255) NULL AFTER `status`");
    }

    // Update room images
    $pdo->exec("UPDATE `rooms` SET `image` = 'images/single.png' WHERE `image` IS NULL OR `image` = '' OR LOWER(`type`) = 'single'");
    $pdo->exec("UPDATE `rooms` SET `image` = 'images/double.png' WHERE LOWER(`type`) = 'double'");
    $pdo->exec("UPDATE `rooms` SET `image` = 'images/studio.png' WHERE LOWER(`type`) = 'studio'");
    $pdo->exec("UPDATE `rooms` SET `image` = 'images/dormitory.png' WHERE LOWER(`type`) IN ('dormitory', 'bedspace')");

    // 4. Create bookings table
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
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. Create payments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `booking_id` INT NOT NULL,
        `payment_method` VARCHAR(50) NOT NULL,
        `reference_number` VARCHAR(100) NULL,
        `proof_image` VARCHAR(255) NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `status` ENUM('Pending Verification', 'Verified', 'Rejected') DEFAULT 'Pending Verification',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Check and add missing columns to `payments` table
    $payCols = array_column($pdo->query("SHOW COLUMNS FROM `payments`")->fetchAll(), 'Field');
    if (!in_array('payment_method', $payCols)) {
        $pdo->exec("ALTER TABLE `payments` ADD `payment_method` VARCHAR(50) NULL AFTER `booking_id`");
    }
    if (!in_array('proof_image', $payCols)) {
        $pdo->exec("ALTER TABLE `payments` ADD `proof_image` VARCHAR(255) NULL AFTER `reference_number`");
    }

    // Sync method -> payment_method and receipt_photo -> proof_image if columns exist
    if (in_array('method', $payCols)) {
        $pdo->exec("UPDATE `payments` SET `payment_method` = `method` WHERE (`payment_method` IS NULL OR `payment_method` = '') AND `method` IS NOT NULL");
    }
    if (in_array('receipt_photo', $payCols)) {
        $pdo->exec("UPDATE `payments` SET `proof_image` = `receipt_photo` WHERE (`proof_image` IS NULL OR `proof_image` = '') AND `receipt_photo` IS NOT NULL");
    }

    $message = "Database schema updated and `payments` table synchronized successfully!";
} catch (Exception $e) {
    $error = "Database setup failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Boarding House System</title>
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="setup-body">
    <div class="setup-card shadow-lg">
        <div class="setup-icon">🏠</div>
        <h2>Database Migration</h2>
        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <strong>✓ Success!</strong> <?= e($message); ?>
            </div>
            <p>Payments table synchronized.</p>
            <div class="setup-actions">
                <a href="my_bookings.php" class="btn btn-primary">My Bookings</a>
                <a href="bookings.php" class="btn btn-secondary">Admin Bookings</a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>❌ Error!</strong> <?= e($error); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
