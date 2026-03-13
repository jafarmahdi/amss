<?php

declare(strict_types=1);

use App\Support\DataRepository;

require __DIR__ . '/../bootstrap.php';

$options = getopt('', ['apply', 'limit::']);
$apply = array_key_exists('apply', $options);
$limit = null;

if (array_key_exists('limit', $options)) {
    $limitValue = trim((string) $options['limit']);
    if ($limitValue !== '' && ctype_digit($limitValue)) {
        $limit = max(1, (int) $limitValue);
    }
}

$summary = DataRepository::reconcileAssetInventory($apply, $limit);
DataRepository::logAudit(
    $apply ? 'reconcile_apply' : 'reconcile_preview',
    'assets',
    null,
    null,
    ['script' => basename(__FILE__)] + $summary
);

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
