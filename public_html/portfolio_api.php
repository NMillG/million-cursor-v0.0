<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portfolio_lib.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function resolve_target_user_id(array $body): int
{
    $requested = isset($body['user_id']) ? (int)$body['user_id'] : 0;
    $selfId = (int)($_SESSION['user_id'] ?? 0);
    if ($selfId <= 0) {
        json_out(401, ['ok' => false, 'error' => 'Not authenticated']);
    }
    if ($requested <= 0 || $requested === $selfId) {
        return $selfId;
    }
    if (!is_admin()) {
        json_out(403, ['ok' => false, 'error' => 'Forbidden']);
    }
    return $requested;
}

function user_exists(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    return (bool)$stmt->fetch();
}

function fetch_positions(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, ticker, shares, cost_per_share, purchase_date, comments, extra_json, current_price, price_updated_at
         FROM portfolio_positions
         WHERE user_id = :user_id
         ORDER BY id ASC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = [];
    while ($row = $stmt->fetch()) {
        $extras = portfolio_decode_extras(isset($row['extra_json']) ? (string)$row['extra_json'] : null);
        $computed = portfolio_compute_row($row, $extras);
        foreach ($extras as $k => $v) {
            if (!is_string($k) || $k === '') {
                continue;
            }
            $computed[$k] = $v;
        }
        $rows[] = $computed;
    }
    return $rows;
}

function fetch_positions_sorted(PDO $pdo, int $userId): array
{
    $prefs = portfolio_load_prefs($pdo, $userId);
    $rows = fetch_positions($pdo, $userId);

    return portfolio_sort_rows_by_prefs($rows, $prefs);
}

function merge_extras(array $existing, array $incoming): array
{
    $reserved = [
        'id',
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
        'extras',
        'price_updated_at',
    ];

    $out = $existing;
    foreach ($incoming as $k => $v) {
        if (!is_string($k) || $k === '') {
            continue;
        }
        if (in_array($k, $reserved, true)) {
            continue;
        }
        if ($v === null) {
            unset($out[$k]);
            continue;
        }
        if (is_scalar($v)) {
            $out[$k] = (string)$v;
        } elseif (is_array($v)) {
            $out[$k] = json_encode($v);
        }
    }
    return $out;
}

