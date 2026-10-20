<?php

if (!isset($_GET['car']) || trim($_GET['car']) === '') {
    header('Location: owner_login.php');
    exit;
}

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
?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>Car Not Found</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<main class="shell">

    <section class="card error-card">

        <div class="error-icon">
            🚗
        </div>

        <h1>
            Car not found
        </h1>

        <p class="muted">
            This QR code is invalid or the vehicle
            is currently inactive.
        </p>

    </section>

</main>

</body>

</html>

<?php
exit;
}
?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1,maximum-scale=1"
>

<meta
    name="theme-color"
    content="#111827"
>

<meta
    name="mobile-web-app-capable"
    content="yes"
>

<meta
    name="apple-mobile-web-app-capable"
    content="yes"
>

<meta
    name="apple-mobile-web-app-status-bar-style"
    content="default"
>

<title>
    Contact <?=htmlspecialchars($car['name'])?>
</title>

<link
    rel="stylesheet"
    href="style.css"
>

<style>
.visitor-account-option {
    margin-top: 22px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
}

.visitor-account-option p {
    margin: 0 0 10px;
    color: #475569;
    font-size: 14px;
    font-weight: 600;
}

.create-account-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 50px;
    padding: 12px 16px;
    box-sizing: border-box;
    border: 2px solid #ffd400;
    border-radius: 13px;
    background: #ffd400;
    color: #111827;
    font-size: 15px;
    font-weight: 800;
    text-decoration: none;
    transition: transform 0.2s, background 0.2s;
}

.create-account-btn:hover {
    background: #f5c900;
    transform: translateY(-1px);
}

.visitor-account-option small {
    display: block;
    margin-top: 10px;
    color: #64748b;
    font-size: 12px;
    line-height: 1.5;
}
/* =====================================================
   VISITOR MOBILE PAGE
   ===================================================== */

.visitor-page{
    width:100%;
}

/* Top branding */

.visitor-header{
    text-align:center;
    padding-top:5px;
}

.visitor-logo{
    width:76px;
    height:76px;

    margin:0 auto 14px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:22px;

    background:#111827;

    font-size:38px;

    box-shadow:
        0 10px 25px
        rgba(15,23,42,.12);
}

.visitor-title{
    margin:0;

    font-size:30px;
    line-height:1.1;
}

.visitor-subtitle{
    margin-top:9px;

    color:#64748b;

    font-size:14px;
    line-height:1.5;
}


/* Vehicle card */

.vehicle-card{
    text-align:center;
}


/* Vehicle icon */

.vehicle-icon{
    width:92px;
    height:92px;

    margin:4px auto 18px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:50%;

    background:#f1f5f9;

    font-size:48px;
}


/* Vehicle name */

.vehicle-name{
    margin:0;

    font-size:24px;
    line-height:1.2;

    overflow-wrap:anywhere;
}


/* Description */

.vehicle-description{
    margin-top:8px;

    color:#64748b;

    font-size:14px;
    line-height:1.5;
}


/* Privacy */

.privacy-box{

    display:flex;

    align-items:flex-start;

    gap:10px;

    margin-top:20px;

    padding:13px;

    background:#f8fafc;

    border:1px solid #e2e8f0;

    border-radius:13px;

    text-align:left;
}

.privacy-icon{
    flex:0 0 auto;

    font-size:19px;
}

.privacy-text{
    color:#475569;

    font-size:12px;

    line-height:1.45;
}

.privacy-text strong{
    color:#111827;
}


/* Call button */

.call-button{

    min-height:58px;

    margin-top:20px;

    border-radius:15px;

    font-size:17px;

    box-shadow:
        0 8px 20px
        rgba(17,24,39,.15);
}


/* Status */

.visitor-status{

    min-height:24px;

    margin-top:13px;

    color:#64748b;

    font-size:13px;
}


/* Calling panel */

.call-panel{

    text-align:center;

    padding-top:28px;
    padding-bottom:28px;
}


/* Calling animation */

.calling-circle{

    width:90px;
    height:90px;

    margin:0 auto 25px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:50%;

    background:#e0f2fe;

    font-size:40px;

    box-shadow:
        0 0 0 12px #f0f9ff,
        0 0 0 24px #f8fafc;

    animation:
        callingPulse
        1.8s
        ease-in-out
        infinite;
}

@keyframes callingPulse{

    0%{
        transform:scale(1);
    }

    50%{
        transform:scale(1.08);
    }

    100%{
        transform:scale(1);
    }
}


/* Call title */

.call-title{

    margin:0;

    font-size:22px;
}


/* Call timer */

