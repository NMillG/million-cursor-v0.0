<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function require_guest(): void
{
    if (is_logged_in()) {
        header('Location: dashboard.php');
        exit;
    }
}

function require_auth(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_ensure(): void
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrf_token(): string
{
    csrf_ensure();
    return (string)$_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    csrf_ensure();
    if ($token === null || $token === '') {
        return false;
    }
    return hash_equals((string)$_SESSION['csrf_token'], $token);
}

function current_user_email(): string
{
    return strtolower(trim((string)($_SESSION['user_email'] ?? '')));
}

function admin_email_list(): array
{
    $raw = trim((string)ADMIN_EMAILS);
    if ($raw === '') {
        return [];
    }
    $parts = preg_split('/[,\s;]+/', $raw) ?: [];
    $out = [];
    foreach ($parts as $p) {
        $p = strtolower(trim((string)$p));
        if ($p !== '') {
            $out[] = $p;
        }
    }
    return array_values(array_unique($out));
}

function is_admin(): bool
{
    $email = current_user_email();
    if ($email === '') {
        return false;
    }
    return in_array($email, admin_email_list(), true);
}

function require_admin(): void
{
    require_auth();
    if (!is_admin()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function recaptcha_debug_enabled(): bool
{
    $v = getenv('RECAPTCHA_DEBUG');
    if ($v === false || $v === '') {
        $v = $_SERVER['RECAPTCHA_DEBUG'] ?? '';
    }
    return $v === '1' || strtolower((string)$v) === 'true';
}

/**
 * POST to Google's siteverify endpoint. Prefers cURL (works when allow_url_fopen is Off).
 */
function recaptcha_siteverify_http(string $postBody): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postBody,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $out = curl_exec($ch);
            $errno = curl_errno($ch);
            $err = curl_error($ch);
            $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($out !== false && $out !== '') {
                return $out;
            }
            if (recaptcha_debug_enabled()) {
                error_log('reCAPTCHA cURL failed errno=' . $errno . ' err=' . $err . ' http=' . $http);
            }
        }
    }

    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        if (recaptcha_debug_enabled()) {
            error_log('reCAPTCHA: allow_url_fopen is disabled and cURL returned no body');
        }
        return null;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postBody,
            'timeout' => 10,
        ],
    ]);
    $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if ($response === false) {
        if (recaptcha_debug_enabled()) {
            error_log('reCAPTCHA file_get_contents to siteverify failed');
        }
        return null;
    }
    return $response;
}

function verify_recaptcha(string $token): bool
{
    $token = trim($token);
    if ($token === '') {
        return false;
    }

    $secret = trim((string)RECAPTCHA_SECRET_KEY);
    if ($secret === '') {
        return false;
    }

    $fields = [
        'secret' => $secret,
        'response' => $token,
    ];
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (is_string($remoteIp) && filter_var($remoteIp, FILTER_VALIDATE_IP)) {
        $fields['remoteip'] = $remoteIp;
    }

    $postBody = http_build_query($fields);
    $response = recaptcha_siteverify_http($postBody);
    if ($response === null || $response === '') {
        return false;
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        if (recaptcha_debug_enabled()) {
            error_log('reCAPTCHA invalid JSON: ' . substr($response, 0, 500));
        }
        return false;
    }

    $ok = !empty($json['success']);
    if (!$ok && recaptcha_debug_enabled()) {
        $codes = $json['error-codes'] ?? [];
        error_log('reCAPTCHA siteverify rejected: ' . json_encode($codes) . ' body=' . substr($response, 0, 300));
    }
    return $ok;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
