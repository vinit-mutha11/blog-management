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
    'SELECT posts.id, posts.title, posts.content, posts.created_at, posts.updated_at, users.username
     FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = :id'
);
$statement->execute(['id' => $id]);
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
