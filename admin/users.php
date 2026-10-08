<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $role = (string) ($_POST['role'] ?? '');
    if (!verifyCsrfToken()) {
        $error = 'Your form session expired. Please try again.';
    } elseif (!$userId || $userId < 1 || !in_array($role, ['user', 'admin'], true)) {
        $error = 'Invalid user or role.';
    } elseif ($userId === (int) $_SESSION['user_id'] && $role !== 'admin') {
        $error = 'You cannot remove your own admin role.';
    } else {
        try {
            $update = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
            $update->execute(['role' => $role, 'id' => $userId]);
            setFlash($update->rowCount() > 0 ? 'User role updated.' : 'The user already has that role.');
            header('Location: /admin/users.php');
            exit;
        } catch (PDOException $exception) {
            error_log('User role update failed: ' . $exception->getMessage());
            $error = 'We could not update the user role. Please try again.';
        }
    }
}

$users = $pdo->query('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Manage users';
require __DIR__ . '/../includes/header.php';
?>
<h1>Manage users</h1>
<?php if ($error !== ''): ?><div class="alert error"><?= escape($error) ?></div><?php endif; ?>
<div class="table-wrap">
    <table>
        <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Update role</th></tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= escape($user['username']) ?></td>
                <td><?= escape($user['email']) ?></td>
                <td><?= escape($user['role']) ?></td>
                <td><?= escape(date('M j, Y', strtotime($user['created_at']))) ?></td>
                <td>
                    <form class="role-form" method="post" action="/admin/users.php">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                        <label class="visually-hidden" for="role-<?= (int) $user['id'] ?>">Role for <?= escape($user['username']) ?></label>
                        <select id="role-<?= (int) $user['id'] ?>" name="role">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <button class="button button-small" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