.call-timer{

    margin:16px 0 20px !important;

    font-size:42px !important;

    font-weight:900;

    letter-spacing:2px;
}


/* End call */

.end-call{

    margin-top:0;

    min-height:54px;
}


/* Diagnostics */

.diagnostics-wrapper{

    margin-top:20px;
}


/* Footer */

.visitor-footer{

    margin-top:24px;

    text-align:center;

    color:#94a3b8;

    font-size:11px;

    line-height:1.5;
}


/* Error */

.error-card{

    margin-top:30px;

    text-align:center;
}

.error-icon{

    width:72px;
    height:72px;

    margin:0 auto 15px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:50%;

    background:#f1f5f9;

    font-size:35px;
}


/* Small phones */

@media(max-width:360px){

    .visitor-logo{
        width:68px;
        height:68px;
        font-size:34px;
    }

    .visitor-title{
        font-size:27px;
    }

    .vehicle-icon{
        width:82px;
        height:82px;
        font-size:42px;
    }

    .vehicle-name{
        font-size:22px;
    }

    .call-timer{
        font-size:36px !important;
    }

}

</style>

</head>


<body>

<main class="shell visitor-page">


    <!-- =========================================
         HEADER
         ========================================= -->

    <header class="visitor-header">

        <div class="visitor-logo">
            🚗
        </div>

        <div class="badge">
            PRIVATE VEHICLE CONTACT
        </div>

        <h1 class="visitor-title">
            Need to contact the owner?
        </h1>

        <p class="visitor-subtitle">
            Talk directly through your browser.
            No phone number required.
        </p>

    </header>


    <!-- =========================================
         VEHICLE CARD
         ========================================= -->

    <section class="card vehicle-card">


        <div class="vehicle-icon">
            🚘
        </div>


        <h2 class="vehicle-name">

            <?=htmlspecialchars(
                $car['name']
            )?>

        </h2>


        <p class="vehicle-description">

            <?=htmlspecialchars(
                $car['description']
                ?: 'Start a private internet audio call with the vehicle owner.'
            )?>

        </p>


        <!-- =====================================
             PRIVACY
             ===================================== -->

        <div class="privacy-box">

            <div class="privacy-icon">
                🔒
            </div>

            <div class="privacy-text">

                <strong>
                    Your phone number is private.
                </strong>

                <br>

                The call uses your browser's
                internet connection. Your phone
                number is not collected or revealed.

            </div>

        </div>


        <!-- =====================================
             CALL BUTTON
             ===================================== -->

        <button
            id="callBtn"
            class="primary call-button"
            type="button"
        >

            📞 Start Private Call

        </button>
        <div class="visitor-account-option">
    <p>Own a vehicle?</p>

    <a href="register.php" class="create-account-btn">
        🚗 Create a New Account
    </a>

    <small>
        Register your vehicle and generate your own QR code.
    </small>
</div>


        <p
            id="status"
            class="visitor-status"
            aria-live="polite"
        >

            Tap the button to request a call.

        </p>

    </section>


    <!-- =========================================
         CALL PANEL
         ========================================= -->

    <section
        id="callPanel"
        class="card call-panel hidden"
    >


        <div class="calling-circle">

            📞

        </div>


        <h2
            id="callTitle"
            class="call-title"
        >

            Connecting…

        </h2>


        <p
            id="timer"
            class="call-timer"
        >

            00:00

        </p>


        <button
            id="hangBtn"
            class="danger end-call"
            type="button"
        >

            🔴 End Call

        </button>

    </section>


    <!-- =========================================
         DIAGNOSTICS
         ========================================= -->

    <section
        class="diagnostics-wrapper"
    >

        <div
            class="diagnostics"
            aria-live="polite"
        >

            <h3>
                Connection Diagnostics
            </h3>

            <div id="diagnostics">

                Waiting for connection test…

            </div>

        </div>

    </section>


    <!-- =========================================
         FOOTER
         ========================================= -->

    <footer class="visitor-footer">

        🔒 Private browser-to-browser audio call

        <br>

        Microphone permission and internet
        connection are required.

    </footer>


</main>


<!-- =============================================
     CONFIG
     ============================================= -->

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
            ICE_SERVERS
    ],
    JSON_UNESCAPED_SLASHES
)?>;

</script>


<!-- =============================================
     SOCKET.IO
     ============================================= -->

<script
    src="https://cdn.socket.io/4.8.1/socket.io.min.js"
></script>


<!-- =============================================
     WEBRTC APPLICATION
     ============================================= -->

<script
    src="app.js?v=12"
></script>


</body>

</html>