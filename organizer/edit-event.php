<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$eventId = (int) ($_GET['id'] ?? 0);
$event = get_event_by_id($eventId);

if (!$event || !user_can_manage_event((int) $user['id'], $event)) {
    set_flash('error', 'Event not found.');
    redirect(base_url('organizer/events.php'));
}

$pageTitle = 'Edit Event';
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
    $coverPath = $event['cover_image'];

    if ($title === '') {
        $errors[] = 'Event title is required.';
    }

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newCover = save_upload($_FILES['cover_image'], 'events', 'event');
        if ($newCover) {
            $coverPath = $newCover;
        } else {
            $errors[] = 'Invalid cover image.';
        }
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'UPDATE events SET title=?, description=?, cover_image=?, location=?, event_date=?, start_time=?, end_time=?, max_participants=?, ticket_price=?, round_duration=?, status=? WHERE id=?'
        );
        $stmt->execute([
            $title, $description, $coverPath, $location, $eventDate, $startTime, $endTime,
            max(1, $maxParticipants), $ticketPrice, max(60, $roundDuration),
            in_array($status, ['draft', 'published', 'live', 'paused', 'completed', 'cancelled'], true) ? $status : $event['status'],
            $eventId,
        ]);

        set_flash('success', 'Event updated successfully!');
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
                <h1>Edit Event</h1>
                <p><?= e($event['title']) ?></p>
            </div>
        </div>

        <div class="card" style="max-width:720px;">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endforeach; ?>

            <form method="post" enctype="multipart/form-data" data-loading>
                <div class="form-group">
                    <label for="title">Event Title *</label>
                    <input type="text" id="title" name="title" class="input" required value="<?= e($event['title']) ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="textarea"><?= e($event['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="cover_image">Cover Image</label>
                    <?php if ($event['cover_image']): ?>
                        <img src="<?= e(upload_url($event['cover_image'])) ?>" alt="Cover" style="max-width:200px;border-radius:8px;margin-bottom:0.5rem;">
                    <?php endif; ?>
                    <input type="file" id="cover_image" name="cover_image" class="input" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="input" value="<?= e($event['location'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Date</label>
                        <input type="date" id="event_date" name="event_date" class="input" value="<?= e($event['event_date']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="select">
                            <?php foreach (['draft', 'published', 'live', 'paused', 'completed', 'cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $event['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="input" value="<?= e(substr($event['start_time'], 0, 5)) ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="input" value="<?= e(substr($event['end_time'], 0, 5)) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_participants">Max Participants</label>
                        <input type="number" id="max_participants" name="max_participants" class="input" value="<?= (int) $event['max_participants'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="ticket_price">Ticket Price (THB)</label>
                        <input type="number" id="ticket_price" name="ticket_price" class="input" value="<?= (float) $event['ticket_price'] ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="round_duration">Round Duration (seconds)</label>
                    <input type="number" id="round_duration" name="round_duration" class="input" value="<?= (int) $event['round_duration'] ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
