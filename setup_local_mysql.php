<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

try {
    $adminDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', MYSQL_HOST, MYSQL_PORT);
    $adminPdo = new PDO($adminDsn, MYSQL_USERNAME, MYSQL_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $adminPdo->exec(
        sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '``', MYSQL_DATABASE)
        )
    );

    require_once __DIR__ . '/db.php';
    get_pdo(); // runs migrations

    fwrite(STDOUT, "MySQL ready. Database: " . MYSQL_DATABASE . PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "MySQL setup failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
