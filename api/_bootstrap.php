<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function require_api_organizer(): void
{
    if (!is_logged_in() || !is_organizer()) {
        json_response(['success' => false, 'message' => __('api.unauthorized')], 401);
    }
}

function get_payment_for_organizer(int $paymentId): ?array
{
    $user = current_user();
    $org = get_organization_for_user((int) $user['id']);

    if (!$org && !is_admin()) {
        return null;
    }

    $sql = 'SELECT p.*, e.organization_id FROM payments p JOIN events e ON e.id = p.event_id WHERE p.id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        return null;
    }

    if (!is_admin() && (int) $payment['organization_id'] !== (int) $org['id']) {
        return null;
    }

    return $payment;
}

function get_event_for_organizer(int $eventId): ?array
{
    $event = get_event_by_id($eventId);
    $user = current_user();

    if (!$event || !user_can_manage_event((int) $user['id'], $event)) {
        return null;
    }

    return $event;
}
