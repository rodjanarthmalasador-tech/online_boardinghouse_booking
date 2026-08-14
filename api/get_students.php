<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $pdo = getDB();
    $search = trim($_GET['search'] ?? '');

    $sql = 'SELECT * FROM `students` WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (`name` LIKE ? OR `course` LIKE ?)';
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
    }

    $sql .= ' ORDER BY `id` ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode($stmt->fetchAll());
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
