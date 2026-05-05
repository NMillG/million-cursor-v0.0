<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function market_fallback(): array
{
    return [
        'source' => 'fallback',
        'trending' => [
            ['symbol' => 'NVDA', 'name' => 'NVIDIA Corp.', 'price' => 128.42, 'change' => 2.31, 'pct' => 1.83],
            ['symbol' => 'AAPL', 'name' => 'Apple Inc.', 'price' => 214.15, 'change' => -0.88, 'pct' => -0.41],
            ['symbol' => 'MSFT', 'name' => 'Microsoft Corp.', 'price' => 418.22, 'change' => 3.67, 'pct' => 0.89],
            ['symbol' => 'AMZN', 'name' => 'Amazon.com Inc.', 'price' => 186.40, 'change' => 1.12, 'pct' => 0.60],
            ['symbol' => 'META', 'name' => 'Meta Platforms', 'price' => 512.30, 'change' => -4.20, 'pct' => -0.81],
        ],
        'gainers' => [
            ['symbol' => 'RDDT', 'name' => 'Reddit Inc.', 'price' => 78.90, 'change' => 6.40, 'pct' => 8.82],
            ['symbol' => 'SMCI', 'name' => 'Super Micro', 'price' => 42.15, 'change' => 3.10, 'pct' => 7.94],
            ['symbol' => 'COIN', 'name' => 'Coinbase', 'price' => 245.60, 'change' => 14.20, 'pct' => 6.13],
            ['symbol' => 'PLTR', 'name' => 'Palantir', 'price' => 36.88, 'change' => 2.05, 'pct' => 5.89],
            ['symbol' => 'MSTR', 'name' => 'MicroStrategy', 'price' => 312.40, 'change' => 16.80, 'pct' => 5.68],
        ],
        'losers' => [
            ['symbol' => 'INTC', 'name' => 'Intel Corp.', 'price' => 20.05, 'change' => -1.35, 'pct' => -6.31],
            ['symbol' => 'DIS', 'name' => 'Walt Disney', 'price' => 88.12, 'change' => -3.44, 'pct' => -3.76],
            ['symbol' => 'NKE', 'name' => 'Nike Inc.', 'price' => 72.30, 'change' => -2.10, 'pct' => -2.82],
            ['symbol' => 'BA', 'name' => 'Boeing Co.', 'price' => 168.90, 'change' => -4.55, 'pct' => -2.62],
            ['symbol' => 'TSLA', 'name' => 'Tesla Inc.', 'price' => 178.50, 'change' => -4.20, 'pct' => -2.30],
        ],
    ];
}

function load_market_data(): array
{
    if (TIINGO_API_KEY === '') {
        return market_fallback();
    }

    $trendingSymbols = ['AAPL', 'MSFT', 'NVDA', 'AMZN', 'TSLA', 'META', 'GOOGL', 'NFLX'];
    $universeSymbols = [
        'AAPL', 'MSFT', 'NVDA', 'AMZN', 'TSLA', 'META', 'GOOGL', 'NFLX', 'AMD', 'INTC',
        'PLTR', 'COIN', 'SMCI', 'MSTR', 'RDDT', 'DIS', 'NKE', 'BA', 'JPM', 'WMT',
    ];

    $quotes = tiingo_fetch_quotes($universeSymbols, TIINGO_API_KEY);
    if ($quotes === []) {
        return market_fallback();
    }

    $mapped = [];
    foreach ($quotes as $quote) {
        $symbol = (string)($quote['ticker'] ?? '');
        if ($symbol === '') {
            continue;
        }
        $last = (float)($quote['last'] ?? $quote['tngoLast'] ?? 0);
        $prevClose = (float)($quote['prevClose'] ?? 0);
        if ($last <= 0) {
            continue;
        }

        $change = $prevClose > 0 ? $last - $prevClose : (float)($quote['dailyChange'] ?? 0);
        $pct = $prevClose > 0 ? ($change / $prevClose) * 100 : (float)($quote['dailyPercentChange'] ?? 0);
        $name = (string)($quote['name'] ?? $symbol);
        if ($name === '') {
            $name = $symbol;
        }

        $mapped[] = [
            'symbol' => $symbol,
            'name' => $name,
            'price' => round($last, 2),
            'change' => round($change, 2),
            'pct' => round($pct, 2),
        ];
    }

    if ($mapped === []) {
        return market_fallback();
    }

    $trending = [];
    foreach ($trendingSymbols as $sym) {
        foreach ($mapped as $row) {
            if ($row['symbol'] === $sym) {
                $trending[] = $row;
                break;
            }
        }
        if (count($trending) >= 5) {
            break;
        }
    }

    usort($mapped, static fn(array $a, array $b): int => $b['pct'] <=> $a['pct']);
    $gainers = array_slice(array_values(array_filter($mapped, static fn(array $r): bool => $r['pct'] >= 0)), 0, 5);
    $losers = array_slice(array_values(array_filter(array_reverse($mapped), static fn(array $r): bool => $r['pct'] < 0)), 0, 5);

    if ($trending === []) {
        $trending = array_slice($mapped, 0, 5);
    }
    if ($gainers === []) {
        $gainers = array_slice($mapped, 0, 5);
    }
    if ($losers === []) {
        $losers = array_slice(array_reverse($mapped), 0, 5);
    }

    return [
        'source' => 'tiingo',
        'trending' => $trending,
        'gainers' => $gainers,
        'losers' => $losers,
    ];
}

function tiingo_fetch_quotes(array $symbols, string $apiKey): array
{
    $tickerList = implode(',', $symbols);
    $url = 'https://api.tiingo.com/iex/?tickers=' . rawurlencode($tickerList) . '&token=' . rawurlencode($apiKey);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 8,
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
