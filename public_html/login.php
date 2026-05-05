<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_guest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $captchaToken = (string)($_POST['g-recaptcha-response'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please fill in email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!verify_recaptcha($captchaToken)) {
        $error = 'reCAPTCHA validation failed. Please try again.';
    } else {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: dashboard.php');
            exit;
        }
    }
}

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> NMillion - Finance, Stocks, Markets, Crypto, Taxes and Banking </title>
  <meta name="description" content="Finance, Stocks, Markets, Crypto, Taxes, Person Finance, Personal Banking, Mortage Auto Loan Calculators, Federal and State taxes.">
  <meta name="keywords" content="Finance, Stocks, Markets, Crypto, Taxes, Person Finance, Personal Banking, Mortage Auto Loan Calculators, Federal and State taxes.">
  <link rel="stylesheet" href="style.css">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
  <div class="auth-page">
    <header class="landing-topbar">
      <a class="landing-brand-link" href="index.php">nmillion</a>
    </header>

    <main class="page">
    <section class="auth-layout auth-layout-single">
      <div class="card">
        <h2>Login</h2>

        <?php if ($flash): ?>
          <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input class="field" type="email" name="email" placeholder="Email address" required>
          <input class="field" type="password" name="password" placeholder="Password" required>
          <div class="g-recaptcha" data-sitekey="<?= h(RECAPTCHA_SITE_KEY) ?>"></div>
          <button class="btn btn-primary" type="submit">Log In</button>
        </form>

        <a class="link" href="forgot_password.php">Forgotten password?</a>
        <hr class="divider">
        <p class="center">
          <a class="btn btn-success" href="register.php" style="display:inline-block;max-width:230px;text-decoration:none;line-height:1.2;">
            Create New Account
          </a>
        </p>
      </div>
    </section>
    </main>
  </div>
</body>
</html>