try {
    $body = read_json_body();
    $action = (string)($body['action'] ?? '');
    if ($action === '') {
        json_out(400, ['ok' => false, 'error' => 'Missing action']);
    }

    if ($action !== 'list' && !csrf_verify(isset($body['csrf']) ? (string)$body['csrf'] : null)) {
        json_out(403, ['ok' => false, 'error' => 'Invalid CSRF token']);
    }

    $pdo = get_pdo();
    $targetUserId = resolve_target_user_id($body);

    if (!user_exists($pdo, $targetUserId)) {
        json_out(404, ['ok' => false, 'error' => 'User not found']);
    }

    if ($action === 'list') {
        portfolio_refresh_prices($pdo, $targetUserId);
        $prefs = portfolio_load_prefs($pdo, $targetUserId);
        json_out(200, [
            'ok' => true,
            'user_id' => $targetUserId,
            'prefs' => $prefs,
            'rows' => portfolio_sort_rows_by_prefs(fetch_positions($pdo, $targetUserId), $prefs),
            'tiingo' => TIINGO_API_KEY !== '',
        ]);
    }

    if ($action === 'save_prefs') {
        $prefs = $body['prefs'] ?? null;
        if (!is_array($prefs)) {
            json_out(400, ['ok' => false, 'error' => 'Invalid prefs']);
        }
        $merged = array_replace_recursive(portfolio_default_prefs(), $prefs);
        portfolio_save_prefs($pdo, $targetUserId, $merged);
        json_out(200, ['ok' => true, 'prefs' => portfolio_load_prefs($pdo, $targetUserId)]);
    }

    if ($action === 'refresh_prices') {
        portfolio_refresh_prices($pdo, $targetUserId);
        json_out(200, ['ok' => true, 'rows' => fetch_positions_sorted($pdo, $targetUserId)]);
    }

    if ($action === 'create_row') {
        $ticker = portfolio_normalize_ticker((string)($body['ticker'] ?? ''));
        if ($ticker === '') {
            json_out(400, ['ok' => false, 'error' => 'Ticker is required']);
        }
        $shares = (float)($body['shares'] ?? 0);
        $cost = (float)($body['cost_per_share'] ?? 0);
        $purchaseDate = trim((string)($body['purchase_date'] ?? ''));
        $comments = trim((string)($body['comments'] ?? ''));
        $extrasIn = is_array($body['extras'] ?? null) ? $body['extras'] : [];

        if ($shares <= 0 || $cost < 0) {
            json_out(400, ['ok' => false, 'error' => 'Invalid shares or cost']);
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $purchaseDate);
        if (!$dt) {
            json_out(400, ['ok' => false, 'error' => 'Invalid purchase_date (use YYYY-MM-DD)']);
        }

        $extras = merge_extras([], $extrasIn);
        $extraJson = $extras === [] ? null : json_encode($extras, JSON_UNESCAPED_SLASHES);

        $stmt = $pdo->prepare(
            'INSERT INTO portfolio_positions (user_id, ticker, shares, cost_per_share, purchase_date, comments, extra_json)
             VALUES (:user_id, :ticker, :shares, :cost_per_share, :purchase_date, :comments, :extra_json)'
        );
        $stmt->execute([
            'user_id' => $targetUserId,
            'ticker' => $ticker,
            'shares' => $shares,
            'cost_per_share' => $cost,
            'purchase_date' => $dt->format('Y-m-d'),
            'comments' => $comments === '' ? null : $comments,
            'extra_json' => $extraJson,
        ]);

        portfolio_refresh_prices($pdo, $targetUserId);
        json_out(200, ['ok' => true, 'rows' => fetch_positions_sorted($pdo, $targetUserId)]);
    }

    if ($action === 'update_row') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_out(400, ['ok' => false, 'error' => 'Invalid id']);
        }

        $stmt = $pdo->prepare('SELECT * FROM portfolio_positions WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $id, 'user_id' => $targetUserId]);
        $existing = $stmt->fetch();
        if (!$existing) {
            json_out(404, ['ok' => false, 'error' => 'Row not found']);
        }

        $ticker = isset($body['ticker']) ? portfolio_normalize_ticker((string)$body['ticker']) : (string)$existing['ticker'];
        if ($ticker === '') {
            json_out(400, ['ok' => false, 'error' => 'Ticker is required']);
        }

        $shares = isset($body['shares']) ? (float)$body['shares'] : (float)$existing['shares'];
        $cost = isset($body['cost_per_share']) ? (float)$body['cost_per_share'] : (float)$existing['cost_per_share'];
        $purchaseDate = isset($body['purchase_date']) ? trim((string)$body['purchase_date']) : (string)$existing['purchase_date'];
        $comments = array_key_exists('comments', $body) ? trim((string)$body['comments']) : (string)($existing['comments'] ?? '');

        if ($shares <= 0 || $cost < 0) {
            json_out(400, ['ok' => false, 'error' => 'Invalid shares or cost']);
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $purchaseDate);
        if (!$dt) {
            json_out(400, ['ok' => false, 'error' => 'Invalid purchase_date (use YYYY-MM-DD)']);
        }

        $extrasExisting = portfolio_decode_extras(isset($existing['extra_json']) ? (string)$existing['extra_json'] : null);
        $extrasIn = is_array($body['extras'] ?? null) ? $body['extras'] : [];
        $extras = $extrasIn === [] ? $extrasExisting : merge_extras($extrasExisting, $extrasIn);
        $extraJson = $extras === [] ? null : json_encode($extras, JSON_UNESCAPED_SLASHES);

        $upd = $pdo->prepare(
            'UPDATE portfolio_positions
             SET ticker = :ticker,
                 shares = :shares,
                 cost_per_share = :cost_per_share,
                 purchase_date = :purchase_date,
                 comments = :comments,
                 extra_json = :extra_json
             WHERE id = :id AND user_id = :user_id'
        );
        $upd->execute([
            'ticker' => $ticker,
            'shares' => $shares,
            'cost_per_share' => $cost,
            'purchase_date' => $dt->format('Y-m-d'),
            'comments' => $comments === '' ? null : $comments,
            'extra_json' => $extraJson,
            'id' => $id,
            'user_id' => $targetUserId,
        ]);

        portfolio_refresh_prices($pdo, $targetUserId);
        json_out(200, ['ok' => true, 'rows' => fetch_positions_sorted($pdo, $targetUserId)]);
    }

    if ($action === 'delete_row') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            json_out(400, ['ok' => false, 'error' => 'Invalid id']);
        }
        $stmt = $pdo->prepare('DELETE FROM portfolio_positions WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $targetUserId]);
        json_out(200, ['ok' => true, 'rows' => fetch_positions_sorted($pdo, $targetUserId)]);
    }

    if ($action === 'add_custom_column') {
        $field = trim((string)($body['field'] ?? ''));
        $title = trim((string)($body['title'] ?? ''));
        if ($field === '' || $title === '') {
            json_out(400, ['ok' => false, 'error' => 'field and title are required']);
        }
        $field = strtolower($field);
        $field = preg_replace('/[^a-z0-9_]/', '', $field) ?? '';
        if ($field === '') {
            json_out(400, ['ok' => false, 'error' => 'Invalid field key']);
        }
        if (!str_starts_with($field, 'x_')) {
            $field = 'x_' . $field;
        }

        $prefs = portfolio_load_prefs($pdo, $targetUserId);
        $custom = is_array($prefs['customColumns'] ?? null) ? $prefs['customColumns'] : [];
        $custom[] = ['field' => $field, 'title' => $title];
        $prefs['customColumns'] = $custom;
        if (!in_array($field, $prefs['columnOrder'], true)) {
            $prefs['columnOrder'][] = $field;
        }
        portfolio_save_prefs($pdo, $targetUserId, $prefs);
        json_out(200, ['ok' => true, 'prefs' => portfolio_load_prefs($pdo, $targetUserId)]);
    }

    if ($action === 'remove_custom_column') {
        $field = trim((string)($body['field'] ?? ''));
        if ($field === '') {
            json_out(400, ['ok' => false, 'error' => 'field is required']);
        }

        $prefs = portfolio_load_prefs($pdo, $targetUserId);
        $custom = [];
        foreach ((array)($prefs['customColumns'] ?? []) as $c) {
            if (is_array($c) && (($c['field'] ?? '') !== $field)) {
                $custom[] = $c;
            }
        }
        $prefs['customColumns'] = $custom;
        $prefs['columnOrder'] = array_values(array_filter((array)($prefs['columnOrder'] ?? []), static fn($f) => (string)$f !== $field));
        $prefs['hidden'] = array_values(array_filter((array)($prefs['hidden'] ?? []), static fn($f) => (string)$f !== $field));
        portfolio_save_prefs($pdo, $targetUserId, $prefs);

        $rowsStmt = $pdo->prepare('SELECT id, extra_json FROM portfolio_positions WHERE user_id = :user_id');
        $rowsStmt->execute(['user_id' => $targetUserId]);
        $upd = $pdo->prepare('UPDATE portfolio_positions SET extra_json = :extra_json WHERE id = :id AND user_id = :user_id');
        while ($r = $rowsStmt->fetch()) {
            $extras = portfolio_decode_extras(isset($r['extra_json']) ? (string)$r['extra_json'] : null);
            unset($extras[$field]);
            $json = $extras === [] ? null : json_encode($extras, JSON_UNESCAPED_SLASHES);
            $upd->execute(['extra_json' => $json, 'id' => (int)$r['id'], 'user_id' => $targetUserId]);
        }

        json_out(200, ['ok' => true, 'prefs' => portfolio_load_prefs($pdo, $targetUserId), 'rows' => fetch_positions_sorted($pdo, $targetUserId)]);
    }

    json_out(400, ['ok' => false, 'error' => 'Unknown action']);
} catch (Throwable $e) {
    json_out(500, ['ok' => false, 'error' => 'Server error']);
}
