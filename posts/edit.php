<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Post not found.');
}

$find = $pdo->prepare('SELECT id, title, content FROM posts WHERE id = :id');
$find->execute(['id' => $id]);
$post = $find->fetch();
if (!$post) {
    http_response_code(404);
    exit('Post not found.');
}

$errors = [];
$title = $post['title'];
$content = $post['content'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));

    if (!verifyCsrfToken()) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    if ($title === '' || strlen($title) > 200) {
        $errors[] = 'Enter a title (up to 200 characters).';
    }
    if ($content === '') {
        $errors[] = 'Post content is required.';
    }
    if ($errors === []) {
        try {
            $update = $pdo->prepare('UPDATE posts SET title = :title, content = :content WHERE id = :id');
            $update->execute(['title' => $title, 'content' => $content, 'id' => $id]);
            setFlash('Post updated.');
            header('Location: /posts/view.php?id=' . $id);
            exit;
        } catch (PDOException $exception) {
            error_log('Post update failed: ' . $exception->getMessage());
            $errors[] = 'We could not update the post. Please try again.';
        }
    }
}

$pageTitle = 'Edit post';
require __DIR__ . '/../includes/header.php';
?>
<section class="form-card">
    <h1>Edit post</h1>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= escape($error) ?></div><?php endforeach; ?>
    <form method="post" action="/posts/edit.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <label for="title">Title</label>
        <input id="title" name="title" maxlength="200" required value="<?= escape($title) ?>">
        <label for="content">Content</label>
        <textarea id="content" name="content" rows="12" required data-character-count><?= escape($content) ?></textarea>
        <p class="field-hint" data-character-count-label aria-live="polite"></p>
        <button class="button" type="submit">Save changes</button>
        <a class="button button-secondary" href="/posts/view.php?id=<?= (int) $id ?>">Cancel</a>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
