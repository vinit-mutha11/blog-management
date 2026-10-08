<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$errors = [];
$username = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!verifyCsrfToken()) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    if ($username === '' || strlen($username) > 50) {
        $errors[] = 'Enter a username (up to 50 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if ($errors === []) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'An account with that email already exists.';
        } else {
            try {
                $insert = $pdo->prepare(
                    'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)'
                );
                $insert->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                setFlash('Your account is ready. Please log in.');
                header('Location: /auth/login.php');
                exit;
            } catch (PDOException $exception) {
                error_log('Registration failed: ' . $exception->getMessage());
                $errors[] = 'We could not create your account. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/../includes/header.php';
?>
<section class="form-card">
    <h1>Create an account</h1>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= escape($error) ?></div><?php endforeach; ?>
    <form method="post" action="/auth/register.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" maxlength="50" required value="<?= escape($username) ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="255" required value="<?= escape($email) ?>">
        <label for="password">Password (at least 8 characters)</label>
        <input id="password" name="password" type="password" minlength="8" required>
        <label for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>
        <button class="button" type="submit">Register</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
