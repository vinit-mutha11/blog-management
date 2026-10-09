<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Post not found.');
}

$statement = $pdo->prepare(
    'SELECT posts.id, posts.title, posts.content, posts.created_at, posts.updated_at, users.username,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = posts.id) AS like_count,
            EXISTS(SELECT 1 FROM post_likes WHERE post_id = posts.id AND user_id = :user_id) AS liked_by_user
     FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = :id'
);
$statement->execute([
    'id' => $id,
    'user_id' => isLoggedIn() ? (int) $_SESSION['user_id'] : 0,
]);
$post = $statement->fetch();
if (!$post) {
    http_response_code(404);
    exit('Post not found.');
}

$pageTitle = $post['title'];
require __DIR__ . '/../includes/header.php';
?>
<article class="card single-post">
    <h1><?= escape($post['title']) ?></h1>
    <p class="post-meta">By <?= escape($post['username']) ?> · <?= escape(date('M j, Y', strtotime($post['created_at']))) ?>
        <?php if ($post['updated_at'] !== $post['created_at']): ?> · Updated <?= escape(date('M j, Y', strtotime($post['updated_at']))) ?><?php endif; ?>
    </p>
    <div class="post-content"><?= nl2br(escape($post['content'])) ?></div>
    <div class="post-engagement">
        <?php if (isLoggedIn()): ?>
            <form class="like-form" action="/posts/like.php" method="post" data-like-form>
                <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                <input type="hidden" name="return_to" value="view">
                <button class="like-button" type="submit" aria-pressed="<?= $post['liked_by_user'] ? 'true' : 'false' ?>">
                    <span aria-hidden="true">♥</span> <span><?= $post['liked_by_user'] ? 'Liked' : 'Like this story' ?></span>
                    <span class="like-count" data-like-count><?= (int) $post['like_count'] ?></span>
                </button>
                <span class="like-error" data-like-error role="status"></span>
            </form>
        <?php else: ?>
            <span class="like-count-display"><span aria-hidden="true">♥</span> <?= (int) $post['like_count'] ?> likes</span>
            <a href="/auth/login.php">Log in</a> to like this story.
        <?php endif; ?>
    </div>
    <?php if (isAdmin()): ?>
        <div class="actions">
            <a class="button" href="/posts/edit.php?id=<?= (int) $post['id'] ?>">Edit post</a>
            <form class="inline-form" action="/posts/delete.php" method="post" onsubmit="return confirm('Delete this post?');">
                <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                <button class="button button-danger" type="submit">Delete post</button>
            </form>
        </div>
    <?php endif; ?>
</article>
<p><a href="/">← Back to posts</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
