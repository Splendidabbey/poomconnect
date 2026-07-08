<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$eventId = (int) ($_POST['event_id'] ?? 0);
$roundId = (int) ($_POST['round_id'] ?? 0);
$voterId = (int) ($_POST['voter_id'] ?? ($_SESSION['participant_user_id'] ?? 0));
$targetId = (int) ($_POST['target_id'] ?? 0);
$vote = trim($_POST['vote'] ?? '');

if (!$eventId || !$roundId || !$voterId || !$targetId) {
    json_response(['success' => false, 'message' => 'Invalid vote data.']);
}

process_match_votes($eventId, $roundId, $voterId, $targetId, $vote);
json_response(['success' => true, 'message' => 'Vote saved.']);
