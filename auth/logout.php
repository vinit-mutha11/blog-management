<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken()) {
    http_response_code(403);
    exit('Invalid logout request.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}
session_destroy();
header('Location: /');
exit;
