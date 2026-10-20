<?php
require __DIR__.'/config.php';
json_response(['publicKey'=>VAPID_PUBLIC_KEY]);
