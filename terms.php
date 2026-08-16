<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = __('legal.terms_title');
$pageMeta = page_meta([
    'title' => __('legal.terms_title'),
    'description' => __('legal.terms_description'),
    'url' => base_url('terms.php'),
    'image' => seo_share_image(['type' => 'page']),
]);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="page-header">
    <div class="container">
        <h1><?php _e('legal.terms_title'); ?></h1>
        <p><?php _e('legal.updated'); ?></p>
    </div>
</section>

<section class="section content-section">
    <div class="container legal-prose card">
        <p><?php _e('legal.terms_intro'); ?></p>
        <h2><?php _e('legal.terms_use_title'); ?></h2>
        <p><?php _e('legal.terms_use_body'); ?></p>
        <h2><?php _e('legal.terms_events_title'); ?></h2>
        <p><?php _e('legal.terms_events_body'); ?></p>
        <h2><?php _e('legal.terms_hosts_title'); ?></h2>
        <p><?php _e('legal.terms_hosts_body'); ?></p>
        <h2><?php _e('legal.terms_contact_title'); ?></h2>
        <p><?php _e('legal.terms_contact_body'); ?></p>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
