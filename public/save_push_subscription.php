<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['owner_id'])) {
    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'message' => 'Owner login required'
    ]);

    exit;
}

$ownerId = (int) $_SESSION['owner_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'message' => 'POST required'
    ]);

    exit;
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'message' => 'Invalid JSON'
    ]);

    exit;
}

$endpoint = trim($input['endpoint'] ?? '');

$p256dh = trim(
    $input['keys']['p256dh'] ?? ''
);

$auth = trim(
    $input['keys']['auth'] ?? ''
);

if (
    $endpoint === '' ||
    $p256dh === '' ||
    $auth === ''
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'message' => 'Invalid push subscription'
    ]);

    exit;
}

$endpointHash = hash(
    'sha256',
    $endpoint
);

try {

    $stmt = db()->prepare("
        INSERT INTO push_subscriptions
        (
            user_id,
            endpoint,
            p256dh,
            auth,
            endpoint_hash
        )
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            endpoint = VALUES(endpoint),
            p256dh = VALUES(p256dh),
            auth = VALUES(auth),
            updated_at = CURRENT_TIMESTAMP
    ");

    $stmt->execute([
        $ownerId,
        $endpoint,
        $p256dh,
        $auth,
        $endpointHash
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'Push subscription saved'
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'message' => 'Database error'
    ]);
}