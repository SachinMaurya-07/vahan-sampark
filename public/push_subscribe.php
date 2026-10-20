<?php
require __DIR__.'/config.php';
require __DIR__.'/auth.php';
require_login('operator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'error'=>'POST required'],405);
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$code = preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['car'] ?? '');
if (!$code || !is_array($data) || empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
    json_response(['ok'=>false,'error'=>'Invalid subscription'],400);
}
$stmt = db()->prepare('SELECT id FROM cars WHERE public_code=? AND active=1 LIMIT 1');
$stmt->execute([$code]);
$car = $stmt->fetch();
if (!$car) json_response(['ok'=>false,'error'=>'Invalid car'],404);

$endpoint = (string)$data['endpoint'];
$hash = hash('sha256', $endpoint);
$json = json_encode($data, JSON_UNESCAPED_SLASHES);
$stmt = db()->prepare('INSERT INTO push_subscriptions(car_id,endpoint,endpoint_hash,subscription_json) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),subscription_json=VALUES(subscription_json),updated_at=CURRENT_TIMESTAMP');
$stmt->execute([$car['id'],$endpoint,$hash,$json]);
json_response(['ok'=>true]);
