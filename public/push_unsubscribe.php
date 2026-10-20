<?php
require __DIR__.'/config.php';
require __DIR__.'/auth.php';
require_login('operator');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false],405);
$data=json_decode(file_get_contents('php://input'),true);
$endpoint=$data['endpoint']??'';
if(!$endpoint) json_response(['ok'=>false],400);
$stmt=db()->prepare('DELETE FROM push_subscriptions WHERE endpoint_hash=?');
$stmt->execute([hash('sha256',$endpoint)]);
json_response(['ok'=>true]);
