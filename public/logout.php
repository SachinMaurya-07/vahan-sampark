<?php

require __DIR__ . '/config.php';

admin_logout();

header('Location: login.php');

exit;