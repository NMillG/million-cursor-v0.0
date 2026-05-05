<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_guest();

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $captchaToken = (string)($_POST['g-recaptcha-response'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!verify_recaptcha($captchaToken)) {
        $error = 'reCAPTCHA validation failed. Please try again.';
    } else {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $insert = $pdo->prepare(
                'INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)'
            );
            $insert->execute([
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);

            $resetLink = APP_BASE_URL . '/reset_password.php?token=' . urlencode($token);
            $message = 'Password reset link generated (demo): <a href="' . h($resetLink) . '">' . h($resetLink) . '</a>';
        } else {
            $message = 'If an account exists for that email, a reset link has been generated.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot password - nMillion</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
  <main class="page">
    <section class="auth-layout">
      <div class="brand">
        <h1>nmillion</h1>
        <p>Reset your password to get back into your account.</p>
      </div>

      <div class="card">
        <h2>Forgot Password</h2>

        <?php if ($error !== ''): ?>
          <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
          <div class="flash flash-success"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
          <input class="field" type="email" name="email" placeholder="Email address" required>
          <div class="g-recaptcha" data-sitekey="<?= h(RECAPTCHA_SITE_KEY) ?>"></div>
          <button class="btn btn-primary" type="submit">Send reset link</button>
        </form>

        <a class="link" href="login.php">Back to login</a>
      </div>
    </section>
  </main>
</body>
</html>
