<?php

require __DIR__ . '/config.php';

require_admin();

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| ADMIN ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | CREATE ADMIN VEHICLE
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {

            $error = 'Car name is required.';

        } else {

            try {

                $code = strtoupper(
                    bin2hex(
                        random_bytes(5)
                    )
                );

                /*
                 * Vehicles created directly by master admin
                 * are automatically approved.
                 */

                $stmt = db()->prepare(
                    'INSERT INTO cars
                    (
                        user_id,
                        public_code,
                        name,
                        description,
                        active,
                        approval_status
                    )
                    VALUES
                    (
                        NULL,
                        ?,
                        ?,
                        ?,
                        1,
                        "approved"
                    )'
                );

                $stmt->execute([
                    $code,
                    $name,
                    $description
                ]);

                $message = 'Vehicle created successfully.';

            } catch (Throwable $e) {

                $error = 'Unable to create vehicle.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE VEHICLE
    |--------------------------------------------------------------------------
    */

    if ($action === 'update') {

        $id = intval($_POST['id'] ?? 0);

        $name = trim(
            $_POST['name'] ?? ''
        );

        $description = trim(
            $_POST['description'] ?? ''
        );


        if (
            $id <= 0 ||
            $name === ''
        ) {

            $error = 'Invalid car information.';

        } else {

            $stmt = db()->prepare(
                'UPDATE cars
                 SET
                    name = ?,
                    description = ?
                 WHERE id = ?'
            );

            $stmt->execute([
                $name,
                $description,
                $id
            ]);

            $message =
                'Car updated successfully.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE VEHICLE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id =
            intval(
                $_POST['id'] ?? 0
            );


        if ($id > 0) {

            $stmt = db()->prepare(
                'DELETE FROM cars
                 WHERE id = ?'
            );

            $stmt->execute([
                $id
            ]);

            $message =
                'Car deleted successfully.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TOGGLE VEHICLE
    |--------------------------------------------------------------------------
    */

    if ($action === 'toggle') {

        $id =
            intval(
                $_POST['id'] ?? 0
            );


        if ($id > 0) {

            $stmt = db()->prepare(
                'UPDATE cars
                 SET active =
                    IF(active = 1, 0, 1)
                 WHERE id = ?'
            );

            $stmt->execute([
                $id
            ]);

            $message =
                'Car status updated.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE OWNER
    |--------------------------------------------------------------------------
    */

    if ($action === 'approve_owner') {

        $ownerId =
            intval(
                $_POST['owner_id'] ?? 0
            );

        if ($ownerId > 0) {

            $stmt = db()->prepare(
                'UPDATE users
                 SET status = "approved"
                 WHERE id = ?
                 AND status = "pending"'
            );

            $stmt->execute([
                $ownerId
            ]);

            if ($stmt->rowCount() > 0) {

                $message =
                    'Owner account approved successfully.';

            } else {

                $error =
                    'Owner account was not found or is no longer pending.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT OWNER
    |--------------------------------------------------------------------------
    */

    if ($action === 'reject_owner') {

        $ownerId =
            intval(
                $_POST['owner_id'] ?? 0
            );

        if ($ownerId > 0) {

            $stmt = db()->prepare(
                'UPDATE users
                 SET status = "rejected"
                 WHERE id = ?
                 AND status = "pending"'
            );

            $stmt->execute([
                $ownerId
            ]);

            if ($stmt->rowCount() > 0) {

                $message =
                    'Owner account rejected.';

            } else {

                $error =
                    'Owner account was not found or is no longer pending.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE VEHICLE
    |--------------------------------------------------------------------------
    */

    if ($action === 'approve_vehicle') {

        $vehicleId =
            intval(
                $_POST['vehicle_id'] ?? 0
            );

        if ($vehicleId > 0) {

            $stmt = db()->prepare(
                'UPDATE cars
                 SET
                    approval_status = "approved",
                    active = 1
                 WHERE id = ?
                 AND approval_status = "pending"'
            );

            $stmt->execute([
                $vehicleId
            ]);

            if ($stmt->rowCount() > 0) {

                $message =
                    'Vehicle approved successfully.';

            } else {

                $error =
                    'Vehicle was not found or is no longer pending.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT VEHICLE
    |--------------------------------------------------------------------------
    */

    if ($action === 'reject_vehicle') {

        $vehicleId =
            intval(
                $_POST['vehicle_id'] ?? 0
            );

        if ($vehicleId > 0) {

            $stmt = db()->prepare(
                'UPDATE cars
                 SET
                    approval_status = "rejected",
                    active = 0
                 WHERE id = ?
                 AND approval_status = "pending"'
            );

            $stmt->execute([
                $vehicleId
            ]);

            if ($stmt->rowCount() > 0) {

                $message =
                    'Vehicle rejected.';

            } else {

                $error =
                    'Vehicle was not found or is no longer pending.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT MODE
|--------------------------------------------------------------------------
*/

$editCar = null;

if (isset($_GET['edit'])) {

    $editId =
        intval(
            $_GET['edit']
        );


    $stmt = db()->prepare(
        'SELECT *
         FROM cars
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $editId
    ]);

    $editCar =
        $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| GET ALL VEHICLES
|--------------------------------------------------------------------------
*/

$cars =
    db()->query(
        'SELECT
            c.*,
            u.name AS owner_name,
            u.email AS owner_email
         FROM cars c
         LEFT JOIN users u
            ON u.id = c.user_id
         ORDER BY c.id DESC'
    )->fetchAll();


/*
|--------------------------------------------------------------------------
| PENDING OWNERS
|--------------------------------------------------------------------------
*/

$pendingOwners =
    db()->query(
        'SELECT
            id,
            name,
            email,
            phone,
            status,
            created_at
         FROM users
         WHERE status = "pending"
         ORDER BY id DESC'
    )->fetchAll();


/*
|--------------------------------------------------------------------------
| PENDING VEHICLES
|--------------------------------------------------------------------------
*/

$pendingVehicles =
    db()->query(
        'SELECT
            c.*,
            u.name AS owner_name,
            u.email AS owner_email
         FROM cars c
         INNER JOIN users u
            ON u.id = c.user_id
         WHERE c.approval_status = "pending"
         ORDER BY c.id DESC'
    )->fetchAll();


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

$totalCars =
    (int) db()->query(
        'SELECT COUNT(*)
         FROM cars'
    )->fetchColumn();


$activeCars =
    (int) db()->query(
        'SELECT COUNT(*)
         FROM cars
         WHERE active = 1'
    )->fetchColumn();


$inactiveCars =
    $totalCars -
    $activeCars;


/*
|--------------------------------------------------------------------------
| APPROVAL STATISTICS
|--------------------------------------------------------------------------
*/

$pendingOwnerCount =
    (int) db()->query(
        'SELECT COUNT(*)
         FROM users
         WHERE status = "pending"'
    )->fetchColumn();


$pendingVehicleCount =
    (int) db()->query(
        'SELECT COUNT(*)
         FROM cars
         WHERE approval_status = "pending"'
    )->fetchColumn();


/*
|--------------------------------------------------------------------------
| CALL STATISTICS
|--------------------------------------------------------------------------
*/

$totalCalls =
    (int) db()->query(
        'SELECT COUNT(*)
         FROM call_sessions'
    )->fetchColumn();


$connectedCalls =
    (int) db()->query(
        'SELECT COUNT(*)
         FROM call_sessions
         WHERE status = "connected"
            OR status = "ended"'
    )->fetchColumn();


$rejectedCalls =
    (int) db()->query(
        'SELECT COUNT(*)
         FROM call_sessions
         WHERE status = "rejected"'
    )->fetchColumn();


$ringingCalls =
    (int) db()->query(
        'SELECT COUNT(*)
         FROM call_sessions
         WHERE status = "ringing"'
    )->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL CALL DURATION
|--------------------------------------------------------------------------
*/

$totalSeconds =
    (int) db()->query(
        'SELECT COALESCE(
            SUM(
                TIMESTAMPDIFF(
                    SECOND,
                    started_at,
                    ended_at
                )
            ),
            0
         )
         FROM call_sessions
         WHERE started_at IS NOT NULL
         AND ended_at IS NOT NULL'
    )->fetchColumn();


function formatDuration(
    int $seconds
): string {

    $hours =
        intdiv(
            $seconds,
            3600
        );

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


$totalDuration =
    formatDuration(
        $totalSeconds
    );


/*
|--------------------------------------------------------------------------
| RECENT CALL HISTORY
|--------------------------------------------------------------------------
*/

$recentCalls =
    db()->query(
        'SELECT
            cs.id,
            cs.room_id,
            cs.status,
            cs.started_at,
            cs.ended_at,
            cs.created_at,
            c.name AS car_name,
            c.public_code
         FROM call_sessions cs
         INNER JOIN cars c
            ON c.id = cs.car_id
         ORDER BY cs.id DESC
         LIMIT 20'
    )->fetchAll();

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
VAHAN SAMPARK Admin
</title>

<link
    rel="stylesheet"
    href="style.css"
>


<style>

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.admin-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 20px;

}


.admin-user {

    color: #6b7280;

    font-size: 14px;

    margin-top: 5px;

}


.logout-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 10px 15px;

    border-radius: 10px;

    text-decoration: none;

    background: #fee2e2;

    color: #991b1b;

    font-size: 14px;

    font-weight: 700;

}


/*
|--------------------------------------------------------------------------
| ALERTS
|--------------------------------------------------------------------------
*/

.alert {

    padding: 12px 14px;

    border-radius: 10px;

    margin-bottom: 16px;

    font-size: 14px;

}


.alert-success {

    background: #dcfce7;

    color: #166534;

}


.alert-error {

    background: #fee2e2;

    color: #991b1b;

}


/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

.dashboard-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 10px;

    margin-bottom: 20px;

}


.stat-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 16px;

}


.stat-icon {

    font-size: 23px;

    margin-bottom: 8px;

}


.stat-value {

    font-size: 25px;

    font-weight: 800;

    line-height: 1.1;

}


.stat-label {

    margin-top: 5px;

    font-size: 12px;

    color: #6b7280;

}


@media (min-width: 700px) {

    .dashboard-grid {

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 14px;

    }

}


/*
|--------------------------------------------------------------------------
| APPROVAL CARDS
|--------------------------------------------------------------------------
*/

.approval-card {

    border: 2px solid #ffd400;

    background: #fffdf0;

    border-radius: 16px;

    padding: 16px;

    margin-top: 14px;

}


.approval-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 12px;

}


.approval-title {

    font-size: 17px;

    font-weight: 800;

}


.approval-meta {

    font-size: 13px;

    color: #6b7280;

    margin-top: 5px;

    word-break: break-word;

}


.pending-badge {

    background: #fef3c7;

    color: #92400e;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 800;

    white-space: nowrap;

}


.approval-actions {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 8px;

    margin-top: 14px;

}


.approval-actions button {

    border: 0;

    border-radius: 9px;

    padding: 11px;

    cursor: pointer;

    font-weight: 800;

}


.approve-btn {

    background: #dcfce7;

    color: #166534;

}


.reject-btn {

    background: #fee2e2;

    color: #991b1b;

}


.empty-approval {

    padding: 20px;

    text-align: center;

    color: #6b7280;

    background: #f9fafb;

    border-radius: 12px;

    margin-top: 14px;

}


/*
|--------------------------------------------------------------------------
| SECTION HEADER
|--------------------------------------------------------------------------
*/

.section-heading {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

}


.section-count {

    font-size: 12px;

    background: #f3f4f6;

    padding: 5px 9px;

    border-radius: 999px;

    color: #6b7280;

}


/*
|--------------------------------------------------------------------------
| CAR ADMIN CARD
|--------------------------------------------------------------------------
*/

.car-admin-card {

    padding: 18px;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    margin-top: 15px;

}


.car-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 12px;

}


.car-name {

    font-size: 18px;

    font-weight: 700;

}


.car-code {

    font-size: 12px;

    color: #6b7280;

    margin-top: 4px;

    word-break: break-all;

}


.owner-label {

    display: inline-block;

    margin-top: 8px;

    padding: 5px 8px;

    border-radius: 7px;

    background: #f3f4f6;

    color: #374151;

    font-size: 11px;

    font-weight: 700;

}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

.status-active {

    background: #dcfce7;

    color: #166534;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 12px;

    white-space: nowrap;

    font-weight: 700;

}


.status-inactive {

    background: #fee2e2;

    color: #991b1b;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 12px;

    white-space: nowrap;

    font-weight: 700;

}


.status-approved {

    background: #dcfce7;

    color: #166534;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 800;

}


.status-pending {

    background: #fef3c7;

    color: #92400e;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 800;

}


.status-rejected {

    background: #fee2e2;

    color: #991b1b;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 800;

}


/*
|--------------------------------------------------------------------------
| ADMIN ACTIONS
|--------------------------------------------------------------------------
*/

.admin-actions {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 8px;

    margin-top: 15px;

}


.admin-actions a,
.admin-actions button {

    display: flex;

    align-items: center;

    justify-content: center;

    min-height: 40px;

    text-align: center;

    padding: 9px 8px;

    border-radius: 9px;

    border: 0;

    text-decoration: none;

    cursor: pointer;

    font-size: 13px;

    font-weight: 600;

    background: #f3f4f6;

    color: #111827;

}


.admin-actions .delete {

    background: #fee2e2;

    color: #991b1b;

}


.admin-actions .toggle {

    background: #fef3c7;

    color: #92400e;

}


@media (min-width: 700px) {

    .admin-actions {

        grid-template-columns:
            repeat(7, 1fr);

    }

}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
*/

.edit-box {

    border: 2px solid #111827;

}


/*
|--------------------------------------------------------------------------
| CALL HISTORY
|--------------------------------------------------------------------------
*/

.history-wrapper {

    overflow-x: auto;

    margin-top: 15px;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

}


.history-table {

    width: 100%;

    min-width: 760px;

    border-collapse: collapse;

}


.history-table th {

    text-align: left;

    background: #f9fafb;

    padding: 12px;

    font-size: 12px;

    color: #6b7280;

    text-transform: uppercase;

    letter-spacing: .4px;

    border-bottom: 1px solid #e5e7eb;

}


.history-table td {

    padding: 13px 12px;

    border-bottom: 1px solid #f1f5f9;

    font-size: 13px;

}


.history-table tr:last-child td {

    border-bottom: 0;

}


.history-car {

    font-weight: 700;

}


.history-code {

    display: block;

    font-size: 10px;

    color: #9ca3af;

    margin-top: 3px;

}


/*
|--------------------------------------------------------------------------
| CALL STATUS
|--------------------------------------------------------------------------
*/

.call-status {

    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 800;

}


.call-connected {

    background: #dcfce7;

    color: #166534;

}


.call-ended {

    background: #dbeafe;

    color: #1d4ed8;

}


.call-rejected {

    background: #fee2e2;

    color: #991b1b;

}


.call-ringing {

    background: #fef3c7;

    color: #92400e;

}


.call-expired {

    background: #f3f4f6;

    color: #4b5563;

}


/*
|--------------------------------------------------------------------------
| HISTORY EMPTY
|--------------------------------------------------------------------------
*/

.history-empty {

    text-align: center;

    padding: 35px 20px;

    color: #6b7280;

}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 480px) {

    .admin-header {

        align-items: flex-start;

    }

    .admin-header h1 {

        font-size: 25px;

    }

    .logout-btn {

        padding: 8px 11px;

        font-size: 12px;

    }

    .stat-card {

        padding: 13px;

    }

    .stat-value {

        font-size: 21px;

    }

    .approval-top {

        flex-direction: column;

    }

}

</style>

</head>


<body>

<main class="shell">


<!--
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
-->

<div class="admin-header">

<div>

<div class="badge">
    VAHAN SAMPARK ADMIN
</div>


<h1>
    Dashboard
</h1>


<div class="admin-user">

Logged in as

<strong>
    <?=htmlspecialchars(
        $_SESSION['admin_username']
    )?>
</strong>

</div>

</div>


<a
    class="logout-btn"
    href="logout.php"
>
    Logout
</a>

</div>


<!--
|--------------------------------------------------------------------------
| ALERTS
|--------------------------------------------------------------------------
-->

<?php if ($message): ?>

<div class="alert alert-success">

    ✅
    <?=htmlspecialchars($message)?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-error">

    ❌
    <?=htmlspecialchars($error)?>

</div>

<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
-->

<section class="dashboard-grid">


<div class="stat-card">

<div class="stat-icon">
    🚗
</div>

<div class="stat-value">
    <?=number_format($totalCars)?>
</div>

<div class="stat-label">
    Total Vehicles
</div>

</div>


<div class="stat-card">

<div class="stat-icon">
    🟢
</div>

<div class="stat-value">
    <?=number_format($activeCars)?>
</div>

<div class="stat-label">
    Active Vehicles
</div>

</div>


<div class="stat-card">

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


<div class="stat-card">

<div class="stat-icon">
    👤
</div>

<div class="stat-value">
    <?=number_format($pendingOwnerCount)?>
</div>

<div class="stat-label">
    Pending Owners
</div>

</div>


<div class="stat-card">

<div class="stat-icon">
    🚘
</div>

<div class="stat-value">
    <?=number_format($pendingVehicleCount)?>
</div>

<div class="stat-label">
    Pending Vehicles
</div>

</div>


<div class="stat-card">

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


<div class="stat-card">

<div class="stat-icon">
    ❌
</div>

<div class="stat-value">
    <?=number_format($rejectedCalls)?>
</div>

<div class="stat-label">
    Rejected Calls
</div>

</div>


<div class="stat-card">

<div class="stat-icon">
    ⏱️
</div>

<div class="stat-value">
    <?=htmlspecialchars($totalDuration)?>
</div>

<div class="stat-label">
    Total Call Time
</div>

</div>


</section>


<!--
|--------------------------------------------------------------------------
| PENDING OWNER ACCOUNTS
|--------------------------------------------------------------------------
-->

<section class="card">

<div class="section-heading">

<h2>
    👤 Pending Owner Accounts
</h2>

<span class="section-count">
    <?=number_format($pendingOwnerCount)?> pending
</span>

</div>


<?php if (!$pendingOwners): ?>

<div class="empty-approval">

    ✅ No pending owner registrations.

</div>

<?php else: ?>


<?php foreach ($pendingOwners as $owner): ?>

<div class="approval-card">


<div class="approval-top">

<div>

<div class="approval-title">

<?=htmlspecialchars(
    $owner['name']
)?>

</div>


<div class="approval-meta">

📧
<?=htmlspecialchars(
    $owner['email']
)?>

</div>


<?php if (!empty($owner['phone'])): ?>

<div class="approval-meta">

📱
<?=htmlspecialchars(
    $owner['phone']
)?>

</div>

<?php endif; ?>


<div class="approval-meta">

Registered:
<?=htmlspecialchars(
    date(
        'd M Y, h:i A',
        strtotime(
            $owner['created_at']
        )
    )
)?>

</div>

</div>


<span class="pending-badge">
    PENDING
</span>

</div>


<div class="approval-actions">


<form method="post">

<input
    type="hidden"
    name="action"
    value="approve_owner"
>

<input
    type="hidden"
    name="owner_id"
    value="<?=intval(
        $owner['id']
    )?>"
>

<button
    type="submit"
    class="approve-btn"
>
    ✅ Approve
</button>

</form>


<form
    method="post"
    onsubmit="
        return confirm(
            'Reject this owner account?'
        )
    "
>

<input
    type="hidden"
    name="action"
    value="reject_owner"
>

<input
    type="hidden"
    name="owner_id"
    value="<?=intval(
        $owner['id']
    )?>"
>

<button
    type="submit"
    class="reject-btn"
>
    ❌ Reject
</button>

</form>


</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</section>


<!--
|--------------------------------------------------------------------------
| PENDING VEHICLES
|--------------------------------------------------------------------------
-->

<section class="card">

<div class="section-heading">

<h2>
    🚘 Pending Vehicle Approvals
</h2>

<span class="section-count">
    <?=number_format($pendingVehicleCount)?> pending
</span>

</div>


<?php if (!$pendingVehicles): ?>

<div class="empty-approval">

    ✅ No pending vehicle approvals.

</div>

<?php else: ?>


<?php foreach ($pendingVehicles as $vehicle): ?>

<div class="approval-card">


<div class="approval-top">

<div>

<div class="approval-title">

<?=htmlspecialchars(
    $vehicle['name']
)?>

</div>


<div class="approval-meta">

Code:
<?=htmlspecialchars(
    $vehicle['public_code']
)?>

</div>


<div class="approval-meta">

👤 Owner:
<strong>
<?=htmlspecialchars(
    $vehicle['owner_name']
)?>
</strong>

</div>


<div class="approval-meta">

📧
<?=htmlspecialchars(
    $vehicle['owner_email']
)?>

</div>


<?php if (!empty($vehicle['description'])): ?>

<div class="approval-meta">

📝
<?=htmlspecialchars(
    $vehicle['description']
)?>

</div>

<?php endif; ?>


</div>


<span class="pending-badge">
    PENDING
</span>

</div>


<div class="approval-actions">


<form method="post">

<input
    type="hidden"
    name="action"
    value="approve_vehicle"
>

<input
    type="hidden"
    name="vehicle_id"
    value="<?=intval(
        $vehicle['id']
    )?>"
>

<button
    type="submit"
    class="approve-btn"
>
    ✅ Approve Vehicle
</button>

</form>


<form
    method="post"
    onsubmit="
        return confirm(
            'Reject this vehicle?'
        )
    "
>

<input
    type="hidden"
    name="action"
    value="reject_vehicle"
>

<input
    type="hidden"
    name="vehicle_id"
    value="<?=intval(
        $vehicle['id']
    )?>"
>

<button
    type="submit"
    class="reject-btn"
>
    ❌ Reject Vehicle
</button>

</form>


</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</section>


<!--
|--------------------------------------------------------------------------
| ADD / EDIT VEHICLE
|--------------------------------------------------------------------------
-->

<section class="card <?= $editCar ? 'edit-box' : '' ?>">


<h2>

<?= $editCar
    ? '✏️ Edit Vehicle'
    : '➕ Add New Vehicle'
?>

</h2>


<form method="post">


<input
    type="hidden"
    name="action"
    value="<?= $editCar
        ? 'update'
        : 'create'
    ?>"
>


<?php if ($editCar): ?>

<input
    type="hidden"
    name="id"
    value="<?=intval(
        $editCar['id']
    )?>"
>

<?php endif; ?>


<label>

Vehicle Name

<input
    type="text"
    name="name"
    placeholder="Honda City"
    value="<?=htmlspecialchars(
        $editCar['name'] ?? ''
    )?>"
    required
>

</label>


<label>

Description

<input
    type="text"
    name="description"
    placeholder="Contact owner about this vehicle"
    value="<?=htmlspecialchars(
        $editCar['description'] ?? ''
    )?>"
>

</label>


<button
    type="submit"
    class="primary"
>

<?= $editCar
    ? '💾 Save Changes'
    : '➕ Create Vehicle'
?>

</button>


<?php if ($editCar): ?>

<a
    href="admin.php"
    class="link"
    style="
        display:block;
        text-align:center;
        margin-top:12px;
    "
>
    Cancel Edit
</a>

<?php endif; ?>


</form>

</section>


<!--
|--------------------------------------------------------------------------
| ALL VEHICLES
|--------------------------------------------------------------------------
-->

<section class="card">


<div class="section-heading">

<h2>
    🚗 All Vehicles
</h2>

<span class="section-count">

<?=number_format(
    $totalCars
)?> total

</span>

</div>


<?php if (!$cars): ?>

<p class="muted">
    No vehicles have been added yet.
</p>

<?php endif; ?>


<?php foreach ($cars as $car): ?>


<div class="car-admin-card">


<div class="car-top">


<div>

<div class="car-name">

<?=htmlspecialchars(
    $car['name']
)?>

</div>


<div class="car-code">

Code:
<?=htmlspecialchars(
    $car['public_code']
)?>

</div>


<?php if (!empty($car['owner_name'])): ?>

<div class="owner-label">

👤 Owner:
<?=htmlspecialchars(
    $car['owner_name']
)?>

</div>

<?php else: ?>

<div class="owner-label">

👑 Master Admin Vehicle

</div>

<?php endif; ?>


</div>


<div>

<?php if ($car['approval_status'] === 'approved'): ?>

<span class="status-approved">
    APPROVED
</span>

<?php elseif ($car['approval_status'] === 'pending'): ?>

<span class="status-pending">
    PENDING
</span>

<?php else: ?>

<span class="status-rejected">
    REJECTED
</span>

<?php endif; ?>


<br><br>


<?php if ($car['active']): ?>

<span class="status-active">
    ACTIVE
</span>

<?php else: ?>

<span class="status-inactive">
    INACTIVE
</span>

<?php endif; ?>

</div>


</div>


<?php if ($car['description']): ?>

<p class="muted">

<?=htmlspecialchars(
    $car['description']
)?>

</p>

<?php endif; ?>


<div class="admin-actions">


<?php if (
    $car['approval_status'] === 'approved'
    && $car['active']
): ?>


<a
    href="index.php?car=<?=urlencode(
        $car['public_code']
    )?>"
    target="_blank"
>
    👁 Visitor
</a>


<a
    href="operator.php?car=<?=urlencode(
        $car['public_code']
    )?>"
    target="_blank"
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


<?php else: ?>

<div
    style="
        grid-column:1/-1;
        padding:10px;
        text-align:center;
        background:#fff7ed;
        color:#9a3412;
        border-radius:9px;
        font-size:13px;
        font-weight:700;
    "
>
    🔒 Vehicle must be approved and active
</div>

<?php endif; ?>


<a
    href="admin.php?edit=<?=intval(
        $car['id']
    )?>"
>
    ✏️ Edit
</a>


<form
    method="post"
    onsubmit="
        return confirm(
            'Change this vehicle status?'
        )
    "
>

<input
    type="hidden"
    name="action"
    value="toggle"
>


<input
    type="hidden"
    name="id"
    value="<?=intval(
        $car['id']
    )?>"
>


<button
    type="submit"
    class="toggle"
>

<?= $car['active']
    ? '⏸ Disable'
    : '▶ Enable'
?>

</button>

</form>


<form
    method="post"
    onsubmit="
        return confirm(
            'DELETE this vehicle permanently? This cannot be undone.'
        )
    "
>

<input
    type="hidden"
    name="action"
    value="delete"
>


<input
    type="hidden"
    name="id"
    value="<?=intval(
        $car['id']
    )?>"
