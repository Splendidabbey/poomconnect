<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_login(['participant', 'organizer']);

$userId = (int) current_user()['id'];
$id = (int) ($_GET['id'] ?? 0);
$community = get_community_by_id($id);

if (!$community) {
    set_flash('error', __('community.not_found'));
    redirect(base_url('community/groups.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['join'])) {
        join_community($id, $userId);
    }
    if (isset($_POST['leave'])) {
        leave_community($id, $userId);
    }
    redirect(base_url('community/group.php?id=' . $id));
}

$member = is_community_member($id, $userId);
$pageTitle = $community['name'];

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header"><div class="container"><h1><?= e($community['name']) ?></h1><p><?= e($community['org_name']) ?></p></div></section>
<section class="section content-section"><div class="container">
    <div class="card">
        <?php if (!empty($community['description'])): ?><p><?= nl2br(e($community['description'])) ?></p><?php endif; ?>
        <p><?= (int) $community['member_count'] ?> <?php _e('community.members'); ?></p>
        <form method="post">
            <?php if ($member): ?>
                <button name="leave" class="btn btn-outline"><?php _e('community.leave'); ?></button>
            <?php else: ?>
                <button name="join" class="btn btn-primary"><?php _e('community.join'); ?></button>
            <?php endif; ?>
        </form>
    </div>
    <a href="<?= base_url('community/groups.php') ?>" class="btn btn-outline" style="margin-top:1rem;">← <?php _e('community.back'); ?></a>
</div></section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
