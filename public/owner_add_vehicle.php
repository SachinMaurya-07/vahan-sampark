<?php

require __DIR__ . '/config.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: owner_login.php');
    exit;
}

$ownerId = (int) $_SESSION['owner_id'];

$stmt = db()->prepare(
    'SELECT id, name, email, status
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$stmt->execute([$ownerId]);

$owner = $stmt->fetch();

if (!$owner || $owner['status'] !== 'approved') {
    session_unset();
    header('Location: owner_login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Vehicle name is required.';
    } elseif (mb_strlen($name) > 120) {
        $error = 'Vehicle name is too long.';
    } elseif (mb_strlen($description) > 255) {
        $error = 'Description is too long.';
    } else {

        try {

            // Generate unique public QR code
            do {
                $publicCode = strtoupper(bin2hex(random_bytes(5)));

                $check = db()->prepare(
                    'SELECT id
                     FROM cars
                     WHERE public_code = ?
                     LIMIT 1'
                );

                $check->execute([$publicCode]);

            } while ($check->fetch());

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
                    ?,
                    ?,
                    ?,
                    ?,
                    0,
                    "pending"
                )'
            );

            $stmt->execute([
                $ownerId,
                $publicCode,
                $name,
                $description !== '' ? $description : null
            ]);

            $success = 'Vehicle submitted successfully. Waiting for admin approval.';

            $_POST = [];

        } catch (Throwable $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Vehicle | VAHAN SAMPARK</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f5f5;
    color: #111;
}

.header {
    background: #ffd400;
    padding: 18px 20px;
    border-bottom: 3px solid #111;
}

.header-inner {
    max-width: 650px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.logo {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: 1px;
}

.back {
    background: #111;
    color: #fff;
    text-decoration: none;
    padding: 9px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: bold;
}

.container {
    width: 100%;
    max-width: 650px;
    margin: auto;
    padding: 25px 16px 50px;
}

.card {
    background: #fff;
    border: 2px solid #111;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 5px 0 #111;
}

h1 {
    margin: 0 0 8px;
    font-size: 27px;
}

.subtitle {
    margin: 0 0 25px;
    color: #666;
    font-size: 14px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-weight: 800;
    font-size: 14px;
}

input,
textarea {
    width: 100%;
    border: 2px solid #111;
    border-radius: 10px;
    padding: 13px;
    font-size: 15px;
    outline: none;
    margin-bottom: 18px;
    background: #fff;
}

input:focus,
textarea:focus {
    border-color: #ffd400;
    box-shadow: 0 0 0 3px rgba(255, 212, 0, .25);
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.submit {
    width: 100%;
    border: 2px solid #111;
    border-radius: 10px;
    padding: 14px;
    background: #ffd400;
    color: #111;
    font-size: 16px;
    font-weight: 900;
    cursor: pointer;
}

.submit:hover {
    background: #ffca00;
}

.alert {
    border: 2px solid #111;
    border-radius: 10px;
    padding: 13px;
    margin-bottom: 18px;
    font-size: 14px;
    font-weight: bold;
}

.error {
    background: #ffdede;
}

.success {
    background: #dcffd9;
}

.info {
    background: #fff8c7;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 13px;
    margin-bottom: 20px;
    font-size: 13px;
    line-height: 1.5;
}

@media (max-width: 480px) {

    .header {
        padding: 15px;
    }

    .logo {
        font-size: 18px;
    }

    .container {
        padding: 20px 12px 40px;
    }

    .card {
        padding: 18px;
    }

    h1 {
        font-size: 24px;
    }
}

</style>
</head>

<body>

<header class="header">

    <div class="header-inner">

        <div class="logo">
            VAHAN SAMPARK
        </div>

        <a href="owner_dashboard.php" class="back">
            ← Dashboard
        </a>

    </div>

</header>


<main class="container">

    <div class="card">

        <h1>Add New Vehicle</h1>

        <p class="subtitle">
            Add your vehicle and submit it for admin approval.
        </p>


        <?php if ($error): ?>

            <div class="alert error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert success">
                <?= htmlspecialchars($success) ?>
            </div>

            <div class="info">

                Your vehicle has been submitted successfully.

                <br><br>

                The admin will review your vehicle before the QR
                calling system becomes active.

            </div>

            <a
                href="owner_dashboard.php"
                class="submit"
                style="display:block;text-align:center;text-decoration:none;"
            >
                Back to Dashboard
            </a>

        <?php else: ?>

            <div class="info">

                <strong>How it works:</strong><br><br>

                1. Enter your vehicle details.<br>
                2. Submit the vehicle.<br>
                3. Admin reviews your request.<br>
                4. After approval, your QR code becomes active.<br>
                5. You can then print the QR card for your vehicle.

            </div>


            <form method="POST">

                <label for="name">
                    Vehicle Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Example: My Honda City"
                    maxlength="120"
                    required
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                >


                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="255"
                    placeholder="Example: White Honda City - UP 16 AB 1234"
                ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>


                <button
                    type="submit"
                    class="submit"
                >
                    SUBMIT VEHICLE
                </button>

            </form>

        <?php endif; ?>

    </div>

</main>

</body>
</html>