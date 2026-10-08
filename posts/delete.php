<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken()) {
    http_response_code(403);
    exit('Invalid delete request.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid post.');
}

try {
    $statement = $pdo->prepare('DELETE FROM posts WHERE id = :id');
    $statement->execute(['id' => $id]);
    $deleted = $statement->rowCount() > 0;
    setFlash($deleted ? 'Post deleted.' : 'Post was not found.', $deleted ? 'success' : 'error');
} catch (PDOException $exception) {
    error_log('Post deletion failed: ' . $exception->getMessage());
    setFlash('We could not delete the post. Please try again.', 'error');
}
header('Location: /');
exit;
