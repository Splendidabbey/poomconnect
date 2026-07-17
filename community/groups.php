<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_login(['participant', 'organizer']);

$userId = (int) current_user()['id'];
$pageTitle = __('community.groups');
$groups = user_communities($userId);
$communityId = (int) ($_GET['id'] ?? 0);

if ($communityId) {
    redirect(base_url('community/group.php?id=' . $communityId));
}

require_once APP_ROOT . '/includes/header.php';
?>

<section class="page-header"><div class="container"><h1><?php _e('community.groups'); ?></h1></div></section>
<section class="section content-section"><div class="container">
    <?php if ($groups): ?>
        <div class="community-grid">
            <?php foreach ($groups as $g): ?>
                <a href="<?= base_url('community/group.php?id=' . (int) $g['id']) ?>" class="card">
                    <strong><?= e($g['name']) ?></strong>
                    <span><?= e($g['org_name']) ?> · <?= (int) $g['member_count'] ?> <?php _e('community.members'); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state card"><p><?php _e('community.no_groups'); ?></p></div>
    <?php endif; ?>
</div></section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
