<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$search = trim((string) ($_GET['search'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'newest');
if (!in_array($sort, ['newest', 'popular'], true)) {
    $sort = 'newest';
}
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $page !== false && $page !== null ? max(1, $page) : 1;
$perPage = 5;
$currentUserId = isLoggedIn() ? (int) $_SESSION['user_id'] : 0;
$orderBy = $sort === 'popular' ? 'like_count DESC, posts.created_at DESC' : 'posts.created_at DESC';

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
        "SELECT posts.id, posts.title, posts.content, posts.created_at, users.username,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = posts.id) AS like_count,
                EXISTS(SELECT 1 FROM post_likes WHERE post_id = posts.id AND user_id = :user_id) AS liked_by_user
         FROM posts JOIN users ON posts.user_id = users.id
         ORDER BY {$orderBy} LIMIT :limit OFFSET :offset"
    );
} else {
    $postStatement = $pdo->prepare(
        "SELECT posts.id, posts.title, posts.content, posts.created_at, users.username,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = posts.id) AS like_count,
                EXISTS(SELECT 1 FROM post_likes WHERE post_id = posts.id AND user_id = :user_id) AS liked_by_user
         FROM posts JOIN users ON posts.user_id = users.id
         WHERE posts.title LIKE :title_search OR posts.content LIKE :content_search
         ORDER BY {$orderBy} LIMIT :limit OFFSET :offset"
    );
    $postStatement->bindValue(':title_search', '%' . $search . '%', PDO::PARAM_STR);
    $postStatement->bindValue(':content_search', '%' . $search . '%', PDO::PARAM_STR);
}
$postStatement->bindValue(':user_id', $currentUserId, PDO::PARAM_INT);
$postStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$postStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
$postStatement->execute();
$posts = $postStatement->fetchAll();

$pageTitle = 'Latest posts';
require __DIR__ . '/includes/header.php';
?>
<section class="intro">
    <p class="eyebrow">THE COMMUNITY BLOG</p>
    <h1>Latest posts</h1>
    <p>Fresh ideas, thoughtful stories, and new perspectives from our community.</p>
    <?php if (isAdmin()): ?><a class="button intro-action" href="/posts/create.php">Write a post <span aria-hidden="true">→</span></a><?php endif; ?>
</section>

<form class="search-form" action="/" method="get" role="search">
    <label class="visually-hidden" for="search">Search posts</label>
    <input id="search" name="search" type="search" value="<?= escape($search) ?>" placeholder="Search titles and content">
    <label class="visually-hidden" for="sort">Sort posts</label>
    <select id="sort" name="sort">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most liked</option>
    </select>
    <button class="button" type="submit">Discover</button>
    <?php if ($search !== '' || $sort !== 'newest'): ?><a class="button button-secondary" href="/">Clear</a><?php endif; ?>
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
                <div class="post-card-footer">
                    <a class="read-more" href="/posts/view.php?id=<?= (int) $post['id'] ?>">Read story <span aria-hidden="true">→</span></a>
                    <?php if (isLoggedIn()): ?>
                        <form class="like-form" action="/posts/like.php" method="post" data-like-form>
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                            <input type="hidden" name="return_to" value="home">
                            <input type="hidden" name="search" value="<?= escape($search) ?>">
                            <input type="hidden" name="page" value="<?= $page ?>">
                            <input type="hidden" name="sort" value="<?= escape($sort) ?>">
                            <button class="like-button" type="submit" aria-pressed="<?= $post['liked_by_user'] ? 'true' : 'false' ?>">
                                <span aria-hidden="true">♥</span> <span><?= $post['liked_by_user'] ? 'Liked' : 'Like' ?></span>
                                <span class="like-count" data-like-count><?= (int) $post['like_count'] ?></span>
                            </button>
                            <span class="like-error" data-like-error role="status"></span>
                        </form>
                    <?php else: ?>
                        <span class="like-count-display"><span aria-hidden="true">♥</span> <?= (int) $post['like_count'] ?></span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <?php
        $query = '&' . http_build_query(['search' => $search, 'sort' => $sort]);
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
