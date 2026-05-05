<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - nMillion</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="dash">
    <h1>Welcome, <?= h((string)($_SESSION['user_name'] ?? 'User')) ?>!</h1>
    <p>You are logged in as <strong><?= h((string)($_SESSION['user_email'] ?? '')) ?></strong>.</p>
    <p>This is your simple Facebook-style authentication demo dashboard.</p>
    <p>
      <a href="portfolio.php">Stock portfolio</a>
      <?php if (is_admin()): ?>
        &nbsp;|&nbsp;<a href="admin_users.php">Admin: users</a>
        &nbsp;|&nbsp;<a href="admin_rates.php">Admin: rates</a>
      <?php endif; ?>
    </p>
    <p><a href="logout.php">Logout</a></p>
  </div>
</body>
</html>
