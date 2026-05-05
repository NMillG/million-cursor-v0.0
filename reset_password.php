<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_guest();

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$message = '';

if ($token === '') {
    $error = 'Invalid reset token.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $pdo = get_pdo();
        $stmt = $pdo->prepare(
            'SELECT id, email, expires_at, used_at FROM password_resets WHERE token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $error = 'Invalid reset token.';
        } elseif ($reset['used_at'] !== null) {
            $error = 'This reset link has already been used.';
        } elseif (strtotime($reset['expires_at']) < time()) {
            $error = 'This reset link has expired.';
        } else {
            $updateUser = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE email = :email');
            $updateUser->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $reset['email'],
            ]);

            $markUsed = $pdo->prepare('UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = :id');
            $markUsed->execute(['id' => $reset['id']]);

            flash_set('success', 'Password reset successful. You can now log in.');
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset password - nMillion</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <main class="page">
    <section class="auth-layout">
      <div class="brand">
        <h1>nmillion</h1>
        <p>Choose a new secure password for your account.</p>
      </div>

      <div class="card">
        <h2>Reset Password</h2>

        <?php if ($error !== ''): ?>
          <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
          <div class="flash flash-success"><?= h($message) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="token" value="<?= h($token) ?>">
          <input class="field" type="password" name="password" placeholder="New password" required>
          <input class="field" type="password" name="confirm_password" placeholder="Confirm new password" required>
          <button class="btn btn-primary" type="submit">Update password</button>
        </form>

        <a class="link" href="login.php">Back to login</a>
      </div>
    </section>
  </main>
</body>
</html>
