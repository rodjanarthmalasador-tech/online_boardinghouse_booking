<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
$course = trim($data['course'] ?? '');

if ($name === '' || $course === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name and course are required.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('INSERT INTO `students` (`name`, `course`) VALUES (?, ?)');
    $stmt->execute([$name, $course]);

    echo json_encode([
        'success' => true,
        'message' => 'Student added successfully.',
        'id' => $pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
