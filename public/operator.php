<?php
require __DIR__.'/config.php';

$code = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '',
    $_GET['car'] ?? ''
);

$stmt = db()->prepare(
    'SELECT * FROM cars
     WHERE public_code = ?
     AND active = 1
     LIMIT 1'
);

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
    <link rel="manifest" href="manifest.json">

<meta
    name="theme-color"
    content="#ffd400"
>

<script src="pwa.js" defer></script>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
    Operator - <?=htmlspecialchars($car['name'])?>
</title>

<link
    rel="stylesheet"
    href="style.css"
>

<style>

.sound-btn{
    width:100%;
    border:0;
    border-radius:14px;
    padding:15px 18px;
    margin-bottom:20px;
    background:#111827;
    color:#fff;
    font-size:16px;
    font-weight:800;
    cursor:pointer;
    transition:.2s;
}

.sound-btn:hover{
    transform:translateY(-1px);
}

.sound-btn:active{
    transform:scale(.98);
}

.sound-enabled{
    background:#047857 !important;
}

.incoming-banner{
    animation:
        operatorRing 1s infinite;
}

@keyframes operatorRing{

    0%{
        transform:scale(1);
    }

    50%{
        transform:scale(1.02);
    }

    100%{
        transform:scale(1);
    }
}

#timer{
    font-size:42px;
    font-weight:900;
    text-align:center;
    letter-spacing:2px;
}

</style>

</head>

<body>

<main class="shell">

    <div class="badge">
        OPERATOR
    </div>

    <h1>
        <?=htmlspecialchars($car['name'])?>
    </h1>

    <?php if (!empty($car['description'])): ?>

        <p class="muted">
            <?=htmlspecialchars($car['description'])?>
        </p>

    <?php endif; ?>


    <section class="card">

        <!-- SOUND ENABLE -->

        <button
            id="enableSoundBtn"
            class="sound-btn"
        >
            🔊 Enable Loud Ringtone
        </button>


        <!-- INCOMING CALL -->

        <div
            id="incomingBanner"
            class="incoming-banner hidden"
        >

            <div class="ring-icon">
                📞
            </div>

            <div>

                <strong>
                    Incoming private call
                </strong>

                <span>
                    Someone is calling this vehicle
                </span>

            </div>

        </div>


        <!-- STATUS -->

        <h2>
            Call status
        </h2>

        <p
            id="status"
            class="status"
        >
            Connecting…
        </p>


        <!-- INCOMING BUTTONS -->

        <div
            id="incoming"
            class="hidden"
        >

            <button
                id="answerBtn"
                class="primary"
            >
                📞 Answer Call
            </button>

            <button
                id="rejectBtn"
                class="danger"
            >
                ❌ Reject Call
            </button>

        </div>


        <!-- ACTIVE CALL -->

        <div
            id="active"
            class="hidden"
        >

            <p id="timer">
                00:00
            </p>

            <button
                id="hangBtn"
                class="danger"
            >
                🔴 End Call
            </button>

        </div>


        <!-- DIAGNOSTICS -->

        <div
            id="diagnostics"
            class="diagnostics"
        >

            <h3>
                Connection Diagnostics
            </h3>

        </div>

    </section>

</main>


<script>

window.CAR_CONFIG =
<?=json_encode(
    [
        'carCode' =>
            $car['public_code'],

        'carName' =>
            $car['name'],

        'signalUrl' =>
            SIGNAL_URL,

        'iceServers' =>
            ICE_SERVERS,

        'operator' =>
            true
    ],
    JSON_UNESCAPED_SLASHES
)?>;

</script>


<script
    src="https://cdn.socket.io/4.8.1/socket.io.min.js"
></script>


<script
    src="operator.js?v=10"
></script>

</body>

</html>