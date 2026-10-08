<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function showFlash(): void
{
    if (!isset($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert ' . escape($flash['type']) . '">' . escape($flash['message']) . '</div>';
}
