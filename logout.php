<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

logout_user();
set_flash('success', __('auth.logged_out'));
redirect(base_url('index.php'));
