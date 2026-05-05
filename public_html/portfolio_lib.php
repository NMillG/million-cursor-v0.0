<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function portfolio_default_prefs(): array
{
    return [
        'version' => 1,
        'columnOrder' => [
            'ticker',
            'shares',
            'cost_per_share',
            'current_price',
            'total_cost',
            'market_value',
            'gain_loss_per_share',
            'gain_loss_per_share_pct',
            'purchase_date',
            'days_holding',
            'comments',
        ],
        'hidden' => [],
        'customColumns' => [],
    ];
}

function portfolio_load_prefs(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT prefs_json FROM portfolio_prefs WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return portfolio_default_prefs();
    }
    $decoded = json_decode((string)$row['prefs_json'], true);
    return is_array($decoded) ? array_replace_recursive(portfolio_default_prefs(), $decoded) : portfolio_default_prefs();
}

function portfolio_save_prefs(PDO $pdo, int $userId, array $prefs): void
{
    $json = json_encode($prefs, JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Failed to encode prefs');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO portfolio_prefs (user_id, prefs_json) VALUES (:user_id, :prefs_json)
         ON DUPLICATE KEY UPDATE prefs_json = VALUES(prefs_json)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'prefs_json' => $json,
    ]);
}

function portfolio_normalize_ticker(string $ticker): string
{
    $ticker = strtoupper(trim($ticker));
    return preg_replace('/[^A-Z0-9\.\-]/', '', $ticker) ?? '';
}

function portfolio_decode_extras(?string $json): array
{
    if ($json === null || $json === '') {
        return [];
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function portfolio_days_holding(string $purchaseDate): int
{
    $start = DateTimeImmutable::createFromFormat('Y-m-d', $purchaseDate);
    if (!$start) {
        return 0;
    }
    $today = new DateTimeImmutable('today');
    return (int)$start->diff($today)->format('%a');
}

function portfolio_compute_row(array $dbRow, array $extras): array
{
    $shares = (float)$dbRow['shares'];
    $cost = (float)$dbRow['cost_per_share'];
    $last = isset($dbRow['current_price']) && $dbRow['current_price'] !== null ? (float)$dbRow['current_price'] : null;

    $totalCost = $shares * $cost;
    $marketValue = $last !== null ? $shares * $last : null;

    $gainPerShare = null;
    $gainPct = null;
    if ($last !== null) {
        $gainPerShare = $last - $cost;
        $gainPct = $cost != 0.0 ? ($gainPerShare / $cost) * 100.0 : null;
    }

    return [
        'id' => (int)$dbRow['id'],
        'ticker' => (string)$dbRow['ticker'],
        'shares' => $shares,
        'cost_per_share' => $cost,
        'current_price' => $last,
        'total_cost' => $totalCost,
        'market_value' => $marketValue,
        'gain_loss_per_share' => $gainPerShare,
        'gain_loss_per_share_pct' => $gainPct,
        'purchase_date' => (string)$dbRow['purchase_date'],
        'days_holding' => portfolio_days_holding((string)$dbRow['purchase_date']),
        'comments' => (string)($dbRow['comments'] ?? ''),
        'extras' => $extras,
        'price_updated_at' => $dbRow['price_updated_at'] ?? null,
    ];
}

function tiingo_fetch_iex_quotes(array $tickers, string $apiKey): array
{
    $tickers = array_values(array_unique(array_filter($tickers, static fn(string $t): bool => $t !== '')));
    if ($tickers === [] || $apiKey === '') {
        return [];
    }

    $tickerList = implode(',', $tickers);
    $url = 'https://api.tiingo.com/iex/?tickers=' . rawurlencode($tickerList) . '&token=' . rawurlencode($apiKey);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "Accept: application/json\r\n",
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [];
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : [];
}

function tiingo_last_price(array $quote): ?float
{
    foreach (['last', 'tngoLast', 'prevClose'] as $k) {
        if (isset($quote[$k]) && $quote[$k] !== null && $quote[$k] !== '') {
            $v = (float)$quote[$k];
            if ($v > 0) {
                return $v;
            }
        }
    }
    return null;
}

function portfolio_refresh_prices(PDO $pdo, int $userId): void
{
    if (TIINGO_API_KEY === '') {
        return;
    }

    $stmt = $pdo->prepare('SELECT DISTINCT ticker FROM portfolio_positions WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    $tickers = [];
    while ($row = $stmt->fetch()) {
        $t = portfolio_normalize_ticker((string)($row['ticker'] ?? ''));
        if ($t !== '') {
            $tickers[] = $t;
        }
    }
    if ($tickers === []) {
        return;
    }

    $quotes = tiingo_fetch_iex_quotes($tickers, TIINGO_API_KEY);
    $map = [];
    foreach ($quotes as $q) {
        $sym = portfolio_normalize_ticker((string)($q['ticker'] ?? ''));
        if ($sym === '') {
            continue;
        }
        $price = tiingo_last_price($q);
        if ($price !== null) {
            $map[$sym] = $price;
        }
    }
    if ($map === []) {
        return;
    }

    $update = $pdo->prepare(
        'UPDATE portfolio_positions
         SET current_price = :current_price, price_updated_at = NOW()
         WHERE user_id = :user_id AND ticker = :ticker'
    );

    foreach ($map as $sym => $price) {
        $update->execute([
            'current_price' => $price,
            'user_id' => $userId,
            'ticker' => $sym,
        ]);
    }
}
