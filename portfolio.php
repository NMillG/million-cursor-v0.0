<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_auth();

$selfId = (int)$_SESSION['user_id'];
$viewUserId = $selfId;
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $requested = (int)$_GET['user_id'];
    if ($requested > 0 && $requested !== $selfId) {
        if (!is_admin()) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        $viewUserId = $requested;
    }
}

$readOnly = $viewUserId !== $selfId;
csrf_ensure();
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock Portfolio - nMillion</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://unpkg.com/tabulator-tables@6.3.0/dist/css/tabulator.min.css" rel="stylesheet">
  <script src="https://unpkg.com/tabulator-tables@6.3.0/dist/js/tabulator.min.js"></script>
</head>
<body>
  <div class="auth-page">
    <header class="landing-topbar">
      <a class="landing-brand-link" href="index.php">nmillion</a>
      <div class="portfolio-top-actions">
        <a class="login-icon-link" href="dashboard.php">Dashboard</a>
        <?php if (is_admin()): ?>
          <a class="login-icon-link" href="admin_users.php">Users</a>
        <?php endif; ?>
      </div>
    </header>

    <main class="page portfolio-page">
      <section class="portfolio-shell">
        <div class="portfolio-header">
          <h2>My Portfolio</h2>
          <p class="portfolio-subtitle">
            <?php if ($readOnly): ?>
              Viewing user ID <strong><?= h((string)$viewUserId) ?></strong> (read-only)
            <?php else: ?>
              Yahoo Finance-style portfolio table. Drag column headers to reorder. Use the column menu to show/hide columns.
            <?php endif; ?>
          </p>
        </div>

        <div class="portfolio-toolbar">
          <?php if (!$readOnly): ?>
            <div class="portfolio-toolbar-buttons" role="toolbar" aria-label="Portfolio actions">
              <button class="btn btn-primary portfolio-btn" type="button" id="btnAddTicker">Add ticker</button>
              <button class="btn btn-primary portfolio-btn" type="button" id="btnAddColumn">Add column</button>
              <button class="btn btn-primary portfolio-btn" type="button" id="btnRemoveColumn">Remove custom column</button>
              <button class="btn btn-primary portfolio-btn" type="button" id="btnRefreshPrices">Refresh prices</button>
            </div>
          <?php endif; ?>
          <span class="portfolio-hint" id="marketHint"></span>
        </div>

        <div class="portfolio-table-scroll" role="region" aria-label="Portfolio positions table">
          <div id="portfolioGrid"></div>
        </div>
      </section>
    </main>
  </div>

  <script>
    window.PORTFOLIO_CTX = {
      csrf: <?= json_encode($csrf, JSON_UNESCAPED_SLASHES) ?>,
      userId: <?= json_encode($viewUserId, JSON_UNESCAPED_SLASHES) ?>,
      readOnly: <?= json_encode($readOnly, JSON_UNESCAPED_SLASHES) ?>
    };
  </script>
  <script src="portfolio_ui.js"></script>
</body>
</html>
