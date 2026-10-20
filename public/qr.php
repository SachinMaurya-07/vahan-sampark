<?php

require __DIR__ . '/config.php';

$code = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '',
    $_GET['car'] ?? ''
);

$stmt = db()->prepare(
    'SELECT * FROM cars WHERE public_code = ? LIMIT 1'
);

$stmt->execute([$code]);

$car = $stmt->fetch();

if (!$car) {
    http_response_code(404);
    exit('Car not found.');
}


/*
|--------------------------------------------------------------------------
| BUILD VISITOR URL
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
| QR API
|--------------------------------------------------------------------------
*/

$qrUrl =
    'https://api.qrserver.com/v1/create-qr-code/' .
    '?size=700x700' .
    '&margin=20' .
    '&data=' .
    rawurlencode($visitorUrl);

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<meta
    name="theme-color"
    content="#111827"
>

<title>
QR - <?=htmlspecialchars($car['name'])?>
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    min-height: 100vh;

    font-family:
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    background: #f3f4f6;

    color: #111827;

    display: flex;

    justify-content: center;

    padding: 25px 15px;

}

.container {

    width: 100%;

    max-width: 500px;

}


/* HEADER */

.header {

    text-align: center;

    margin-bottom: 20px;

}

.logo {

    width: 65px;

    height: 65px;

    margin: 0 auto 12px;

    border-radius: 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #111827;

    color: white;

    font-size: 30px;

}

.header h1 {

    margin: 0;

    font-size: 25px;

}

.header p {

    margin: 7px 0 0;

    color: #6b7280;

}


/* QR CARD */

.qr-card {

    background: white;

    border-radius: 22px;

    padding: 25px;

    box-shadow:
        0 10px 30px rgba(0,0,0,.08);

    text-align: center;

}


/* QR */

.qr-wrapper {

    background: white;

    border-radius: 15px;

    padding: 12px;

    display: inline-block;

    margin: 10px auto 20px;

}

.qr {

    display: block;

    width: 320px;

    max-width: 100%;

    height: auto;

}


/* INSTRUCTION */

.instruction {

    font-size: 16px;

    font-weight: 600;

    margin: 5px 0;

}

.sub {

    color: #6b7280;

    font-size: 14px;

    line-height: 1.5;

}


/* BUTTONS */

.buttons {

    display: grid;

    gap: 10px;

    margin-top: 22px;

}

.btn {

    display: flex;

    align-items: center;

    justify-content: center;

    min-height: 48px;

    padding: 12px 16px;

    border-radius: 12px;

    text-decoration: none;

    font-weight: 600;

    cursor: pointer;

    border: none;

    font-size: 15px;

}

.btn-primary {

    background: #111827;

    color: white;

}

.btn-secondary {

    background: #e5e7eb;

    color: #111827;

}

.btn:hover {

    opacity: .9;

}


/* URL */

.url-box {

    margin-top: 20px;

    padding: 12px;

    background: #f3f4f6;

    border-radius: 10px;

    word-break: break-all;

    font-size: 12px;

    color: #4b5563;

}


/* FOOTER */

.footer {

    text-align: center;

    margin-top: 18px;

    color: #9ca3af;

    font-size: 12px;

}


/* PRINT */

@media print {

    body {

        background: white;

        padding: 0;

    }

    .container {

        max-width: none;

    }

    .header {

        margin-top: 20px;

    }

    .qr-card {

        box-shadow: none;

        border: 2px solid #111;

    }

    .buttons,

    .url-box,

    .footer {

        display: none;

    }

    .qr {

        width: 400px;

    }

}

</style>

</head>


<body>

<div class="container">


<div class="header">

<div class="logo">
    🚗
</div>

<h1>
    <?=htmlspecialchars($car['name'])?>
</h1>

<p>
    Private Vehicle Contact
</p>

</div>


<div class="qr-card">


<div class="qr-wrapper">

<img
    class="qr"
    id="qrImage"
    src="<?=htmlspecialchars($qrUrl)?>"
    alt="QR code for <?=htmlspecialchars($car['name'])?>"
>

</div>


<div class="instruction">

📱 Scan this QR code

</div>


<div class="sub">

Scan with your phone camera to privately
contact the vehicle owner.

<br>

No phone number is displayed.

</div>


<div class="buttons">

<button
    class="btn btn-primary"
    onclick="downloadQR()"
>
    ⬇️ Download QR
</button>


<button
    class="btn btn-secondary"
    onclick="window.print()"
>
    🖨️ Print QR
</button>


<a
    class="btn btn-secondary"
    href="<?=htmlspecialchars($visitorUrl)?>"
    target="_blank"
>
    🔗 Open Visitor Page
</a>

</div>


<div class="url-box">

<?=htmlspecialchars($visitorUrl)?>

</div>

</div>


<div class="footer">

Car WebCall • Private Internet Audio Calling

</div>


</div>


<script>

function downloadQR() {

    const image =
        document.getElementById('qrImage');

    const link =
        document.createElement('a');

    link.href = image.src;

    link.download =
        '<?=htmlspecialchars(
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $car['name']
            )
        )?>-QR.png';

    document.body.appendChild(link);

    link.click();

    document.body.removeChild(link);

}

</script>

</body>

</html>