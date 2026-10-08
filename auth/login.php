<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verifyCsrfToken()) {
        $error = 'Your form session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $statement = $pdo->prepare('SELECT id, username, email, password, role FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: /');
            exit;
        }
        $error = 'Email or password is incorrect.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/../includes/header.php';
?>
<section class="form-card">
    <h1>Log in</h1>
    <?php if ($error !== ''): ?><div class="alert error"><?= escape($error) ?></div><?php endif; ?>
    <form method="post" action="/auth/login.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= escape($email) ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        <button class="button" type="submit">Log in</button>
    </form>
    <p>Don't have an account? <a href="/auth/register.php">Register</a>.</p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
