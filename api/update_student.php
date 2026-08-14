<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$course = trim($data['course'] ?? '');

if ($id <= 0 || $name === '' || $course === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Valid student ID, name, and course are required.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE `students` SET `name` = ?, `course` = ? WHERE `id` = ?');
    $stmt->execute([$name, $course, $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Student updated successfully.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
