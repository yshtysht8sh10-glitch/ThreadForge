<?php

declare(strict_types=1);

require_once __DIR__ . '/api.php';

if (PHP_SAPI !== 'cli') {
    $pdo = getConnection();
    requireCronApiKey($pdo);
    socialDebugLog('cron web social reaction refresh start');
    $result = runSocialReactionRefresh($pdo);
    $testCleanup = FRONTEND_ID === 'proxy-release' ? cleanupExpiredTestPublications($pdo, FRONTEND_ID) : null;
    socialDebugLog('cron web social reaction refresh complete', [
        'updated' => $result['updated'],
        'checked_posts' => $result['checked_posts'],
        'errors' => count($result['errors']),
    ]);

    jsonResponse([
        'success' => count($result['errors']) === 0 && (int)($testCleanup['failed'] ?? 0) === 0,
        'message' => 'SNS reactions refreshed.',
        'updated' => $result['updated'],
        'checked_posts' => $result['checked_posts'],
        'recent_days' => $result['recent_days'],
        'errors' => $result['errors'],
        'test_publication_cleanup' => $testCleanup,
    ]);
}

socialDebugLog('cron social reaction refresh start');
$pdo = getConnection();
$result = runSocialReactionRefresh($pdo);
$testCleanup = FRONTEND_ID === 'proxy-release' ? cleanupExpiredTestPublications($pdo, FRONTEND_ID) : null;
socialDebugLog('cron social reaction refresh complete', [
    'updated' => $result['updated'],
    'checked_posts' => $result['checked_posts'],
    'errors' => count($result['errors']),
]);

echo json_encode([
    'success' => count($result['errors']) === 0 && (int)($testCleanup['failed'] ?? 0) === 0,
    'message' => 'SNS reactions refreshed.',
    'updated' => $result['updated'],
    'checked_posts' => $result['checked_posts'],
    'recent_days' => $result['recent_days'],
    'errors' => $result['errors'],
    'test_publication_cleanup' => $testCleanup,
], JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(count($result['errors']) === 0 && (int)($testCleanup['failed'] ?? 0) === 0 ? 0 : 1);
