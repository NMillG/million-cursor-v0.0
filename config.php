<?php
declare(strict_types=1);

/**
 * Read environment variable with fallback.
 * Checks getenv(), $_SERVER (Apache SetEnv), and REDIRECT_$key (common after rewrites).
 *
 * @param bool $trim when true, trim() the resolved value (use for API keys; not for passwords that may intentionally contain spaces)
 */
function env_or(string $key, string $fallback, bool $trim = false): string
{
    $sources = [];
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        $sources[] = $value;
    }
    if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
        $sources[] = $_SERVER[$key];
    }
    $redirectKey = 'REDIRECT_' . $key;
    if (isset($_SERVER[$redirectKey]) && is_string($_SERVER[$redirectKey]) && $_SERVER[$redirectKey] !== '') {
        $sources[] = $_SERVER[$redirectKey];
    }
    foreach ($sources as $s) {
        $out = $trim ? trim($s) : $s;
        if ($out !== '') {
            return $out;
        }
    }
    return $fallback;
}

/**
 * Basic app configuration.
 * Defaults are tuned for local MySQL on localhost.
 */
define('MYSQL_HOST', env_or('MYSQL_HOST', '127.0.0.1'));
define('MYSQL_PORT', (int)env_or('MYSQL_PORT', '3306'));
define('MYSQL_DATABASE', env_or('MYSQL_DATABASE', 'nmillion'));
define('MYSQL_USERNAME', env_or('MYSQL_USERNAME', 'root'));
define('MYSQL_PASSWORD', env_or('MYSQL_PASSWORD', ''));
define('APP_BASE_URL', env_or('APP_BASE_URL', 'http://localhost:8000'));
define('TIINGO_API_KEY', env_or('TIINGO_API_KEY', ''));
define('ADMIN_EMAILS', env_or('ADMIN_EMAILS', ''));

// Google reCAPTCHA v2: set RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY in the
// server environment (cPanel → Set Environment Variable) or in .htaccess SetEnv.
// Create production keys at https://www.google.com/recaptcha/admin — choose
// reCAPTCHA v2 "I'm not a robot" Checkbox, and add nmillion.com (and www).
//
// Defaults below are Google's documented *test* pair (must match each other).
// They only work for local/dev; production must use your own keys.
define(
    'RECAPTCHA_SITE_KEY',
    env_or('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', true)
);
define(
    'RECAPTCHA_SECRET_KEY',
    env_or('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe', true)
);
