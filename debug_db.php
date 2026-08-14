<?php
require __DIR__ . '/config.php';
$pdo = getDB();
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$roomCount = (int)$pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn();
$rows = $pdo->query("SELECT * FROM `rooms` ORDER BY `id` DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/db_debug.txt', json_encode([
    'tables' => $tables,
    'room_count' => $roomCount,
    'rooms' => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "room_count={$roomCount}\n";
