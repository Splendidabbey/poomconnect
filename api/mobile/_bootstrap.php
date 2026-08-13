<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

mobile_handle_options();

function mobile_bootstrap(): void
{
    ensure_mobile_api_schema();
}
