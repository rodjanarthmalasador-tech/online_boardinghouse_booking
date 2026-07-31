<?php
$candidates = [
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'root'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'password'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '123456'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'laragon'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password'],
];

foreach ($candidates as $c) {
    try {
        $dsn = 'mysql:host=' . $c['host'] . ';dbname=boardinghouse_db;charset=utf8mb4';
        $pdo = new PDO($dsn, $c['user'], $c['pass']);
        echo $c['host'] . '|' . $c['user'] . '|' . $c['pass'] . ' => OK' . PHP_EOL;
        break;
    } catch (Exception $e) {
        echo $c['host'] . '|' . $c['user'] . '|' . $c['pass'] . ' => FAIL: ' . $e->getMessage() . PHP_EOL;
    }
}
