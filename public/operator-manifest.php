<?php
require __DIR__.'/config.php';
$code=preg_replace('/[^A-Za-z0-9_-]/','',$_GET['car']??'');
header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
  'name'=>'Operator Call - '.($code ?: 'Vehicle'),
  'short_name'=>'Operator Call',
  'start_url'=>'operator.php?car='.urlencode($code),
  'display'=>'standalone',
  'background_color'=>'#0f172a',
  'theme_color'=>'#0f172a',
  'description'=>'Private QR vehicle call operator app'
], JSON_UNESCAPED_SLASHES);
