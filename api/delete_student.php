<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? $_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid student ID.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('DELETE FROM `students` WHERE `id` = ?');
    $stmt->execute([$id]);

    echo json_encode([
        'success' => true,
        'message' => 'Student deleted successfully.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
