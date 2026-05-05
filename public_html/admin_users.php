<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$pdo = get_pdo();
$stmt = $pdo->query('SELECT id, full_name, email, created_at FROM users ORDER BY id DESC');
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Users - nMillion</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="auth-page">
    <header class="landing-topbar">
      <a class="landing-brand-link" href="index.php">nmillion</a>
      <div class="portfolio-top-actions">
        <a class="login-icon-link" href="dashboard.php">Dashboard</a>
        <a class="login-icon-link" href="portfolio.php">My portfolio</a>
        <a class="login-icon-link" href="admin_rates.php">Rates</a>
      </div>
    </header>

    <main class="page portfolio-page">
      <section class="portfolio-shell">
        <div class="portfolio-header">
          <h2>Registered Users</h2>
          <p class="portfolio-subtitle">Open a user portfolio (read-only).</p>
        </div>

        <div class="admin-users">
          <table class="admin-users-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Created</th>
                <th>Portfolio</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= h((string)$u['id']) ?></td>
                  <td><?= h((string)$u['full_name']) ?></td>
                  <td><?= h((string)$u['email']) ?></td>
                  <td><?= h((string)$u['created_at']) ?></td>
                  <td><a class="link" href="portfolio.php?user_id=<?= h((string)$u['id']) ?>">View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
