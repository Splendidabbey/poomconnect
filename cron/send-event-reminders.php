#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Send event reminders to registered participants whose events start within the next N hours.
 *
 * Crontab example (hourly):
 *   0 * * * * /Applications/MAMP/bin/php /Applications/MAMP/htdocs/poomconnect/cron/send-event-reminders.php
 *
 * Optional hours-ahead window (default 24):
 *   php cron/send-event-reminders.php 24
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__) . '/config/app.php';

$hoursAhead = isset($argv[1]) ? (int) $argv[1] : 24;
$result = send_due_event_reminders($hoursAhead);

echo sprintf(
    "[%s] Event reminders: %d sent across %d events (window: %d hours)\n",
    date('Y-m-d H:i:s'),
    $result['sent'],
    $result['events'],
    $result['hours_ahead']
);

foreach ($result['errors'] as $error) {
    fwrite(STDERR, "  error: {$error}\n");
}

exit($result['errors'] ? 1 : 0);
