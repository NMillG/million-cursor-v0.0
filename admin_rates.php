<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/rates_loader.php';
require_auth();

$ratesFile = __DIR__ . '/rates.json';
$rates = load_rates_data($ratesFile);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedAt = trim((string)($_POST['updated_at'] ?? ''));

    $mortgage = [
        ['label' => '30-Year Fixed', 'rate' => trim((string)($_POST['mortgage_30'] ?? ''))],
        ['label' => '20-Year Fixed', 'rate' => trim((string)($_POST['mortgage_20'] ?? ''))],
        ['label' => '15-Year Fixed', 'rate' => trim((string)($_POST['mortgage_15'] ?? ''))],
        ['label' => '10-Year Fixed', 'rate' => trim((string)($_POST['mortgage_10'] ?? ''))],
    ];

    $auto = [
        ['label' => '7-Year Loan', 'rate' => trim((string)($_POST['auto_7'] ?? ''))],
        ['label' => '5-Year Loan', 'rate' => trim((string)($_POST['auto_5'] ?? ''))],
        ['label' => '4-Year Loan', 'rate' => trim((string)($_POST['auto_4'] ?? ''))],
        ['label' => '3-Year Loan', 'rate' => trim((string)($_POST['auto_3'] ?? ''))],
    ];

    $allRates = array_merge(array_column($mortgage, 'rate'), array_column($auto, 'rate'));
    $hasEmpty = false;
    foreach ($allRates as $rate) {
        if ($rate === '') {
            $hasEmpty = true;
            break;
        }
    }

    if ($updatedAt === '') {
        $error = 'Updated date is required.';
    } elseif ($hasEmpty) {
        $error = 'All rate fields are required.';
    } else {
        $payload = [
            'updated_at' => $updatedAt,
            'mortgage' => $mortgage,
            'auto' => $auto,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $error = 'Failed to prepare rates payload.';
        } elseif (file_put_contents($ratesFile, $json . PHP_EOL, LOCK_EX) === false) {
            $error = 'Could not write rates.json.';
        } else {
            $success = 'Rates updated successfully.';
            $rates = $payload;
        }
    }
}

function value_for(array $list, string $label): string
{
    foreach ($list as $item) {
        if (($item['label'] ?? '') === $label) {
            return (string)($item['rate'] ?? '');
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Rate Manager - nMillion</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="landing-topbar">
    <h1 class="landing-brand">nmillion Admin</h1>
    <a class="login-icon-link" href="index.php">Home</a>
  </header>

  <main class="calculator-page">
    <section class="calculator-card">
      <h2>Update Today's Rates</h2>

      <?php if ($error !== ''): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
      <?php endif; ?>
      <?php if ($success !== ''): ?>
        <div class="flash flash-success"><?= h($success) ?></div>
      <?php endif; ?>

      <form method="POST">
        <label class="calc-label" for="updated_at">Updated Date</label>
        <input class="field" id="updated_at" type="date" name="updated_at" value="<?= h((string)($rates['updated_at'] ?? '')) ?>" required>

        <h3>Mortgage Rates</h3>
        <label class="calc-label" for="mortgage_30">30-Year Fixed</label>
        <input class="field" id="mortgage_30" type="text" name="mortgage_30" value="<?= h(value_for((array)$rates['mortgage'], '30-Year Fixed')) ?>" required>
        <label class="calc-label" for="mortgage_20">20-Year Fixed</label>
        <input class="field" id="mortgage_20" type="text" name="mortgage_20" value="<?= h(value_for((array)$rates['mortgage'], '20-Year Fixed')) ?>" required>
        <label class="calc-label" for="mortgage_15">15-Year Fixed</label>
        <input class="field" id="mortgage_15" type="text" name="mortgage_15" value="<?= h(value_for((array)$rates['mortgage'], '15-Year Fixed')) ?>" required>
        <label class="calc-label" for="mortgage_10">10-Year Fixed</label>
        <input class="field" id="mortgage_10" type="text" name="mortgage_10" value="<?= h(value_for((array)$rates['mortgage'], '10-Year Fixed')) ?>" required>

        <h3>Auto Loan Rates</h3>
        <label class="calc-label" for="auto_7">7-Year Loan</label>
        <input class="field" id="auto_7" type="text" name="auto_7" value="<?= h(value_for((array)$rates['auto'], '7-Year Loan')) ?>" required>
        <label class="calc-label" for="auto_5">5-Year Loan</label>
        <input class="field" id="auto_5" type="text" name="auto_5" value="<?= h(value_for((array)$rates['auto'], '5-Year Loan')) ?>" required>
        <label class="calc-label" for="auto_4">4-Year Loan</label>
        <input class="field" id="auto_4" type="text" name="auto_4" value="<?= h(value_for((array)$rates['auto'], '4-Year Loan')) ?>" required>
        <label class="calc-label" for="auto_3">3-Year Loan</label>
        <input class="field" id="auto_3" type="text" name="auto_3" value="<?= h(value_for((array)$rates['auto'], '3-Year Loan')) ?>" required>

        <button class="btn btn-primary" type="submit">Save Rates</button>
      </form>
    </section>
  </main>
</body>
</html>
