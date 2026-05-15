<?php

declare(strict_types=1);

require_once __DIR__ . '/api.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "This cron script must be run from the server cron command.\n";
    exit(1);
}

$result = runSocialReactionRefresh(getConnection(), 7);

echo json_encode([
    'success' => true,
    'message' => 'SNS reactions refreshed.',
    'updated' => $result['updated'],
    'checked_posts' => $result['checked_posts'],
    'recent_days' => $result['recent_days'],
    'errors' => $result['errors'],
], JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(count($result['errors']) === 0 ? 0 : 1);
