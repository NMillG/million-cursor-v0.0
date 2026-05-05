<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_guest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $captchaToken = (string)($_POST['g-recaptcha-response'] ?? '');

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!verify_recaptcha($captchaToken)) {
        $error = 'reCAPTCHA validation failed. Please try again.';
    } else {
        $pdo = get_pdo();
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);
        if ($existing->fetch()) {
            $error = 'An account already exists with this email.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)'
            );
            $stmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            flash_set('success', 'Registration successful. Please log in.');
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
          <h2>Create New Account</h2>

          <?php if ($error !== ''): ?>
            <div class="flash flash-error"><?= h($error) ?></div>
          <?php endif; ?>

          <form method="POST">
            <input class="field" type="text" name="full_name" placeholder="Full name" required>
            <input class="field" type="email" name="email" placeholder="Email address" required>
            <input class="field" type="password" name="password" placeholder="Password (min 8 chars)" required>
            <input class="field" type="password" name="confirm_password" placeholder="Confirm password" required>
            <div class="g-recaptcha" data-sitekey="<?= h(RECAPTCHA_SITE_KEY) ?>"></div>
            <button class="btn btn-primary" type="submit">Sign Up</button>
          </form>

          <a class="link" href="login.php">Already have an account? Log in</a>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
