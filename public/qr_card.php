<?php

require __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| GET CAR
|--------------------------------------------------------------------------
*/

$code = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '',
    $_GET['car'] ?? ''
);

$stmt = db()->prepare(
    'SELECT * FROM cars WHERE public_code = ? AND active = 1 LIMIT 1'
);

$stmt->execute([$code]);

$car = $stmt->fetch();

if (!$car) {
    http_response_code(404);
    exit('Car not found');
}


/*
|--------------------------------------------------------------------------
| VISITOR URL
|--------------------------------------------------------------------------
*/

$scheme = (
    !empty($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
) ? 'https' : 'http';

$base =
    $scheme .
    '://' .
    $_SERVER['HTTP_HOST'] .
    rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$visitorUrl =
    $base .
    '/index.php?car=' .
    rawurlencode($code);


/*
|--------------------------------------------------------------------------
| QR CODE
|--------------------------------------------------------------------------
*/

$qrUrl =
    'https://api.qrserver.com/v1/create-qr-code/' .
    '?size=900x900' .
    '&margin=20' .
    '&data=' .
    rawurlencode($visitorUrl);


$name = htmlspecialchars(
    $car['name'],
    ENT_QUOTES,
    'UTF-8'
);

$description = trim(
    $car['description'] ?? ''
);

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
VAHAN SAMPARK - <?= $name ?>
</title>

<style>

/*
|--------------------------------------------------------------------------
| RESET
|--------------------------------------------------------------------------
*/

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
}

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #d1d5db;

    color: #050505;

}


/*
|--------------------------------------------------------------------------
| TOOLBAR
|--------------------------------------------------------------------------
*/

.toolbar {

    position: sticky;

    top: 0;

    z-index: 100;

    width: 100%;

    padding: 12px;

    background: #111827;

    text-align: center;

    box-shadow:
        0 3px 12px rgba(0,0,0,.2);

}

.toolbar button,
.toolbar a {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 42px;

    margin: 4px;

    padding: 10px 18px;

    border: 0;

    border-radius: 8px;

    font-size: 14px;

    font-weight: 700;

    text-decoration: none;

    cursor: pointer;

}

.toolbar button {

    background: white;

    color: #111827;

}

.toolbar a {

    background: #374151;

    color: white;

}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

.page {

    min-height: calc(100vh - 65px);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 35px;

}


/*
|--------------------------------------------------------------------------
| VAHAN SAMPARK CARD
|--------------------------------------------------------------------------
*/

.vahan-card {

    width: 160mm;

    height: 100mm;

    max-width: 100%;

    background: white;

    display: grid;

    grid-template-columns: 58% 42%;

    overflow: hidden;

    border-radius: 5mm;

    box-shadow:
        0 15px 40px rgba(0,0,0,.22);

}


/*
|--------------------------------------------------------------------------
| LEFT SIDE
|--------------------------------------------------------------------------
*/

.left {

    background: #ffffff;

    padding: 8mm;

    display: flex;

    flex-direction: column;

    justify-content: space-between;

}


/*
|--------------------------------------------------------------------------
| BRAND
|--------------------------------------------------------------------------
*/

.brand {

    font-size: 30px;

    line-height: 1;

    font-weight: 950;

    letter-spacing: -1.5px;

    text-transform: uppercase;

}

.brand span {

    color: #facc15;

}


/*
|--------------------------------------------------------------------------
| BRAND SUBTITLE
|--------------------------------------------------------------------------
*/

.brand-subtitle {

    margin-top: 4px;

    color: #777;

    font-size: 9px;

    line-height: 1.4;

    max-width: 95%;

}


/*
|--------------------------------------------------------------------------
| VEHICLE NAME
|--------------------------------------------------------------------------
*/

.vehicle-name {

    margin-top: 8px;

    font-size: 16px;

    font-weight: 800;

    text-transform: uppercase;

}


/*
|--------------------------------------------------------------------------
| MAIN MESSAGE
|--------------------------------------------------------------------------
*/

.main-message {

    font-size: 25px;

    line-height: 1.12;

    font-weight: 900;

    margin: 0;

    letter-spacing: -0.7px;

}

.main-message .underline {

    text-decoration: underline;

    text-decoration-thickness: 2px;

    text-underline-offset: 4px;

}


/*
|--------------------------------------------------------------------------
| BOTTOM TEXT
|--------------------------------------------------------------------------
*/

.bottom-text {

    font-size: 9px;

    line-height: 1.35;

    color: #555;

    text-transform: uppercase;

}

.bottom-text strong {

    color: #111;

}


/*
|--------------------------------------------------------------------------
| RIGHT SIDE
|--------------------------------------------------------------------------
*/

.right {

    background: #ffd900;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: space-between;

    padding: 5mm 5mm 4mm;

}


/*
|--------------------------------------------------------------------------
| QR AREA
|--------------------------------------------------------------------------
*/

