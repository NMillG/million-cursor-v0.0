<?php
declare(strict_types=1);
require_once __DIR__ . '/rates_loader.php';
require_once __DIR__ . '/market_data.php';
$rates = load_rates_data(__DIR__ . '/rates.json');
$market = load_market_data();
$trendingTickers = $market['trending'];
$topGainers = $market['gainers'];
$topLosers = $market['losers'];
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
  <header class="landing-topbar">
    <h1 class="landing-brand">nmillion</h1>
    <div class="portfolio-top-actions">
      <a class="login-icon-link" href="login.php" aria-label="Login">&#128100; Login</a>
      <a class="login-icon-link" href="portfolio.php">Portfolio</a>
    </div>
  </header>

  <main class="landing-page">
    <section class="landing-grid">
      <article class="landing-card">
        <h2>Mortgage Loan Calculator</h2>
        <p>Estimate monthly payment, total interest, and payoff breakdown for your home loan.</p>
        <a class="landing-action" href="mortgage.php">Open Mortgage Calculator</a>

        <div class="rates-box">
          <h3>Today's Mortgage Rates</h3>
          <p class="rates-updated">Updated: <?= htmlspecialchars($rates['updated_at'], ENT_QUOTES, 'UTF-8') ?></p>
          <ul>
            <?php foreach ($rates['mortgage'] as $item): ?>
              <li>
                <span><?= htmlspecialchars((string)($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= htmlspecialchars((string)($item['rate'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </article>

      <article class="landing-card">
        <h2>Auto Loan Calculator</h2>
        <p>Quickly evaluate monthly installments, APR impact, and total cost of your auto loan.</p>
        <a class="landing-action" href="autoloan.php">Open Auto Loan Calculator</a>

        <div class="rates-box">
          <h3>Today's Auto Loan Rates</h3>
          <p class="rates-updated">Updated: <?= htmlspecialchars($rates['updated_at'], ENT_QUOTES, 'UTF-8') ?></p>
          <ul>
            <?php foreach ($rates['auto'] as $item): ?>
              <li>
                <span><?= htmlspecialchars((string)($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= htmlspecialchars((string)($item['rate'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </article>
    </section>

    <section class="landing-market-grid" aria-label="Market snapshot">
      <article class="landing-card market-card">
        <h2>Trending Tickers</h2>
        <p class="market-disclaimer">Source: <?= htmlspecialchars($market['source'], ENT_QUOTES, 'UTF-8') === 'tiingo' ? 'Tiingo' : 'Fallback sample data' ?></p>
        <table class="market-table">
          <thead>
            <tr>
              <th scope="col">Symbol</th>
              <th scope="col">Last</th>
              <th scope="col">Chg</th>
              <th scope="col">% Chg</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trendingTickers as $row): ?>
              <tr>
                <td>
                  <span class="sym"><?= htmlspecialchars($row['symbol'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="name"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td class="<?= $row['change'] >= 0 ? 'pos' : 'neg' ?>"><?= ($row['change'] >= 0 ? '+' : '') . number_format($row['change'], 2) ?></td>
                <td class="<?= $row['pct'] >= 0 ? 'pos' : 'neg' ?>"><?= ($row['pct'] >= 0 ? '+' : '') . number_format($row['pct'], 2) ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </article>

      <article class="landing-card market-card">
        <h2>Top Gainers</h2>
        <p class="market-disclaimer">Source: <?= htmlspecialchars($market['source'], ENT_QUOTES, 'UTF-8') === 'tiingo' ? 'Tiingo' : 'Fallback sample data' ?></p>
        <table class="market-table">
          <thead>
            <tr>
              <th scope="col">Symbol</th>
              <th scope="col">Last</th>
              <th scope="col">Chg</th>
              <th scope="col">% Chg</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topGainers as $row): ?>
              <tr>
                <td>
                  <span class="sym"><?= htmlspecialchars($row['symbol'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="name"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td class="pos">+<?= number_format($row['change'], 2) ?></td>
                <td class="pos">+<?= number_format($row['pct'], 2) ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </article>

      <article class="landing-card market-card">
        <h2>Top Losers</h2>
        <p class="market-disclaimer">Source: <?= htmlspecialchars($market['source'], ENT_QUOTES, 'UTF-8') === 'tiingo' ? 'Tiingo' : 'Fallback sample data' ?></p>
        <table class="market-table">
          <thead>
            <tr>
              <th scope="col">Symbol</th>
              <th scope="col">Last</th>
              <th scope="col">Chg</th>
              <th scope="col">% Chg</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topLosers as $row): ?>
              <tr>
                <td>
                  <span class="sym"><?= htmlspecialchars($row['symbol'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="name"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td class="neg"><?= number_format($row['change'], 2) ?></td>
                <td class="neg"><?= number_format($row['pct'], 2) ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </article>
    </section>
  </main>
</body>
</html>
