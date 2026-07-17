<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$eventId = (int) ($_GET['event_id'] ?? 0);
$event = $eventId ? get_event_by_id($eventId) : null;

if ($event && !user_can_manage_event((int) $user['id'], $event)) {
    $event = null;
}

$pageTitle = __('organizer.participants_title');
$bodyClass = 'dashboard-page';

$org = get_organization_for_user((int) $user['id']);
$orgId = $org ? (int) $org['id'] : 0;

$eventsList = [];
if ($orgId) {
    $stmt = db()->prepare('SELECT id, title FROM events WHERE organization_id = ? ORDER BY event_date DESC');
    $stmt->execute([$orgId]);
    $eventsList = $stmt->fetchAll();
}

$participants = [];
if ($event) {
    $stmt = db()->prepare(
        'SELECT ep.*, u.full_name, u.email, u.phone, t.qr_token, t.checked_in AS ticket_checked_in
         FROM event_participants ep
         JOIN users u ON u.id = ep.user_id
         LEFT JOIN tickets t ON t.event_id = ep.event_id AND t.user_id = ep.user_id
         WHERE ep.event_id = ?
         ORDER BY ep.created_at DESC'
    );
    $stmt->execute([$eventId]);
    $participants = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    if (isset($_POST['manual_checkin_user'])) {
        manual_checkin_participant($eventId, (int) $_POST['manual_checkin_user']);
        set_flash('success', __('organizer.manual_checkin_done'));
        redirect(base_url('organizer/participants.php?event_id=' . $eventId));
    }
    if (isset($_POST['reject_ticket_id'])) {
        reject_ticket_checkin((int) $_POST['reject_ticket_id'], (int) $user['id']);
        set_flash('success', __('organizer.checkin_rejected'));
        redirect(base_url('organizer/participants.php?event_id=' . $eventId));
    }
    $qrToken = trim($_POST['qr_token'] ?? '');
    if ($qrToken !== '') {
        $result = checkin_ticket($qrToken);
        if ($result['success']) {
            set_flash('success', $result['message']);
        } elseif (!empty($result['warning'])) {
            set_flash('warning', $result['message']);
        } else {
            set_flash('error', $result['message']);
        }
        redirect(base_url('organizer/participants.php?event_id=' . $eventId));
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
                <h1><?php _e('organizer.participants_title'); ?></h1>
                <p><?php _e('organizer.scan_qr'); ?></p>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <form method="get" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:end;">
                <div class="form-group" style="margin:0;flex:1;min-width:200px;">
                    <label for="event_id"><?php _e('organizer.select_event'); ?></label>
                    <select id="event_id" name="event_id" class="select" onchange="this.form.submit()">
                        <option value=""><?php _e('organizer.select_event'); ?></option>
                        <?php foreach ($eventsList as $ev): ?>
                            <option value="<?= (int) $ev['id'] ?>" <?= $eventId === (int) $ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($event): ?>
            <div class="card" style="margin-bottom:1.5rem;">
                <h3 style="margin-bottom:1rem;"><?php _e('organizer.check_in'); ?></h3>
                <form method="post" class="form-row">
                    <div class="form-group" style="margin:0;">
                        <label for="qr_token"><?php _e('organizer.scan_qr'); ?></label>
                        <input type="text" id="qr_token" name="qr_token" class="input" placeholder="<?= e(__('organizer.qr_placeholder')) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self:end;"><?php _e('organizer.check_in'); ?></button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-bottom:1rem;"><?= e(__('organizer.participants_for', ['title' => $event['title']])) ?> — <?= count($participants) ?></h3>
                <?php if ($participants): ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php _e('organizer.name'); ?></th>
                                    <th><?php _e('organizer.email'); ?></th>
                                    <th><?php _e('organizer.payment'); ?></th>
                                    <th><?php _e('organizer.ticket'); ?></th>
                                    <th><?php _e('ticket_page.status'); ?></th>
                                    <th><?php _e('common.actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $p): ?>
                                    <tr>
                                        <td><?= e($p['full_name']) ?></td>
                                        <td><?= e($p['email']) ?></td>
                                        <td><span class="badge badge-<?= $p['payment_status'] === 'approved' ? 'success' : ($p['payment_status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= e(status_label($p['payment_status'])) ?></span></td>
                                        <td><?= $p['qr_token'] ? '<span class="badge badge-purple">' . e(__('status.issued')) . '</span>' : '—' ?></td>
                                        <td><?= (int) $p['checked_in'] ? '<span class="badge badge-success">' . e(__('ticket_page.checked_in')) . '</span>' : '<span class="badge badge-purple">' . e(__('ticket_page.not_checked_in')) . '</span>' ?></td>
                                        <td class="table-actions">
                                            <?php if ((int) !$p['checked_in'] && $p['payment_status'] === 'approved'): ?>
                                                <form method="post"><input type="hidden" name="manual_checkin_user" value="<?= (int) $p['user_id'] ?>"><button type="submit" class="btn btn-outline btn-sm"><?php _e('organizer.manual_checkin'); ?></button></form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><p><?php _e('organizer.no_participants'); ?></p></div>
                <?php endif; ?>
            </div>
        <?php elseif ($eventId): ?>
            <div class="alert alert-error"><?php _e('flash.event_not_found'); ?></div>
        <?php else: ?>
            <div class="empty-state card"><p><?php _e('organizer.select_event'); ?></p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