>


<button
    type="submit"
    class="delete"
>
    🗑️ Delete
</button>

</form>


</div>

</div>


<?php endforeach; ?>


</section>


<!--
|--------------------------------------------------------------------------
| CALL HISTORY
|--------------------------------------------------------------------------
-->

<section class="card">


<div class="section-heading">

<h2>
    📞 Recent Call History
</h2>

<span class="section-count">
    Latest 20
</span>

</div>


<?php if (!$recentCalls): ?>

<div class="history-empty">

<div style="font-size:35px;">
    📭
</div>

<p>
    No calls have been recorded yet.
</p>

</div>

<?php else: ?>


<div class="history-wrapper">

<table class="history-table">


<thead>

<tr>

<th>
    Vehicle
</th>

<th>
    Status
</th>

<th>
    Started
</th>

<th>
    Ended
</th>

<th>
    Duration
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $recentCalls
    as $call
): ?>


<?php

$status =
    strtolower(
        $call['status']
    );


$statusClass =
    'call-' .
    $status;


$durationSeconds = 0;


if (
    !empty(
        $call['started_at']
    ) &&
    !empty(
        $call['ended_at']
    )
) {

    $start =
        strtotime(
            $call['started_at']
        );

    $end =
        strtotime(
            $call['ended_at']
        );

    if (
        $start !== false &&
        $end !== false &&
        $end >= $start
    ) {

        $durationSeconds =
            $end - $start;

    }

}


