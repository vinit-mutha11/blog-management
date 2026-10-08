<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$search = trim((string) ($_GET['search'] ?? ''));
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $page !== false && $page !== null ? max(1, $page) : 1;
$perPage = 5;

if ($search === '') {
    $countStatement = $pdo->query('SELECT COUNT(*) FROM posts');
    $totalPosts = (int) $countStatement->fetchColumn();
} else {
    $countStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM posts WHERE title LIKE :title_search OR content LIKE :content_search'
    );
    $countStatement->execute([
        'title_search' => '%' . $search . '%',
        'content_search' => '%' . $search . '%',
    ]);
    $totalPosts = (int) $countStatement->fetchColumn();
}

$totalPages = max(1, (int) ceil($totalPosts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

if ($search === '') {
    $postStatement = $pdo->prepare(
        'SELECT posts.id, posts.title, posts.content, posts.created_at, users.username
         FROM posts JOIN users ON posts.user_id = users.id
         ORDER BY posts.created_at DESC LIMIT :limit OFFSET :offset'
    );
} else {
    $postStatement = $pdo->prepare(
        'SELECT posts.id, posts.title, posts.content, posts.created_at, users.username
         FROM posts JOIN users ON posts.user_id = users.id
         WHERE posts.title LIKE :title_search OR posts.content LIKE :content_search
         ORDER BY posts.created_at DESC LIMIT :limit OFFSET :offset'
    );
    $postStatement->bindValue(':title_search', '%' . $search . '%', PDO::PARAM_STR);
    $postStatement->bindValue(':content_search', '%' . $search . '%', PDO::PARAM_STR);
}
$postStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$postStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
$postStatement->execute();
$posts = $postStatement->fetchAll();

$pageTitle = 'Latest posts';
require __DIR__ . '/includes/header.php';
?>
<section class="intro">
    <h1>Latest posts</h1>
    <p>Read and discover stories from our community.</p>
</section>

<form class="search-form" action="/" method="get">
    <label class="visually-hidden" for="search">Search posts</label>
    <input id="search" name="search" type="search" value="<?= escape($search) ?>" placeholder="Search titles and content">
    <button class="button" type="submit">Search</button>
    <?php if ($search !== ''): ?><a class="button button-secondary" href="/">Clear</a><?php endif; ?>
</form>

<?php if ($posts === []): ?>
    <div class="empty-state"><?= $search !== '' ? 'No posts matched your search.' : 'There are no posts yet.' ?></div>
<?php else: ?>
    <div class="post-list">
        <?php foreach ($posts as $post): ?>
            <article class="card">
                <h2><a href="/posts/view.php?id=<?= (int) $post['id'] ?>"><?= escape($post['title']) ?></a></h2>
                <p class="post-meta">By <?= escape($post['username']) ?> · <?= escape(date('M j, Y', strtotime($post['created_at']))) ?></p>
                <p><?= nl2br(escape(substr($post['content'], 0, 240))) ?><?= strlen($post['content']) > 240 ? '...' : '' ?></p>
                <a class="read-more" href="/posts/view.php?id=<?= (int) $post['id'] ?>">Read post</a>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <?php
        $query = $search !== '' ? '&search=' . urlencode($search) : '';
        ?>
        <nav class="pagination" aria-label="Post pages">
            <?php if ($page > 1): ?><a href="/?page=<?= $page - 1 . $query ?>">Previous</a><?php endif; ?>
            <?php for ($number = 1; $number <= $totalPages; $number++): ?>
                <?php if ($number === $page): ?>
                    <span class="current" aria-current="page"><?= $number ?></span>
                <?php else: ?>
                    <a href="/?page=<?= $number . $query ?>"><?= $number ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="/?page=<?= $page + 1 . $query ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
