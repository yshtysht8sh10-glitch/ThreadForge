<?php

declare(strict_types=1);

require_once __DIR__ . '/api.php';

if (PHP_SAPI !== 'cli') {
    $pdo = getConnection();
    requireCronApiKey($pdo);
    socialDebugLog('cron web social reaction refresh start');
    $result = runSocialReactionRefresh($pdo);
    socialDebugLog('cron web social reaction refresh complete', [
        'updated' => $result['updated'],
        'checked_posts' => $result['checked_posts'],
        'errors' => count($result['errors']),
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'SNS reactions refreshed.',
        'updated' => $result['updated'],
        'checked_posts' => $result['checked_posts'],
        'recent_days' => $result['recent_days'],
        'errors' => $result['errors'],
    ]);
}

socialDebugLog('cron social reaction refresh start');
$result = runSocialReactionRefresh(getConnection());
socialDebugLog('cron social reaction refresh complete', [
    'updated' => $result['updated'],
    'checked_posts' => $result['checked_posts'],
    'errors' => count($result['errors']),
]);

echo json_encode([
    'success' => true,
    'message' => 'SNS reactions refreshed.',
    'updated' => $result['updated'],
    'checked_posts' => $result['checked_posts'],
    'recent_days' => $result['recent_days'],
    'errors' => $result['errors'],
], JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(count($result['errors']) === 0 ? 0 : 1);