.qr-box {

    width: 62mm;

    height: 62mm;

    max-width: 100%;

    background: white;

    border-radius: 5mm;

    padding: 3mm;

    display: flex;

    align-items: center;

    justify-content: center;

    border: 2px solid #222;

}

.qr {

    width: 100%;

    height: 100%;

    object-fit: contain;

    display: block;

}


/*
|--------------------------------------------------------------------------
| RIGHT BRAND
|--------------------------------------------------------------------------
*/

.qr-brand {

    width: 100%;

    text-align: right;

    font-size: 12px;

    font-weight: 900;

    color: #111;

}

.qr-brand span {

    color: #8b7500;

}


/*
|--------------------------------------------------------------------------
| ICON / WARNING ROW
|--------------------------------------------------------------------------
*/

.warning-row {

    width: 100%;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 5px;

    text-align: center;

}

.warning-item {

    font-size: 8px;

    line-height: 1.15;

    font-weight: 700;

}

.warning-icon {

    display: block;

    font-size: 20px;

    margin-bottom: 3px;

}


/*
|--------------------------------------------------------------------------
| RIGHT DESCRIPTION
|--------------------------------------------------------------------------
*/

.right-description {

    font-size: 9px;

    line-height: 1.25;

    text-align: center;

    font-weight: 600;

}


/*
|--------------------------------------------------------------------------
| PRINT
|--------------------------------------------------------------------------
*/

@page {

    size: 160mm 100mm;

    margin: 0;

}

@media print {

    html,
    body {

        width: 160mm;

        height: 100mm;

        background: white;

    }

    .toolbar {

        display: none;

    }

    .page {

        width: 160mm;

        height: 100mm;

        min-height: 0;

        padding: 0;

        margin: 0;

    }

    .vahan-card {

        width: 160mm;

        height: 100mm;

        max-width: none;

        border-radius: 0;

        box-shadow: none;

    }

}


/*
|--------------------------------------------------------------------------
| MOBILE PREVIEW
|--------------------------------------------------------------------------
*/

@media screen and (max-width: 700px) {

    .page {

        padding: 15px;

        overflow-x: auto;

        justify-content: flex-start;

    }

    .vahan-card {

        min-width: 800px;

        transform-origin: top left;

    }

}

</style>

</head>


<body>


<!--
|--------------------------------------------------------------------------
| TOOLBAR
|--------------------------------------------------------------------------
-->

<div class="toolbar">

    <button onclick="window.print()">
        🖨️ Print / Save PDF
    </button>

    <a
        href="qr.php?car=<?=urlencode($code)?>"
    >
        ← Back to QR
    </a>

</div>


<!--
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
-->

<div class="page">

<section class="vahan-card">


<!--
|--------------------------------------------------------------------------
| LEFT
|--------------------------------------------------------------------------
-->

<div class="left">


    <!-- BRAND -->

    <div>

        <div class="brand">
            VAHAN<span>SAMPARK</span>
        </div>

        <div class="brand-subtitle">

            Vahan Sampark vehicle tag.
            Contact vehicle owner, report an issue,
            emergency contact or wrong parking.

        </div>

        <div class="vehicle-name">

            Vehicle:
            <?= $name ?>

        </div>

    </div>


    <!-- MAIN MESSAGE -->

    <div>

        <div class="main-message">

            Scan the code<br>

            to <span class="underline">
                contact
            </span><br>

            <span class="underline">
                vehicle owner.
            </span>

        </div>

    </div>


    <!-- BOTTOM -->

    <div class="bottom-text">

        Scan using phone camera,
        Google Lens or any QR scanner app.

        <br><br>

        <strong>
            No phone number is displayed.
        </strong>

    </div>


</div>


<!--
|--------------------------------------------------------------------------
| RIGHT
|--------------------------------------------------------------------------
-->

<div class="right">


    <!-- QR -->

    <div class="qr-box">

        <img
            class="qr"
            src="<?=htmlspecialchars(
                $qrUrl,
                ENT_QUOTES,
                'UTF-8'
            )?>"
            alt="VAHAN SAMPARK QR"
        >

    </div>


    <!-- SMALL BRAND -->

    <div class="qr-brand">

        VAHAN<span>SAMPARK</span>

    </div>


    <!-- WARNING ICONS -->

    <div class="warning-row">


        <div class="warning-item">

            <span class="warning-icon">
                🚨
            </span>

            Emergency<br>
            Contact

        </div>


        <div class="warning-item">

            <span class="warning-icon">
                🚫
            </span>

            Wrong<br>
            Parking

        </div>


        <div class="warning-item">

            <span class="warning-icon">
                ⚠️
            </span>

            Vehicle<br>
            Issue

        </div>


    </div>


    <!-- DESCRIPTION -->

    <div class="right-description">

        Wrong Parking, Emergency Contact,<br>

        any issue with the vehicle,<br>

        <strong>Scan the QR.</strong>

    </div>


</div>


</section>

</div>


</body>

</html>