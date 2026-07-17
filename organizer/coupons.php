<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);
if (!$org) {
    redirect(base_url('organizer/dashboard.php'));
}

$pageTitle = __('organizer.coupons_title');
$bodyClass = 'dashboard-page';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!save_coupon((int) $org['id'], $_POST)) {
        $errors[] = __('validation.coupon_exists');
    } else {
        set_flash('success', __('organizer.coupon_created'));
        redirect(base_url('organizer/coupons.php'));
    }
}

$coupons = get_org_coupons((int) $org['id']);
$eventsList = db()->prepare('SELECT id, title FROM events WHERE organization_id = ?');
$eventsList->execute([(int) $org['id']]);
$eventsList = $eventsList->fetchAll();

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header"><h1><?php _e('organizer.coupons_title'); ?></h1></div>
        <div class="card form-card-wide" style="margin-bottom:1.5rem;">
            <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
            <form method="post" class="form-inline-row">
                <div class="form-group"><label><?php _e('organizer.coupon_code'); ?></label><input name="code" class="input" required></div>
                <div class="form-group"><label><?php _e('organizer.discount_type'); ?></label><select name="discount_type" class="select"><option value="percent">%</option><option value="fixed">THB</option></select></div>
                <div class="form-group"><label><?php _e('organizer.discount_value'); ?></label><input name="discount_value" type="number" class="input" required min="1"></div>
                <div class="form-group"><label><?php _e('organizer.event_optional'); ?></label><select name="event_id" class="select"><option value=""><?php _e('organizer.all_events'); ?></option><?php foreach ($eventsList as $ev): ?><option value="<?= (int) $ev['id'] ?>"><?= e($ev['title']) ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="btn btn-primary"><?php _e('organizer.add_coupon'); ?></button>
            </form>
        </div>
        <div class="card">
            <table class="table">
                <thead><tr><th><?php _e('organizer.coupon_code'); ?></th><th><?php _e('organizer.discount'); ?></th><th><?php _e('organizer.event'); ?></th><th><?php _e('organizer.uses'); ?></th></tr></thead>
                <tbody>
                    <?php foreach ($coupons as $c): ?>
                        <tr>
                            <td><code><?= e($c['code']) ?></code></td>
                            <td><?= $c['discount_type'] === 'percent' ? e($c['discount_value']) . '%' : e(format_currency((float) $c['discount_value'])) ?></td>
                            <td><?= e($c['event_title'] ?? __('organizer.all_events')) ?></td>
                            <td><?= (int) $c['used_count'] ?><?= $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
