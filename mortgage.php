<?php
declare(strict_types=1);

$loanAmount = (float)($_POST['loan_amount'] ?? 300000);
$annualRate = (float)($_POST['annual_rate'] ?? 6.5);
$loanYears = (int)($_POST['loan_years'] ?? 30);
$monthlyPayment = null;
$totalPayment = null;
$totalInterest = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($loanAmount <= 0 || $annualRate < 0 || $loanYears <= 0) {
        $error = 'Please enter valid positive values.';
    } else {
        $months = $loanYears * 12;
        $monthlyRate = ($annualRate / 100) / 12;

        if ($monthlyRate == 0.0) {
            $monthlyPayment = $loanAmount / $months;
        } else {
            $factor = pow(1 + $monthlyRate, $months);
            $monthlyPayment = $loanAmount * (($monthlyRate * $factor) / ($factor - 1));
        }

        $totalPayment = $monthlyPayment * $months;
        $totalInterest = $totalPayment - $loanAmount;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mortgage Loan Calculator - nMillion</title>
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
      <h2>Mortgage Loan Calculator</h2>
      <p>Enter your loan details to estimate monthly and total repayment.</p>

      <?php if ($error !== ''): ?>
        <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="POST">
        <label class="calc-label" for="loan_amount">Loan Amount ($)</label>
        <input class="field" id="loan_amount" type="number" name="loan_amount" min="1" step="0.01" value="<?= htmlspecialchars((string)$loanAmount, ENT_QUOTES, 'UTF-8') ?>" required>

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
