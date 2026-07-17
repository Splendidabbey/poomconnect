<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_login(['participant', 'organizer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('index.php'));
}

$userId = (int) current_user()['id'];
$orgId = (int) ($_POST['org_id'] ?? 0);
$action = $_POST['action'] ?? 'follow';

if (!$orgId) {
    redirect(base_url('index.php'));
}

if ($action === 'unfollow') {
    unfollow_organizer($orgId, $userId);
} else {
    follow_organizer($orgId, $userId);
}

redirect($_SERVER['HTTP_REFERER'] ?? base_url('org/profile.php?org=' . $orgId));
