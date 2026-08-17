<?php

declare(strict_types=1);
?>
<a href="<?= base_url('events.php') ?>" class="<?= in_array($currentPath, ['events.php', 'event.php'], true) ? 'active' : '' ?>"><?php _e('nav.events'); ?></a>
<?php if (is_logged_in()): ?>
    <a href="<?= base_url('my-events.php') ?>" class="<?= $currentPath === 'my-events.php' ? 'active' : '' ?>"><?php _e('nav.my_events'); ?></a>
    <a href="<?= base_url('participant/matches.php') ?>" class="<?= $currentPath === 'matches.php' ? 'active' : '' ?>"><?php _e('nav.matches'); ?></a>
    <a href="<?= base_url('chat.php') ?>" class="<?= in_array($currentPath, ['chat.php', 'chat-thread.php'], true) ? 'active' : '' ?>"><?php _e('nav.chat'); ?></a>
<?php else: ?>
    <a href="<?= base_url('blog.php') ?>" class="<?= in_array($currentPath, ['blog.php', 'article.php'], true) ? 'active' : '' ?>"><?php _e('nav.blog'); ?></a>
<?php endif; ?>
