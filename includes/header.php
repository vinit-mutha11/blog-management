<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Simple Blog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($pageTitle) ?> | Simple Blog</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="site-header">
    <nav class="navbar container" aria-label="Main navigation">
        <a class="brand" href="/">Simple Blog</a>
        <div class="nav-links">
            <a href="/">Home</a>
            <?php if (isAdmin()): ?>
                <a href="/posts/create.php">New post</a>
                <a href="/admin/users.php">Manage users</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
                <span class="welcome">Hello, <?= escape((string) $_SESSION['username']) ?></span>
                <form class="inline-form" action="/auth/logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                    <button class="link-button" type="submit">Log out</button>
                </form>
            <?php else: ?>
                <a href="/auth/login.php">Log in</a>
                <a href="/auth/register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<main class="container main-content">
    <?php showFlash(); ?>
