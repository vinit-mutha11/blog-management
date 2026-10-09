<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
$fail = static function (int $status, string $message) use ($wantsJson): void {
    http_response_code($status);
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }

    setFlash($message, 'error');
    header('Location: /');
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $fail(405, 'Invalid like request.');
}
if (!isLoggedIn()) {
    $fail(401, 'Log in to like posts.');
}
if (!verifyCsrfToken()) {
    $fail(403, 'Your form session expired. Please try again.');
}

$postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$postId || $postId < 1) {
    $fail(400, 'Invalid post.');
}

$postExists = $pdo->prepare('SELECT 1 FROM posts WHERE id = :post_id');
$postExists->execute(['post_id' => $postId]);
if (!$postExists->fetchColumn()) {
    $fail(404, 'Post not found.');
}

$returnTo = (string) ($_POST['return_to'] ?? '');
$redirectTo = '/';
if ($returnTo === 'view') {
    $redirectTo = '/posts/view.php?id=' . $postId;
} elseif ($returnTo === 'home') {
    $search = trim((string) ($_POST['search'] ?? ''));
    $page = filter_var($_POST['page'] ?? null, FILTER_VALIDATE_INT);
    $sort = (string) ($_POST['sort'] ?? 'newest');
    $query = [];
    if ($search !== '') {
        $query['search'] = $search;
    }
    if ($page !== false && $page !== null && $page > 1) {
        $query['page'] = $page;
    }
    if ($sort === 'popular') {
        $query['sort'] = $sort;
    }
    if ($query !== []) {
        $redirectTo .= '?' . http_build_query($query);
    }
}

try {
    $insert = $pdo->prepare('INSERT IGNORE INTO post_likes (user_id, post_id) VALUES (:user_id, :post_id)');
    $insert->execute(['user_id' => (int) $_SESSION['user_id'], 'post_id' => $postId]);
    $liked = $insert->rowCount() === 1;

    if (!$liked) {
        $delete = $pdo->prepare('DELETE FROM post_likes WHERE user_id = :user_id AND post_id = :post_id');
        $delete->execute(['user_id' => (int) $_SESSION['user_id'], 'post_id' => $postId]);
    }

    $count = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = :post_id');
    $count->execute(['post_id' => $postId]);
    $likeCount = (int) $count->fetchColumn();

    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['liked' => $liked, 'like_count' => $likeCount]);
        exit;
    }

    setFlash($liked ? 'You liked this post.' : 'Your like was removed.');
} catch (PDOException $exception) {
    error_log('Post like update failed: ' . $exception->getMessage());
    $fail(500, 'We could not update your like. Please try again.');
}

header('Location: ' . $redirectTo);
exit;
