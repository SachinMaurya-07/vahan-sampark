<?php

require __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| OWNER AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['owner_id'])) {
    header('Location: owner_login.php');
    exit;
}

$ownerId = (int) $_SESSION['owner_id'];


/*
|--------------------------------------------------------------------------
| GET OWNER
|--------------------------------------------------------------------------
*/

$stmt = db()->prepare(
    'SELECT id, name, email, phone, status, created_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->execute([
    $ownerId
]);

$owner = $stmt->fetch();

if (!$owner) {

    unset(
        $_SESSION['owner_id'],
        $_SESSION['owner_name'],
        $_SESSION['owner_email']
    );

    header('Location: owner_login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK ACCOUNT STATUS
|--------------------------------------------------------------------------
*/

if ($owner['status'] !== 'approved') {

    $statusMessage = '';

    if ($owner['status'] === 'pending') {
        $statusMessage =
            'Your account is waiting for admin approval.';
    } elseif ($owner['status'] === 'rejected') {
        $statusMessage =
            'Your account has been rejected by admin.';
    } elseif ($owner['status'] === 'suspended') {
        $statusMessage =
            'Your account has been suspended.';
    } else {
        $statusMessage =
            'Your account is not currently active.';
    }

} else {

    $statusMessage = '';
}


/*
|--------------------------------------------------------------------------
| GET OWNER VEHICLES
|--------------------------------------------------------------------------
*/

$stmt = db()->prepare(
    'SELECT *
     FROM cars
     WHERE user_id = ?
     ORDER BY id DESC'
);

$stmt->execute([
    $ownerId
]);

$cars = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| VEHICLE COUNTS
|--------------------------------------------------------------------------
*/

$totalVehicles = count($cars);

$approvedVehicles = 0;
$pendingVehicles = 0;
$rejectedVehicles = 0;

foreach ($cars as $car) {

    if ($car['approval_status'] === 'approved') {
        $approvedVehicles++;
    }

    if ($car['approval_status'] === 'pending') {
        $pendingVehicles++;
    }

    if ($car['approval_status'] === 'rejected') {
        $rejectedVehicles++;
    }
}


/*
|--------------------------------------------------------------------------
| CALL STATISTICS
|--------------------------------------------------------------------------
*/

$totalCalls = 0;
$connectedCalls = 0;
$rejectedCalls = 0;
$totalSeconds = 0;


/*
| Only calculate calls for this owner's vehicles.
*/

$stmt = db()->prepare(
    'SELECT
        COUNT(*) AS total_calls,

        COALESCE(
            SUM(
                CASE
                    WHEN status = "connected"
                      OR status = "ended"
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS connected_calls,

        COALESCE(
            SUM(
                CASE
                    WHEN status = "rejected"
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS rejected_calls,

        COALESCE(
            SUM(
                CASE
                    WHEN started_at IS NOT NULL
                     AND ended_at IS NOT NULL
                    THEN TIMESTAMPDIFF(
                        SECOND,
                        started_at,
                        ended_at
                    )
                    ELSE 0
                END
            ),
            0
        ) AS total_seconds

     FROM call_sessions cs

     INNER JOIN cars c
        ON c.id = cs.car_id

     WHERE c.user_id = ?'
);

$stmt->execute([
    $ownerId
]);

$stats = $stmt->fetch();

if ($stats) {

    $totalCalls =
        (int) $stats['total_calls'];

    $connectedCalls =
        (int) $stats['connected_calls'];

    $rejectedCalls =
        (int) $stats['rejected_calls'];

    $totalSeconds =
        (int) $stats['total_seconds'];
}


/*
|--------------------------------------------------------------------------
| FORMAT DURATION
|--------------------------------------------------------------------------
*/

function formatOwnerDuration(int $seconds): string
{
    $hours = intdiv($seconds, 3600);

    $minutes =
        intdiv(
            $seconds % 3600,
            60
        );

    $seconds =
        $seconds % 60;

    if ($hours > 0) {

        return sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $seconds
        );
    }

    return sprintf(
        '%02d:%02d',
        $minutes,
        $seconds
    );
}


$totalCallTime =
    formatOwnerDuration(
        $totalSeconds
    );

?>

<!doctype html>

<html lang="en">

<head>
<link rel="manifest" href="manifest.json">

<meta
    name="theme-color"
    content="#ffd400"
>

<script
    src="pwa.js"
    defer
></script>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<meta
    name="theme-color"
    content="#ffd600"
>
<div style="
    background:#fff8c7;
    border:2px solid #111;
    border-radius:14px;
    padding:16px;
    margin-bottom:20px;
">

    <strong>📱 VAHAN SAMPARK App</strong>

    <p style="
        margin:8px 0 14px;
        font-size:13px;
    ">
        Enable notifications and install the app
        for faster incoming-call alerts.
    </p>

    <button
        id="enableNotificationsBtn"
        style="
            width:100%;
            border:2px solid #111;
            border-radius:9px;
            padding:12px;
            background:#ffd400;
            font-weight:800;
            cursor:pointer;
        "
    >
        🔔 Enable Notifications
    </button>

    <button
        id="installPwaBtn"
        style="
            display:none;
            width:100%;
            margin-top:8px;
            border:2px solid #111;
            border-radius:9px;
            padding:12px;
            background:#111;
            color:#fff;
            font-weight:800;
            cursor:pointer;
        "
    >
        📱 Install VAHAN SAMPARK
    </button>

    <div
        id="notificationStatus"
        style="
            margin-top:10px;
            font-size:12px;
            color:#555;
        "
    ></div>

</div>
<title>
VAHAN SAMPARK - Owner Dashboard
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    background: #f5f5f5;

    color: #111827;

    font-family:
        Inter,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

.page {

    width: 100%;

    max-width: 1100px;

    margin: auto;

    padding: 16px;
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.header {

    background: #ffd600;

    border-radius: 20px;

    padding: 20px;

    margin-bottom: 16px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}

.brand {

    display: flex;

    align-items: center;

    gap: 12px;
}

.brand-icon {

    width: 48px;

    height: 48px;

    border-radius: 14px;

    background: #111827;

    color: #ffd600;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 24px;
}

.brand-name {

    font-size: 18px;

    font-weight: 900;

    line-height: 1.1;
}

.brand-sub {

    font-size: 11px;

    color: #374151;

    margin-top: 3px;
}

.logout {

    text-decoration: none;

    background: #111827;

    color: white;

    padding: 10px 13px;

    border-radius: 10px;

    font-size: 12px;

    font-weight: 800;

    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| WELCOME
|--------------------------------------------------------------------------
*/

.welcome {

    margin-bottom: 18px;
}

.welcome h1 {

    margin: 0;

    font-size: 25px;

    font-weight: 900;
}

.welcome p {

    margin: 5px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/*
|--------------------------------------------------------------------------
| ACCOUNT STATUS
|--------------------------------------------------------------------------
*/

.account-status {

    padding: 14px 16px;

    border-radius: 14px;

    margin-bottom: 18px;

    font-size: 14px;

    font-weight: 600;
}

.account-approved {

    background: #dcfce7;

    color: #166534;

    border: 1px solid #bbf7d0;
}

.account-pending {

    background: #fef3c7;

    color: #92400e;

    border: 1px solid #fde68a;
}

.account-rejected {

    background: #fee2e2;

    color: #991b1b;

    border: 1px solid #fecaca;
}


/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/

.stats {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 10px;

    margin-bottom: 18px;
}

.stat {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 15px;
}

.stat-icon {

    font-size: 21px;

    margin-bottom: 7px;
}

.stat-value {

    font-size: 22px;

    font-weight: 900;

    line-height: 1.1;
}

.stat-label {

    margin-top: 5px;

    font-size: 11px;

    color: #6b7280;
}


/*
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
*/

.card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    padding: 18px;

    margin-bottom: 18px;
}

.section-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 14px;
}

.section-header h2 {

    margin: 0;

    font-size: 19px;
}

.count {

    background: #f3f4f6;

    color: #6b7280;

    border-radius: 999px;

    padding: 5px 9px;

    font-size: 11px;

    font-weight: 700;
}


/*
|--------------------------------------------------------------------------
| ADD VEHICLE
|--------------------------------------------------------------------------
*/

.add-button {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 100%;

    min-height: 48px;

    border-radius: 12px;

    background: #111827;

    color: white;

    text-decoration: none;

    font-size: 14px;

    font-weight: 800;

    margin-bottom: 15px;
}


/*
|--------------------------------------------------------------------------
| VEHICLE
|--------------------------------------------------------------------------
*/

.vehicle {

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 16px;

    margin-top: 12px;
}

.vehicle-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 10px;
}

.vehicle-name {

    font-size: 17px;

    font-weight: 800;
}

.vehicle-code {

    font-size: 11px;

    color: #9ca3af;

    margin-top: 4px;

    word-break: break-all;
}


/*
|--------------------------------------------------------------------------
| APPROVAL BADGES
|--------------------------------------------------------------------------
*/

.badge {

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 10px;

    font-weight: 800;

    white-space: nowrap;
}

.badge-approved {

    background: #dcfce7;

    color: #166534;
}

.badge-pending {

    background: #fef3c7;

    color: #92400e;
}

.badge-rejected {

    background: #fee2e2;

    color: #991b1b;
}


/*
|--------------------------------------------------------------------------
| VEHICLE DESCRIPTION
|--------------------------------------------------------------------------
*/

.vehicle-description {

    color: #6b7280;

    font-size: 13px;

    line-height: 1.5;

    margin: 10px 0 0;
}


/*
|--------------------------------------------------------------------------
| VEHICLE ACTIONS
|--------------------------------------------------------------------------
*/

.vehicle-actions {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 8px;

    margin-top: 14px;
}

.vehicle-actions a {

    min-height: 42px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    border-radius: 10px;

    background: #f3f4f6;

    color: #111827;

    font-size: 12px;

    font-weight: 800;
}

.vehicle-actions .operator {

    background: #111827;

    color: white;
}

.vehicle-actions .disabled {

    opacity: .45;

    pointer-events: none;
}


/*
|--------------------------------------------------------------------------
| EMPTY
|--------------------------------------------------------------------------
*/

.empty {

    text-align: center;

    padding: 30px 15px;

    color: #6b7280;
}

.empty-icon {

    font-size: 40px;

    margin-bottom: 8px;
}

.empty strong {

    color: #111827;
}


/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

.footer {

    text-align: center;

    color: #9ca3af;

    font-size: 11px;

    padding: 8px 0 20px;
}


/*
|--------------------------------------------------------------------------
| DESKTOP
|--------------------------------------------------------------------------
*/

@media (min-width: 700px) {

    .page {

        padding: 25px;
    }

    .header {

        padding: 22px 25px;
    }

    .stats {

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 14px;
    }

    .vehicle-actions {

        grid-template-columns:
            repeat(4, minmax(0, 1fr));
    }

    .vehicle {

        padding: 18px;
    }
}

</style>

</head>


<body>

<div class="page">


<!--
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
-->

<header class="header">

    <div class="brand">

        <div class="brand-icon">
            🚗
        </div>

        <div>

            <div class="brand-name">
                VAHAN SAMPARK
            </div>

            <div class="brand-sub">
                Vehicle Owner Portal
            </div>

        </div>

    </div>


    <a
        href="owner_logout.php"
        class="logout"
    >
        Logout
    </a>

</header>


<!--
|--------------------------------------------------------------------------
| WELCOME
|--------------------------------------------------------------------------
-->

<div class="welcome">

    <h1>
        Hello, <?=htmlspecialchars($owner['name'])?> 👋
    </h1>

    <p>
        Manage your vehicles and receive private calls.
    </p>

</div>


<!--
|--------------------------------------------------------------------------
| ACCOUNT STATUS
|--------------------------------------------------------------------------
-->

<?php if ($owner['status'] === 'approved'): ?>

<div class="account-status account-approved">

    🟢 Your owner account is approved and active.

</div>

<?php elseif ($owner['status'] === 'pending'): ?>

<div class="account-status account-pending">

    🟡 <?=htmlspecialchars($statusMessage)?>

</div>

<?php else: ?>

<div class="account-status account-rejected">

    🔴 <?=htmlspecialchars($statusMessage)?>

</div>

<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
-->

<section class="stats">


<div class="stat">

    <div class="stat-icon">
        🚗
    </div>

    <div class="stat-value">
        <?=number_format($totalVehicles)?>
    </div>

    <div class="stat-label">
        My Vehicles
    </div>

</div>


<div class="stat">

    <div class="stat-icon">
        📞
    </div>

    <div class="stat-value">
        <?=number_format($totalCalls)?>
    </div>

    <div class="stat-label">
        Total Calls
    </div>

</div>


<div class="stat">

    <div class="stat-icon">
        ✅
    </div>

    <div class="stat-value">
        <?=number_format($connectedCalls)?>
    </div>

    <div class="stat-label">
        Connected Calls
    </div>

</div>


<div class="stat">

    <div class="stat-icon">
        ⏱️
    </div>

    <div class="stat-value">
        <?=htmlspecialchars($totalCallTime)?>
    </div>

    <div class="stat-label">
        Total Call Time
    </div>

</div>


</section>


<!--
|--------------------------------------------------------------------------
| VEHICLES
|--------------------------------------------------------------------------
-->

<section class="card">

<div class="section-header">

    <h2>
        🚗 My Vehicles
    </h2>

    <span class="count">
        <?=number_format($totalVehicles)?>
    </span>

</div>


<?php if ($owner['status'] === 'approved'): ?>

<a
    href="owner_add_vehicle.php"
    class="add-button"
>
    ➕ Add New Vehicle
</a>

<?php endif; ?>


<?php if (!$cars): ?>

<div class="empty">

    <div class="empty-icon">
        🚗
    </div>

    <strong>
        No vehicles yet
    </strong>

    <p>
        Add your first vehicle to get started.
    </p>

</div>

<?php else: ?>


<?php foreach ($cars as $car): ?>

<div class="vehicle">


    <div class="vehicle-top">

        <div>

            <div class="vehicle-name">

                <?=htmlspecialchars(
                    $car['name']
                )?>

            </div>

            <div class="vehicle-code">

                Code:
                <?=htmlspecialchars(
                    $car['public_code']
                )?>

            </div>

        </div>


        <?php if (
            $car['approval_status'] === 'approved'
        ): ?>

            <span class="badge badge-approved">
                APPROVED
            </span>

        <?php elseif (
            $car['approval_status'] === 'pending'
        ): ?>

            <span class="badge badge-pending">
                PENDING
            </span>

        <?php else: ?>

            <span class="badge badge-rejected">
                REJECTED
            </span>

        <?php endif; ?>

    </div>


    <?php if (
        !empty($car['description'])
    ): ?>

        <div class="vehicle-description">

            <?=htmlspecialchars(
                $car['description']
            )?>

        </div>

    <?php endif; ?>


    <div class="vehicle-actions">


        <?php if (
            $car['approval_status'] === 'approved'
            && (int)$car['active'] === 1
        ): ?>

            <a
                href="operator.php?car=<?=urlencode(
                    $car['public_code']
                )?>"
                class="operator"
            >
                🎧 Operator
            </a>

            <a
                href="qr.php?car=<?=urlencode(
                    $car['public_code']
                )?>"
                target="_blank"
            >
                📱 QR
            </a>

            <a
                href="qr_card.php?car=<?=urlencode(
                    $car['public_code']
                )?>"
                target="_blank"
            >
                🖨️ Print
            </a>

            <a
                href="index.php?car=<?=urlencode(
                    $car['public_code']
                )?>"
                target="_blank"
            >
                👁 Visitor
            </a>

        <?php else: ?>

            <a class="disabled">
                🔒 Awaiting Approval
            </a>

        <?php endif; ?>


    </div>

</div>

<?php endforeach; ?>


<?php endif; ?>


</section>


<!--
|--------------------------------------------------------------------------
| ACCOUNT
|--------------------------------------------------------------------------
-->

<section class="card">

    <div class="section-header">

        <h2>
            👤 My Account
        </h2>

    </div>

    <div
        style="
            font-size:14px;
            line-height:1.8;
            color:#4b5563;
        "
    >

        <strong style="color:#111827;">
            Name:
        </strong>

        <?=htmlspecialchars(
            $owner['name']
        )?>

        <br>

        <strong style="color:#111827;">
            Email:
        </strong>

        <?=htmlspecialchars(
            $owner['email']
        )?>

        <br>

        <?php if (!empty($owner['phone'])): ?>

            <strong style="color:#111827;">
                Phone:
            </strong>

            <?=htmlspecialchars(
                $owner['phone']
            )?>

            <br>

        <?php endif; ?>

        <strong style="color:#111827;">
            Account:
        </strong>

        <?=htmlspecialchars(
            ucfirst($owner['status'])
        )?>

    </div>

</section>


<div class="footer">

    VAHAN SAMPARK © <?=date('Y')?>

</div>


</div>

</body>

</html>