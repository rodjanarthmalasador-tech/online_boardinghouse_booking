<?php
$dsn = 'mysql:host=127.0.0.1;dbname=boardinghouse_db;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', 'sql', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$count = (int)$pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn();
file_put_contents(__DIR__ . '/room_seed_status.txt', "COUNT={$count}\n");
if ($count === 0) {
    $defaults = [
        ['Sunrise Single', 'Single', 5500, 1, '2nd Floor', 'Available', 'images/single.png', 'Bright and quiet single room with a study desk and shared bath.'],
        ['Garden Double', 'Double', 7800, 2, '1st Floor', 'Available', 'images/double.png', 'Spacious double room for two tenants with natural light and cabinet space.'],
        ['Metro Studio', 'Studio', 9200, 2, '3rd Floor', 'Available', 'images/studio.png', 'Modern studio room with kitchenette space and a private working area.'],
        ['Harbor Dormitory', 'Dormitory', 3200, 4, 'Ground Floor', 'Available', 'images/dormitory.png', 'Budget-friendly shared dormitory with secure lockers and common lounge.'],
        ['Skyline Deluxe', 'Single', 6800, 1, '4th Floor', 'Occupied', 'images/single.png', 'Premium single room with balcony access and a roomy wardrobe.'],
        ['Maple Corner', 'Double', 8100, 2, '2nd Floor', 'Maintenance', 'images/double.png', 'Corner unit currently under maintenance with fresh repainting scheduled.'],
    ];
    $stmt = $pdo->prepare("INSERT INTO `rooms` (`name`, `type`, `price`, `capacity`, `floor`, `status`, `image`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($defaults as $row) {
        $stmt->execute($row);
    }
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn();
    file_put_contents(__DIR__ . '/room_seed_status.txt', "COUNT={$count}\nINSERTED=DEFAULTS\n");
}

$rows = $pdo->query("SELECT `id`, `name`, `type`, `price`, `capacity`, `status` FROM `rooms` ORDER BY `id` LIMIT 3")->fetchAll();
foreach ($rows as $row) {
    file_put_contents(__DIR__ . '/room_seed_status.txt', "COUNT={$count}\n" . $row['id'] . '|' . $row['name'] . '|' . $row['type'] . '|' . $row['price'] . '|' . $row['capacity'] . '|' . $row['status'] . "\n", FILE_APPEND);
}
