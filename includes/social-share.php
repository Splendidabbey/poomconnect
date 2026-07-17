<?php

declare(strict_types=1);

/**
 * @param array{url:string,title:string,text?:string,entity_type:string,entity_id:int} $share
 */
function render_social_share(array $share): string
{
    $url = $share['url'];
    $title = $share['title'];
    $text = $share['text'] ?? $title;
    $entityType = $share['entity_type'];
    $entityId = (int) $share['entity_id'];

    ob_start();
    ?>
    <div class="social-share" data-entity-type="<?= e($entityType) ?>" data-entity-id="<?= $entityId ?>">
        <span class="social-share-label"><?php _e('marketing.share_via'); ?></span>
        <div class="social-share-buttons">
            <?php foreach (social_share_channels() as $channel): ?>
                <?php if ($channel === 'copy'): ?>
                    <button type="button" class="social-share-btn" data-share-channel="copy" data-share-url="<?= e($url) ?>" title="<?= e(__('marketing.copy_link')) ?>">🔗</button>
                <?php else: ?>
                    <a href="<?= e(social_share_url($channel, $url, $title, $text)) ?>"
                       class="social-share-btn social-share-<?= e($channel) ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       data-share-channel="<?= e($channel) ?>"
                       title="<?= e(__('marketing.channel_' . $channel)) ?>"><?= social_share_icon($channel) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    (function () {
        const root = document.currentScript?.previousElementSibling;
        if (!root) return;
        const entityType = root.dataset.entityType;
        const entityId = root.dataset.entityId;
        root.querySelectorAll('[data-share-channel]').forEach(function (el) {
            el.addEventListener('click', function () {
                const channel = this.dataset.shareChannel;
                if (channel === 'copy') {
                    navigator.clipboard?.writeText(this.dataset.shareUrl || '');
                }
                fetch(<?= json_encode(base_url('api/log-share.php')) ?>, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({entity_type: entityType, entity_id: entityId, channel: channel})
                }).catch(function () {});
            });
        });
    })();
    </script>
    <?php
    return (string) ob_get_clean();
}

function social_share_icon(string $channel): string
{
    return match ($channel) {
        'facebook' => 'f',
        'x' => '𝕏',
        'line' => 'LINE',
        'tiktok' => '♪',
        'whatsapp' => 'WA',
        default => '↗',
    };
}
