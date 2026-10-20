<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$roomId = trim($_POST['room_id'] ?? '');
$carCode = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '',
    $_POST['car_code'] ?? ''
);

if ($roomId === '') {
    json_response([
        'success' => false,
        'error' => 'Room ID required'
    ], 400);
}


/*
|--------------------------------------------------------------------------
| CREATE CALL
|--------------------------------------------------------------------------
*/

if ($action === 'create') {

    if ($carCode === '') {
        json_response([
            'success' => false,
            'error' => 'Car code required'
        ], 400);
    }

    $stmt = db()->prepare(
        'SELECT id
         FROM cars
         WHERE public_code = ?
         AND active = 1
         LIMIT 1'
    );

    $stmt->execute([$carCode]);

    $car = $stmt->fetch();

    if (!$car) {
        json_response([
            'success' => false,
            'error' => 'Car not found'
        ], 404);
    }

    /*
    | Prevent duplicate session
    */

    $stmt = db()->prepare(
        'SELECT id
         FROM call_sessions
         WHERE room_id = ?
         LIMIT 1'
    );

    $stmt->execute([$roomId]);

    if (!$stmt->fetch()) {

        $stmt = db()->prepare(
            'INSERT INTO call_sessions
             (car_id, room_id, status)
             VALUES (?, ?, "ringing")'
        );

        $stmt->execute([
            $car['id'],
            $roomId
        ]);
    }

    json_response([
        'success' => true
    ]);
}


/*
|--------------------------------------------------------------------------
| CONNECTED
|--------------------------------------------------------------------------
*/

if ($action === 'connected') {

    $stmt = db()->prepare(
        'UPDATE call_sessions
         SET status = "connected",
             started_at = COALESCE(started_at, NOW())
         WHERE room_id = ?'
    );

    $stmt->execute([$roomId]);

    json_response([
        'success' => true
    ]);
}


/*
|--------------------------------------------------------------------------
| REJECTED
|--------------------------------------------------------------------------
*/

if ($action === 'rejected') {

    $stmt = db()->prepare(
        'UPDATE call_sessions
         SET status = "rejected",
             ended_at = COALESCE(ended_at, NOW())
         WHERE room_id = ?'
    );

    $stmt->execute([$roomId]);

    json_response([
        'success' => true
    ]);
}


/*
|--------------------------------------------------------------------------
| ENDED
|--------------------------------------------------------------------------
*/

if ($action === 'ended') {

    $stmt = db()->prepare(
        'UPDATE call_sessions
         SET status = "ended",
             ended_at = COALESCE(ended_at, NOW())
         WHERE room_id = ?'
    );

    $stmt->execute([$roomId]);

    json_response([
        'success' => true
    ]);
}


/*
|--------------------------------------------------------------------------
| INVALID ACTION
|--------------------------------------------------------------------------
*/

json_response([
    'success' => false,
    'error' => 'Invalid action'
], 400);