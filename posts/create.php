<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$errors = [];
$title = '';
$content = '';
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
            $statement = $pdo->prepare('INSERT INTO posts (title, content, user_id) VALUES (:title, :content, :user_id)');
            $statement->execute(['title' => $title, 'content' => $content, 'user_id' => $_SESSION['user_id']]);
            setFlash('Post created.');
            header('Location: /posts/view.php?id=' . $pdo->lastInsertId());
            exit;
        } catch (PDOException $exception) {
            error_log('Post creation failed: ' . $exception->getMessage());
            $errors[] = 'We could not create the post. Please try again.';
        }
    }
}

$pageTitle = 'New post';
require __DIR__ . '/../includes/header.php';
?>
<section class="form-card">
    <h1>Create a post</h1>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= escape($error) ?></div><?php endforeach; ?>
    <form method="post" action="/posts/create.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <label for="title">Title</label>
        <input id="title" name="title" maxlength="200" required value="<?= escape($title) ?>">
        <label for="content">Content</label>
        <textarea id="content" name="content" rows="12" required><?= escape($content) ?></textarea>
        <button class="button" type="submit">Publish post</button>
        <a class="button button-secondary" href="/">Cancel</a>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
