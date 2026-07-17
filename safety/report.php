<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_login(['participant', 'organizer']);

$userId = (int) current_user()['id'];
$pageTitle = __('safety.report_title');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportedId = (int) ($_POST['reported_id'] ?? 0);
    $reason = $_POST['reason'] ?? 'other';
    $details = trim($_POST['details'] ?? '');
    if ($reportedId && in_array($reason, safety_report_reasons(), true)) {
        report_user($userId, $reportedId, $reason, $details, (int) ($_POST['event_id'] ?? 0) ?: null);
        set_flash('success', __('safety.report_sent'));
        redirect(base_url('profile.php?tab=safety'));
    }
}

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header"><div class="container"><h1><?php _e('safety.report_title'); ?></h1></div></section>
<section class="section content-section"><div class="container" style="max-width:560px;">
    <form method="post" class="card">
        <div class="form-group"><label><?php _e('safety.reported_user_id'); ?></label><input class="input" name="reported_id" type="number" required placeholder="User ID"></div>
        <div class="form-group"><label><?php _e('safety.reason'); ?></label>
            <select name="reason" class="select"><?php foreach (safety_report_reasons() as $r): ?><option value="<?= e($r) ?>"><?= e(__('safety.reason_' . $r)) ?></option><?php endforeach; ?></select>
        </div>
        <div class="form-group"><label><?php _e('safety.details'); ?></label><textarea class="textarea" name="details" rows="4"></textarea></div>
        <div class="form-group"><label><?php _e('safety.event_id_optional'); ?></label><input class="input" name="event_id" type="number"></div>
        <button type="submit" class="btn btn-primary"><?php _e('safety.submit_report'); ?></button>
    </form>
    <p class="form-help" style="margin-top:1rem;"><?php _e('ai_policy.safety_note'); ?></p>
</div></section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
