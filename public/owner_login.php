<?php

require __DIR__ . '/config.php';

if (isset($_SESSION['owner_id'])) {
    header('Location: owner_dashboard.php');
    exit;
}

$error = '';

$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif ($password === '') {

        $error = 'Please enter your password.';

    } else {

        $stmt = db()->prepare(
            'SELECT *
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([
            $email
        ]);

        $user = $stmt->fetch();

        if (!$user) {

            $error = 'Invalid email or password.';

        } elseif (!password_verify($password, $user['password_hash'])) {

            $error = 'Invalid email or password.';

        } elseif ($user['status'] === 'pending') {

            $error = 'Your account is waiting for admin approval.';

        } elseif ($user['status'] === 'rejected') {

            $error = 'Your registration has been rejected by admin.';

        } elseif ($user['status'] === 'suspended') {

            $error = 'Your account has been suspended.';

        } elseif ($user['status'] !== 'approved') {

            $error = 'Your account is not active.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | OWNER LOGIN
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            $_SESSION['owner_id'] = (int)$user['id'];
            $_SESSION['owner_name'] = $user['name'];
            $_SESSION['owner_email'] = $user['email'];

            header('Location: owner_dashboard.php');
            exit;
        }
    }
}

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
    content="#ffd600"
>

<title>VAHAN SAMPARK - Owner Login</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    min-height: 100vh;

    font-family:
        Inter,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    background:
        linear-gradient(
            180deg,
            #ffd600 0%,
            #ffd600 30%,
            #f5f5f5 30%,
            #f5f5f5 100%
        );

    color: #111827;

    padding: 20px;
}

.page {

    width: 100%;

    max-width: 460px;

    margin: auto;

    padding-top: 25px;
}

/* BRAND */

.brand {

    text-align: center;

    margin-bottom: 24px;
}

.brand-mark {

    width: 68px;

    height: 68px;

    margin: 0 auto 12px;

    border-radius: 20px;

    background: #111827;

    color: #ffd600;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 31px;

    box-shadow:
        0 10px 25px rgba(0,0,0,.15);
}

.brand h1 {

    margin: 0;

    font-size: 28px;

    font-weight: 900;

    letter-spacing: -1px;
}

.brand p {

    margin: 6px 0 0;

    font-size: 14px;

    color: #4b5563;
}

/* CARD */

.card {

    background: #fff;

    border-radius: 22px;

    padding: 24px;

    box-shadow:
        0 15px 45px rgba(0,0,0,.12);
}

.card h2 {

    margin: 0 0 6px;

    font-size: 22px;
}

.subtitle {

    margin: 0 0 22px;

    color: #6b7280;

    font-size: 14px;

    line-height: 1.5;
}

/* ALERT */

.alert {

    padding: 13px 14px;

    border-radius: 12px;

    margin-bottom: 18px;

    font-size: 14px;

    line-height: 1.5;
}

.alert-error {

    background: #fee2e2;

    color: #991b1b;

    border: 1px solid #fecaca;
}

/* FORM */

.form-group {

    margin-bottom: 16px;
}

label {

    display: block;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 7px;
}

input {

    width: 100%;

    min-height: 49px;

    padding: 0 14px;

    border: 1px solid #d1d5db;

    border-radius: 12px;

    outline: none;

    font-size: 15px;

    color: #111827;

    background: #fff;
}

input:focus {

    border-color: #111827;

    box-shadow:
        0 0 0 3px rgba(255,214,0,.35);
}

input::placeholder {

    color: #9ca3af;
}

/* BUTTON */

button {

    width: 100%;

    min-height: 50px;

    border: 0;

    border-radius: 12px;

    background: #111827;

    color: #fff;

    font-size: 15px;

    font-weight: 800;

    cursor: pointer;
}

button:active {

    transform: scale(.98);
}

/* REGISTER */

.register-link {

    text-align: center;

    margin-top: 20px;

    font-size: 14px;

    color: #6b7280;
}

.register-link a {

    color: #111827;

    font-weight: 800;

    text-decoration: none;
}

/* INFO */

.info {

    margin-top: 18px;

    padding: 13px;

    border-radius: 12px;

    background: #f9fafb;

    color: #6b7280;

    text-align: center;

    font-size: 12px;

    line-height: 1.5;
}

@media (min-width: 600px) {

    body {
        padding: 40px 20px;
    }

    .card {
        padding: 30px;
    }
}

</style>

</head>

<body>

<div class="page">

    <div class="brand">

        <div class="brand-mark">
            🚗
        </div>

        <h1>
            VAHAN SAMPARK
        </h1>

        <p>
            Vehicle owner portal
        </p>

    </div>


    <div class="card">

        <h2>
            Owner Login
        </h2>

        <p class="subtitle">
            Login to manage your vehicles and receive
            private browser calls.
        </p>


        <?php if ($error): ?>

            <div class="alert alert-error">

                ❌

                <?=htmlspecialchars($error)?>

            </div>

        <?php endif; ?>


        <form method="post">

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    placeholder="rahul@example.com"
                    value="<?=htmlspecialchars($email)?>"
                    autocomplete="email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Your password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <button type="submit">
                🔐 Login
            </button>

        </form>


        <div class="register-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>


        <div class="info">

            🔒 Secure owner login

            <br>

            Your account must be approved by
            VAHAN SAMPARK admin before login.

        </div>

    </div>

</div>

</body>

</html>