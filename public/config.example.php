<?php

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

const DB_HOST = '127.0.0.1';
const DB_NAME = 'your_database_name';
const DB_USER = 'your_database_user';
const DB_PASS = 'replace_with_a_strong_database_password';


/*
|--------------------------------------------------------------------------
| SIGNALING SERVER
|--------------------------------------------------------------------------
|
| Replace this with your actual Node.js signaling server URL.
|
*/

const SIGNAL_URL = 'https://signal.example.com/';


/*
|--------------------------------------------------------------------------
| WEBRTC ICE SERVERS
|--------------------------------------------------------------------------
*/

const ICE_SERVERS = [

    [
        'urls' => 'stun:stun.l.google.com:19302'
    ]

];


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        $pdo = new PDO(
            'mysql:host=' . DB_HOST .
            ';dbname=' . DB_NAME .
            ';charset=utf8mb4',

            DB_USER,
            DB_PASS,

            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }

    return $pdo;
}


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function json_response(
    array $data,
    int $status = 200
): never {

    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
*/

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN LOGIN
|--------------------------------------------------------------------------
*/

function require_admin(): void
{
    if (!is_admin_logged_in()) {

        header('Location: login.php');

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| ADMIN LOGOUT
|--------------------------------------------------------------------------
*/

function admin_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}