<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_organizer();

$user = current_user();
$org = get_organization_for_user((int) $user['id']);

$pageTitle = 'Settings';
$bodyClass = 'dashboard-page';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $promptpay = trim($_POST['promptpay_number'] ?? '');
    $bankName = trim($_POST['bank_name'] ?? '');
    $bankAccountName = trim($_POST['bank_account_name'] ?? '');
    $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
    $primaryColor = trim($_POST['primary_color'] ?? '#6C35FF');
    $logoPath = $org ? ($org['logo'] ?? null) : null;

    if ($name === '') {
        $errors[] = 'Organization name is required.';
    }

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newLogo = save_upload($_FILES['logo'], 'logos', 'logo');
        if ($newLogo) {
            $logoPath = $newLogo;
        } else {
            $errors[] = 'Invalid logo file.';
        }
    }

    if ($errors === []) {
        if ($org) {
            $stmt = db()->prepare(
                'UPDATE organizations SET name=?, logo=?, primary_color=?, promptpay_number=?, bank_name=?, bank_account_name=?, bank_account_number=? WHERE id=?'
            );
            $stmt->execute([$name, $logoPath, $primaryColor, $promptpay, $bankName, $bankAccountName, $bankAccountNumber, $org['id']]);
        } else {
            $slug = slugify($name) . '-' . time();
            $stmt = db()->prepare(
                'INSERT INTO organizations (name, slug, logo, primary_color, promptpay_number, bank_name, bank_account_name, bank_account_number, owner_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $slug, $logoPath, $primaryColor, $promptpay, $bankName, $bankAccountName, $bankAccountNumber, $user['id']]);
        }

        set_flash('success', 'Settings saved successfully!');
        redirect(base_url('organizer/settings.php'));
    }
}

$org = get_organization_for_user((int) $user['id']);

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<div class="dashboard-layout">
    <?php require APP_ROOT . '/includes/organizer-sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Settings</h1>
                <p>Manage your organization and payment details</p>
            </div>
        </div>

        <div class="card" style="max-width:640px;">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endforeach; ?>

            <form method="post" enctype="multipart/form-data" data-loading>
                <div class="form-group">
                    <label for="name">Organization Name *</label>
                    <input type="text" id="name" name="name" class="input" required value="<?= e($org['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="logo">Logo</label>
                    <?php if (!empty($org['logo'])): ?>
                        <img src="<?= e(upload_url($org['logo'])) ?>" alt="Logo" style="max-width:80px;margin-bottom:0.5rem;border-radius:8px;">
                    <?php endif; ?>
                    <input type="file" id="logo" name="logo" class="input" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div class="form-group">
                    <label for="primary_color">Primary Color</label>
                    <input type="color" id="primary_color" name="primary_color" value="<?= e($org['primary_color'] ?? '#6C35FF') ?>">
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">

                <h3 style="margin-bottom:1rem;">Payment Details</h3>

                <div class="form-group">
                    <label for="promptpay_number">PromptPay Number</label>
                    <input type="text" id="promptpay_number" name="promptpay_number" class="input" value="<?= e($org['promptpay_number'] ?? '') ?>" placeholder="e.g. 0812345678">
                </div>

                <div class="form-group">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" id="bank_name" name="bank_name" class="input" value="<?= e($org['bank_name'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bank_account_name">Account Name</label>
                        <input type="text" id="bank_account_name" name="bank_account_name" class="input" value="<?= e($org['bank_account_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bank_account_number">Account Number</label>
                        <input type="text" id="bank_account_number" name="bank_account_number" class="input" value="<?= e($org['bank_account_number'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