$duration =
    formatDuration(
        $durationSeconds
    );

?>


<tr>


<td>

<div class="history-car">

<?=htmlspecialchars(
    $call['car_name']
)?>

</div>


<span class="history-code">

<?=htmlspecialchars(
    $call['public_code']
)?>

</span>

</td>


<td>

<span
    class="call-status <?=htmlspecialchars(
        $statusClass
    )?>"
>

<?php

if ($status === 'connected') {

    echo '🟢 Connected';

} elseif ($status === 'ended') {

    echo '🔵 Ended';

} elseif ($status === 'rejected') {

    echo '🔴 Rejected';

} elseif ($status === 'ringing') {

    echo '🟡 Ringing';

} elseif ($status === 'expired') {

    echo '⚪ Expired';

} else {

    echo htmlspecialchars(
        ucfirst($status)
    );

}

?>

</span>

</td>


<td>

<?= !empty(
    $call['started_at']
)
    ? htmlspecialchars(
        date(
            'd M Y, h:i A',
            strtotime(
                $call['started_at']
            )
        )
      )
    : '—'
?>

</td>


<td>

<?= !empty(
    $call['ended_at']
)
    ? htmlspecialchars(
        date(
            'd M Y, h:i A',
            strtotime(
                $call['ended_at']
            )
        )
      )
    : '—'
?>

</td>


<td>

<strong>

<?= $duration ?>

</strong>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>


<?php endif; ?>


</section>


</main>

</body>

</html>