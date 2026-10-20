<?php

require __DIR__ . '/config.php';

if (is_admin_logged_in()) {
    header('Location: admin.php');
    exit;
}

$message = '';
$error = '';

$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = 'Please enter your name.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 8) {

        $error = 'Password must contain at least 8 characters.';

    } elseif ($password !== $confirmPassword) {

        $error = 'Passwords do not match.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING EMAIL
        |--------------------------------------------------------------------------
        */

        $stmt = db()->prepare(
            'SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([
            $email
        ]);

        if ($stmt->fetch()) {

            $error = 'An account with this email already exists.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | CREATE ACCOUNT
            |--------------------------------------------------------------------------
            */

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = db()->prepare(
                'INSERT INTO users
                (
                    name,
                    email,
                    phone,
                    password_hash,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    "pending"
                )'
            );

            $stmt->execute([
                $name,
                $email,
                $phone,
                $passwordHash
            ]);

            $message =
                'Registration successful! Your account is waiting for admin approval.';

            $name = '';
            $email = '';
            $phone = '';
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

<title>
VAHAN SAMPARK - Register
</title>

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

    margin: 0 auto;

    padding-top: 25px;
}


/*
|--------------------------------------------------------------------------
| BRAND
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
*/

.card {

    background: #ffffff;

    border-radius: 22px;

    padding: 24px;

    box-shadow:
        0 15px 45px rgba(0,0,0,.12);
}


.card h2 {

    margin: 0 0 6px;

    font-size: 22px;
}


.card-subtitle {

    margin: 0 0 22px;

    color: #6b7280;

    font-size: 14px;

    line-height: 1.5;
}


/*
|--------------------------------------------------------------------------
| ALERTS
|--------------------------------------------------------------------------
*/

.alert {

    padding: 13px 14px;

    border-radius: 12px;

    margin-bottom: 18px;

    font-size: 14px;

    line-height: 1.5;
}


.alert-success {

    background: #dcfce7;

    color: #166534;

    border: 1px solid #bbf7d0;
}


.alert-error {

    background: #fee2e2;

    color: #991b1b;

    border: 1px solid #fecaca;
}


/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

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

    min-height: 48px;

    border: 1px solid #d1d5db;

    border-radius: 12px;

    padding: 0 14px;

    font-size: 15px;

    outline: none;

    background: #ffffff;

    color: #111827;

    transition: .2s;
}


input:focus {

    border-color: #111827;

    box-shadow:
        0 0 0 3px rgba(255,214,0,.35);
}


input::placeholder {

    color: #9ca3af;
}


.phone-note {

    margin-top: 6px;

    font-size: 11px;

    color: #6b7280;
}


/*
|--------------------------------------------------------------------------
| BUTTON
|--------------------------------------------------------------------------
*/

button {

    width: 100%;

    min-height: 50px;

    border: 0;

    border-radius: 12px;

    background: #111827;

    color: #ffffff;

    font-size: 15px;

    font-weight: 800;

    cursor: pointer;

    margin-top: 5px;
}


button:active {

    transform: scale(.98);
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

.login-link {

    text-align: center;

    margin-top: 20px;

    font-size: 14px;

    color: #6b7280;
}


.login-link a {

    color: #111827;

    font-weight: 800;

    text-decoration: none;
}


/*
|--------------------------------------------------------------------------
| SECURITY
|--------------------------------------------------------------------------
*/

.security {

    margin-top: 18px;

    padding: 13px;

    background: #f9fafb;

    border-radius: 12px;

    font-size: 12px;

    line-height: 1.5;

    color: #6b7280;

    text-align: center;
}


@media (min-width: 600px) {

    body {

        padding: 40px 20px;

    }

    .page {

        padding-top: 20px;

    }

    .card {

        padding: 30px;

    }

}

</style>

</head>


<body>

<div class="page">


<!-- BRAND -->

<div class="brand">

<div class="brand-mark">
    🚗
</div>

<h1>
    VAHAN SAMPARK
</h1>

<p>
    Smart vehicle communication system
</p>

</div>


<!-- CARD -->

<div class="card">

<h2>
    Create Owner Account
</h2>

<p class="card-subtitle">
    Register your account to manage your vehicles
    and receive private browser calls.
</p>


<?php if ($message): ?>

<div class="alert alert-success">

    ✅

    <?=htmlspecialchars($message)?>

    <br><br>

    <strong>
        Please wait for admin approval before logging in.
    </strong>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-error">

    ❌

    <?=htmlspecialchars($error)?>

</div>

<?php endif; ?>


<?php if (!$message): ?>


<form method="post">


<div class="form-group">

<label for="name">
    Full Name
</label>

<input
    id="name"
    type="text"
    name="name"
    placeholder="Rahul Sharma"
    value="<?=htmlspecialchars($name)?>"
    autocomplete="name"
    required
>

</div>


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

<label for="phone">
    Phone Number
</label>

<input
    id="phone"
    type="tel"
    name="phone"
    placeholder="+91 98765 43210"
    value="<?=htmlspecialchars($phone)?>"
    autocomplete="tel"
>

<div class="phone-note">
    Used only for account/contact purposes.
</div>

</div>


<div class="form-group">

<label for="password">
    Password
</label>

<input
    id="password"
    type="password"
    name="password"
    placeholder="Minimum 8 characters"
    autocomplete="new-password"
    minlength="8"
    required
>

</div>


<div class="form-group">

<label for="confirm_password">
    Confirm Password
</label>

<input
    id="confirm_password"
    type="password"
    name="confirm_password"
    placeholder="Enter password again"
    autocomplete="new-password"
    minlength="8"
    required
>

</div>


<button type="submit">
    Create Owner Account
</button>


</form>

<?php endif; ?>


<div class="login-link">

Already have an account?

<a href="owner_login.php">
    Login
</a>

</div>


<div class="security">

🔒 Your password is securely encrypted.
<br>
Your account will be activated after admin approval.

</div>

</div>

</div>

</body>

</html>