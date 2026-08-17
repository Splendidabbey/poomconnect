<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
mobile_bootstrap();

$user = mobile_require_auth();
$userId = (int) $user['id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = mobile_read_json_body();

if ($method === 'GET') {
    $roomId = (int) ($_GET['room_id'] ?? 0);
    if ($roomId <= 0) {
        $rooms = get_user_chat_rooms($userId);
        mobile_json_response([
            'success' => true,
            'conversations' => array_map(
                static fn(array $room): array => mobile_conversation_payload($room, $userId),
                $rooms
            ),
        ]);
    }

    $room = get_chat_room($roomId, $userId);
    if (!$room) {
        mobile_json_response(['success' => false, 'message' => 'Chat unlocks after a mutual match.'], 403);
    }

    $messages = get_chat_messages($roomId);
    mobile_json_response([
        'success' => true,
        'messages' => array_map(
            static fn(array $row): array => mobile_message_payload($row, $userId),
            $messages
        ),
    ]);
}

if ($method === 'POST') {
    $roomId = (int) ($body['room_id'] ?? 0);
    $text = trim((string) ($body['body'] ?? ''));
    if ($roomId <= 0 || $text === '') {
        mobile_json_response(['success' => false, 'message' => 'Message body is required'], 422);
    }

    if (!send_chat_message($roomId, $userId, $text)) {
        mobile_json_response(['success' => false, 'message' => 'Chat unlocks after a mutual match.'], 403);
    }

    $latestStmt = db()->prepare(
        'SELECT m.*, u.full_name AS sender_name
         FROM chat_messages m JOIN users u ON u.id = m.sender_id
         WHERE m.room_id = ? ORDER BY m.id DESC LIMIT 1'
    );
    $latestStmt->execute([$roomId]);
    $row = $latestStmt->fetch() ?: [
        'id' => 0,
        'room_id' => $roomId,
        'sender_id' => $userId,
        'body' => $text,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    mobile_json_response([
        'success' => true,
        'message' => mobile_message_payload($row, $userId),
    ], 201);
}

mobile_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
