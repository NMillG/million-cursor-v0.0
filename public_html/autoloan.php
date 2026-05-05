<?php
declare(strict_types=1);

$carPrice = (float)($_POST['car_price'] ?? 35000);
$downPayment = (float)($_POST['down_payment'] ?? 5000);
$annualRate = (float)($_POST['annual_rate'] ?? 5.5);
$loanYears = (int)($_POST['loan_years'] ?? 5);
$monthlyPayment = null;
$totalPayment = null;
$totalInterest = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $principal = $carPrice - $downPayment;
    if ($carPrice <= 0 || $downPayment < 0 || $principal <= 0 || $annualRate < 0 || $loanYears <= 0) {
        $error = 'Please enter valid values. Down payment must be less than car price.';
    } else {
        $months = $loanYears * 12;
        $monthlyRate = ($annualRate / 100) / 12;

        if ($monthlyRate == 0.0) {
            $monthlyPayment = $principal / $months;
        } else {
            $factor = pow(1 + $monthlyRate, $months);
            $monthlyPayment = $principal * (($monthlyRate * $factor) / ($factor - 1));
        }

        $totalPayment = $monthlyPayment * $months;
        $totalInterest = $totalPayment - $principal;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auto Loan Calculator - nMillion</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="landing-topbar">
    <h1 class="landing-brand">nmillion</h1>
    <div class="portfolio-top-actions">
      <a class="login-icon-link" href="login.php" aria-label="Login">&#128100; Login</a>
      <a class="login-icon-link" href="portfolio.php">Portfolio</a>
    </div>
  </header>

  <main class="calculator-page">
    <section class="calculator-card">
      <h2>Auto Loan Calculator</h2>
      <p>Estimate monthly EMI, total payment, and total interest for your auto loan.</p>

      <?php if ($error !== ''): ?>
        <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="POST">
        <label class="calc-label" for="car_price">Car Price ($)</label>
        <input class="field" id="car_price" type="number" name="car_price" min="1" step="0.01" value="<?= htmlspecialchars((string)$carPrice, ENT_QUOTES, 'UTF-8') ?>" required>

        <label class="calc-label" for="down_payment">Down Payment ($)</label>
        <input class="field" id="down_payment" type="number" name="down_payment" min="0" step="0.01" value="<?= htmlspecialchars((string)$downPayment, ENT_QUOTES, 'UTF-8') ?>" required>

        <label class="calc-label" for="annual_rate">Interest Rate (% per year)</label>
        <input class="field" id="annual_rate" type="number" name="annual_rate" min="0" step="0.01" value="<?= htmlspecialchars((string)$annualRate, ENT_QUOTES, 'UTF-8') ?>" required>

        <label class="calc-label" for="loan_years">Loan Term (Years)</label>
        <input class="field" id="loan_years" type="number" name="loan_years" min="1" step="1" value="<?= htmlspecialchars((string)$loanYears, ENT_QUOTES, 'UTF-8') ?>" required>

        <button class="btn btn-primary" type="submit">Calculate</button>
      </form>

      <?php if ($monthlyPayment !== null): ?>
        <div class="result-grid">
          <div class="result-box">
            <h3>Monthly Payment</h3>
            <p>$<?= number_format($monthlyPayment, 2) ?></p>
          </div>
          <div class="result-box">
            <h3>Total Payment</h3>
            <p>$<?= number_format((float)$totalPayment, 2) ?></p>
          </div>
          <div class="result-box">
            <h3>Total Interest</h3>
            <p>$<?= number_format((float)$totalInterest, 2) ?></p>
          </div>
        </div>
      <?php endif; ?>

      <a class="link" href="index.php">Back to Home</a>
    </section>
  </main>
</body>
</html>
