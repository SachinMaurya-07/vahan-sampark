<?php

require __DIR__ . '/config.php';

unset(
    $_SESSION['owner_id'],
    $_SESSION['owner_name'],
    $_SESSION['owner_email']
);

header('Location: owner_login.php');
exit;