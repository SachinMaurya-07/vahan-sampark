# VAHAN Sampark

VAHAN Sampark is a QR-based vehicle contact project. A visitor scans a QR code displayed on a vehicle and can initiate a browser-based audio call with the vehicle operator without sharing their phone number.

## Features

- PHP pages for visitor contact, owner registration/login, vehicle management, and QR cards
- MySQL database schema
- Node.js + Socket.IO signaling server for WebRTC calls
- Browser-based audio calling using WebRTC
- Progressive Web App assets and service workers
- Optional web-push subscription endpoints (configure and test keys before use)

## Technology

- PHP 8+ (recommended; verify compatibility with your hosting environment)
- MySQL or MariaDB
- Node.js 18+
- Socket.IO
- HTTPS in production for microphone and service-worker features

## Repository layout

- `public/` — web application files
- `public/database/schema.sql` — database schema (import this into a new database)
- `server/` — Node.js signaling service
- `config.example.php` — example for the root-level PHP entry points
- `public/config.example.php` — example configuration for the public application

## Setup

1. Create a MySQL/MariaDB database and import `public/database/schema.sql`.
2. Copy `public/config.example.php` to `public/config.php`, then set your database credentials and signaling URL.
3. Copy `config.example.php` to `config.php` if you use the root-level `operator.php` entry point. Set the same database/signaling values.
4. Configure the web server document root and routing to serve the intended PHP entry points. Review the included redirects and paths before deployment.
5. In `server/`, install dependencies and start the signaling server:

   ```bash
   npm install
   npm start
   ```

6. Put the signaling service behind HTTPS/WSS and set the matching URL in the PHP configuration.
7. Import the schema, configure an administrator account, and test vehicle registration, QR generation, and calls in a staging environment before using it publicly.

## Security checklist before deployment

- Never commit `config.php`, `.env`, database passwords, private keys, or real user data.
- Use unique, strong database and administrator credentials.
- Rotate any credential that was previously committed or shared.
- Configure HTTPS/WSS and appropriate CORS origins; do not leave signaling access open to untrusted origins in production.
- Add rate limits and authorization checks to call/signaling events before exposing the service publicly.
- Configure TURN for more reliable calls across mobile networks and restrictive NATs.
- Test push-notification key configuration separately; do not commit private VAPID keys.
- Do not expose SQL dumps containing real user records. The included schema should contain structure only.

## Notes

This repository is a project starter, not a guarantee of production security. Review authentication, signaling authorization, push-notification configuration, and hosting paths before public deployment.
