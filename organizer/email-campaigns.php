<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('marketing.email_campaigns');
$bodyClass = 'dashboard-page';
$orgId = (int) $org['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'send' && !empty($_POST['campaign_id'])) {
        $result = send_email_campaign((int) $_POST['campaign_id'], $orgId);
        if ($result['ok']) {
            set_flash('success', __('marketing.campaign_sent', ['count' => $result['sent']]));
        } else {
            set_flash('error', $result['error'] ?? __('marketing.campaign_not_found'));
        }
    } else {
        create_email_campaign($orgId, (int) $user['id'], [
            'name' => trim($_POST['name'] ?? ''),
            'subject' => trim($_POST['subject'] ?? ''),
            'body_html' => trim($_POST['body_html'] ?? ''),
            'audience' => $_POST['audience'] ?? 'all_participants',
            'event_id' => (int) ($_POST['event_id'] ?? 0),
        ]);
        set_flash('success', __('marketing.campaign_created'));
    }

    redirect(base_url('organizer/email-campaigns.php'));
}

$campaigns = get_org_email_campaigns($orgId);
$eventsList = db()->prepare('SELECT id, title FROM events WHERE organization_id = ? ORDER BY event_date DESC');
$eventsList->execute([$orgId]);
$eventsList = $eventsList->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <h1><?php _e('marketing.email_campaigns'); ?></h1>
            <a href="<?= base_url('organizer/marketing.php') ?>" class="btn btn-outline btn-sm">← <?php _e('marketing.title'); ?></a>
        </div>

        <div class="card form-card-wide" style="margin-bottom:1.5rem;">
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="form-grid-2">
                    <div class="form-group"><label><?php _e('marketing.campaign_name'); ?></label><input name="name" class="input" required></div>
                    <div class="form-group"><label><?php _e('marketing.campaign_subject'); ?></label><input name="subject" class="input" required></div>
                    <div class="form-group">
                        <label><?php _e('marketing.campaign_audience'); ?></label>
                        <select name="audience" class="select">
                            <option value="all_participants"><?php _e('marketing.audience_all'); ?></option>
                            <option value="followers"><?php _e('marketing.audience_followers'); ?></option>
                            <option value="event_attendees"><?php _e('marketing.audience_event'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php _e('organizer.event_optional'); ?></label>
                        <select name="event_id" class="select">
                            <option value=""><?php _e('organizer.all_events'); ?></option>
                            <?php foreach ($eventsList as $ev): ?>
                                <option value="<?= (int) $ev['id'] ?>"><?= e($ev['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-group-full">
                        <label><?php _e('marketing.campaign_body'); ?></label>
                        <textarea name="body_html" class="input" rows="6" required placeholder="<p>Hello!</p>"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?php _e('marketing.create_campaign'); ?></button>
            </form>
        </div>

        <div class="card">
            <?php if ($campaigns): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php _e('marketing.campaign_name'); ?></th>
                            <th><?php _e('marketing.campaign_subject'); ?></th>
                            <th><?php _e('marketing.campaign_audience'); ?></th>
                            <th><?php _e('common.status'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $c): ?>
                            <tr>
                                <td><?= e($c['name']) ?></td>
                                <td><?= e($c['subject']) ?></td>
                                <td><?= e($c['audience']) ?></td>
                                <td><?= e($c['status'] === 'sent' ? __('marketing.status_sent') : __('marketing.status_draft')) ?></td>
                                <td>
                                    <?php if ($c['status'] !== 'sent'): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="send">
                                            <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm(<?= json_encode(__('marketing.send_campaign') . '?') ?>)"><?php _e('marketing.send_campaign'); ?></button>
                                        </form>
                                    <?php else: ?>
                                        <?= (int) $c['sent_count'] ?> sent
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('marketing.no_campaigns'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
