<?php
declare(strict_types=1);

function default_rates_data(): array
{
    return [
        'updated_at' => date('Y-m-d'),
        'mortgage' => [
            ['label' => '30-Year Fixed', 'rate' => '6.89%'],
            ['label' => '20-Year Fixed', 'rate' => '6.62%'],
            ['label' => '15-Year Fixed', 'rate' => '6.21%'],
            ['label' => '10-Year Fixed', 'rate' => '6.05%'],
        ],
        'auto' => [
            ['label' => '7-Year Loan', 'rate' => '7.35%'],
            ['label' => '5-Year Loan', 'rate' => '6.79%'],
            ['label' => '4-Year Loan', 'rate' => '6.42%'],
            ['label' => '3-Year Loan', 'rate' => '6.08%'],
        ],
    ];
}

function load_rates_data(string $jsonFile): array
{
    if (!is_file($jsonFile)) {
        return default_rates_data();
    }

    $raw = file_get_contents($jsonFile);
    if ($raw === false) {
        return default_rates_data();
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return default_rates_data();
    }

    $defaults = default_rates_data();
    $decoded['updated_at'] = (string)($decoded['updated_at'] ?? $defaults['updated_at']);
    $decoded['mortgage'] = is_array($decoded['mortgage'] ?? null) ? $decoded['mortgage'] : $defaults['mortgage'];
    $decoded['auto'] = is_array($decoded['auto'] ?? null) ? $decoded['auto'] : $defaults['auto'];

    return $decoded;
}
