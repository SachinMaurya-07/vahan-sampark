<?php

require __DIR__ . '/config.php';

if (is_admin_logged_in()) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {

        $stmt = db()->prepare(
            'SELECT * FROM admins WHERE username = ? LIMIT 1'
        );

        $stmt->execute([$username]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: admin.php');
            exit;

        } else {
            $error = 'Invalid username or password.';
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

<title>Admin Login</title>

<link rel="stylesheet" href="style.css">

<style>

.login-wrapper {
    width: 100%;
    max-width: 430px;
    margin: 0 auto;
}

.login-logo {
    width: 70px;
    height: 70px;
    margin: 0 auto 18px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 20px;

    background: #111827;

    font-size: 32px;
}

.login-title {
    text-align: center;
    margin-bottom: 8px;
}

.login-subtitle {
    text-align: center;
    color: #6b7280;
    margin-bottom: 25px;
}

.error-box {
    background: #fee2e2;
    color: #991b1b;

    padding: 12px 14px;

    border-radius: 10px;

    margin-bottom: 16px;

    font-size: 14px;
}

.login-button {
    width: 100%;
    margin-top: 10px;
}

</style>

</head>

<body>

<main class="shell">

<div class="login-wrapper">

<div class="login-logo">
    🚗
</div>

<h1 class="login-title">
    Car WebCall
</h1>

<p class="login-subtitle">
    Admin Panel Login
</p>

<section class="card">

<?php if ($error): ?>

<div class="error-box">
    <?=htmlspecialchars($error)?>
</div>

<?php endif; ?>

<form method="post">

<label>
    Username

    <input
        type="text"
        name="username"
        autocomplete="username"
        required
    >

</label>

<label>
    Password

    <input
        type="password"
        name="password"
        autocomplete="current-password"
        required
    >

</label>

<button
    type="submit"
    class="primary login-button"
>
    🔐 Login
</button>

</form>

</section>

</div>

</main>

</body>

</html>