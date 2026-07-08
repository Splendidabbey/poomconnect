<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);

if (!$org && !is_admin()) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = 'Create Event';
$bodyClass = 'dashboard-page';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $maxParticipants = (int) ($_POST['max_participants'] ?? 50);
    $ticketPrice = (float) ($_POST['ticket_price'] ?? 0);
    $roundDuration = (int) ($_POST['round_duration'] ?? 300);
    $status = $_POST['status'] ?? 'draft';

    if ($title === '') {
        $errors[] = 'Event title is required.';
    }
    if ($eventDate === '') {
        $errors[] = 'Event date is required.';
    }
    if (!$org && !is_admin()) {
        $errors[] = 'Organization required.';
    }

    $coverPath = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $coverPath = save_upload($_FILES['cover_image'], 'events', 'event');
        if (!$coverPath) {
            $errors[] = 'Invalid cover image. Use JPG, PNG, or WEBP under 5MB.';
        }
    }

    if ($errors === [] && $org) {
        $stmt = db()->prepare(
            'INSERT INTO events (organization_id, title, description, cover_image, location, event_date, start_time, end_time, max_participants, ticket_price, round_duration, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $org['id'],
            $title,
            $description,
            $coverPath,
            $location,
            $eventDate,
            $startTime,
            $endTime,
            max(1, $maxParticipants),
            $ticketPrice,
            max(60, $roundDuration),
            in_array($status, ['draft', 'published'], true) ? $status : 'draft',
            $user['id'],
        ]);

        $eventId = (int) db()->lastInsertId();
        ensure_live_state($eventId, max(60, $roundDuration));

        set_flash('success', 'Event created successfully!');
        redirect(base_url('organizer/events.php'));
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Create Event</h1>
                <p>Set up a new matching event</p>
            </div>
        </div>

        <div class="card" style="max-width:720px;">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endforeach; ?>

            <?php if (!$org && !is_admin()): ?>
                <div class="alert alert-error">Please set up your organization in Settings first.</div>
            <?php else: ?>
                <form method="post" enctype="multipart/form-data" data-loading>
                    <div class="form-group">
                        <label for="title">Event Title *</label>
                        <input type="text" id="title" name="title" class="input" required value="<?= e($_POST['title'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="textarea"><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="cover_image">Cover Image</label>
                        <input type="file" id="cover_image" name="cover_image" class="input" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="input" value="<?= e($_POST['location'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Date *</label>
                            <input type="date" id="event_date" name="event_date" class="input" required value="<?= e($_POST['event_date'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="select">
                                <option value="draft">Draft</option>
                                <option value="published" selected>Published</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" class="input" value="<?= e($_POST['start_time'] ?? '18:00') ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" class="input" value="<?= e($_POST['end_time'] ?? '22:00') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_participants">Max Participants</label>
                            <input type="number" id="max_participants" name="max_participants" class="input" min="2" value="<?= e($_POST['max_participants'] ?? '50') ?>">
                        </div>
                        <div class="form-group">
                            <label for="ticket_price">Ticket Price (THB)</label>
                            <input type="number" id="ticket_price" name="ticket_price" class="input" min="0" step="1" value="<?= e($_POST['ticket_price'] ?? '990') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="round_duration">Round Duration (seconds)</label>
                        <input type="number" id="round_duration" name="round_duration" class="input" min="60" value="<?= e($_POST['round_duration'] ?? '300') ?>">
                        <p class="form-help">Default: 300 seconds (5 minutes per round)</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">Create Event</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
