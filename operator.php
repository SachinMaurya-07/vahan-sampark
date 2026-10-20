<?php
require __DIR__.'/config.php';

$code = preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['car'] ?? '');
$stmt = db()->prepare('SELECT * FROM cars WHERE public_code = ? AND active = 1 LIMIT 1');
$stmt->execute([$code]);
$car = $stmt->fetch();

if (!$car) {
    http_response_code(404);
    exit('Invalid car code.');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Operator - <?=htmlspecialchars($car['name'])?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
  <div class="badge">OPERATOR</div>
  <h1><?=htmlspecialchars($car['name'])?></h1>
  <section class="card">
    <div id="incomingBanner" class="incoming-banner hidden"><div class="ring-icon">📞</div><div><strong>Incoming private call</strong><span>Someone is calling this vehicle</span></div></div>
    <h2>Call status</h2>
    <p id="status" class="status">Waiting for a visitor…</p>
    <div id="incoming" class="hidden">
      <button id="answerBtn" class="primary">📞 Answer call</button>
      <button id="rejectBtn" class="danger">Reject</button>
    </div>
    <div id="active" class="hidden">
      <p id="timer">00:00</p>
      <button id="hangBtn" class="danger">End call</button>
    </div>
  </section>
</main>
<script>
window.CAR_CONFIG = <?=json_encode([
    'carCode'=>$car['public_code'],
    'carName'=>$car['name'],
    'signalUrl'=>SIGNAL_URL,
    'iceServers'=>ICE_SERVERS,
    'operator'=>true
], JSON_UNESCAPED_SLASHES)?>;
</script>
<script src="https://cdn.socket.io/4.8.1/socket.io.min.js"></script>
<script src="operator.js?v=4"></script>
</body>
</html>
