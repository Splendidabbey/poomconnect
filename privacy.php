<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = __('legal.privacy_title');
$pageMeta = page_meta([
    'title' => __('legal.privacy_title'),
    'description' => __('legal.privacy_description'),
    'url' => base_url('privacy.php'),
    'image' => seo_share_image(['type' => 'page']),
]);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <h1><?php _e('legal.privacy_title'); ?></h1>
        <p><?php _e('legal.updated'); ?></p>
    </div>
</section>

<section class="section content-section">
    <div class="container legal-prose card">
        <p><?php _e('legal.privacy_intro'); ?></p>
        <h2><?php _e('legal.privacy_accounts_title'); ?></h2>
        <p><?php _e('legal.privacy_accounts_body'); ?></p>
        <h2><?php _e('legal.privacy_payments_title'); ?></h2>
        <p><?php _e('legal.privacy_payments_body'); ?></p>
        <h2><?php _e('legal.privacy_cookies_title'); ?></h2>
        <p><?php _e('legal.privacy_cookies_body'); ?></p>
        <h2><?php _e('legal.privacy_contact_title'); ?></h2>
        <p><?php _e('legal.privacy_contact_body'); ?></p>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
