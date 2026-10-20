const VAPID_PUBLIC_KEY =
    'BHLHBrOiTFsCk-ratsUVSdL8djrlkbnnkFd7raQVZEq3n4cZWJBHTU9aWmGcHZl62S2U97xIP4EWgILmY7Ef4mA';


let serviceWorkerRegistration = null;
let deferredInstallPrompt = null;


/*
|--------------------------------------------------------------------------
| BASE64 → UINT8ARRAY
|--------------------------------------------------------------------------
*/

function urlBase64ToUint8Array(base64String) {

    const padding =
        '='.repeat(
            (4 - base64String.length % 4) % 4
        );

    const base64 =
        (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

    const rawData =
        window.atob(base64);

    return Uint8Array.from(
        [...rawData].map(
            char => char.charCodeAt(0)
        )
    );
}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

function setNotificationStatus(message) {

    const element =
        document.getElementById(
            'notificationStatus'
        );

    if (element) {
        element.textContent = message;
    }
}


/*
|--------------------------------------------------------------------------
| SERVICE WORKER
|--------------------------------------------------------------------------
*/

async function initPWA() {

    if (!('serviceWorker' in navigator)) {

        setNotificationStatus(
            'Service Worker is not supported.'
        );

        return null;
    }

    try {

        serviceWorkerRegistration =
            await navigator.serviceWorker.register(
                'sw.js'
            );

        console.log(
            'Service Worker registered:',
            serviceWorkerRegistration
        );

        return serviceWorkerRegistration;

    } catch (error) {

        console.error(
            'Service Worker registration failed:',
            error
        );

        setNotificationStatus(
            'Service Worker registration failed.'
        );

        return null;
    }
}


/*
|--------------------------------------------------------------------------
| ENABLE WEB PUSH
|--------------------------------------------------------------------------
*/

async function enableNotifications() {

    try {

        if (!serviceWorkerRegistration) {

            serviceWorkerRegistration =
                await navigator.serviceWorker.ready;
        }

        if (!('Notification' in window)) {

            setNotificationStatus(
                'Notifications are not supported.'
            );

            return;
        }

        const permission =
            await Notification.requestPermission();

        if (permission !== 'granted') {

            setNotificationStatus(
                'Notification permission was not granted.'
            );

            return;
        }

        /*
         * Check existing subscription.
         */

        let subscription =
            await serviceWorkerRegistration
                .pushManager
                .getSubscription();


        /*
         * Create subscription if needed.
         */

        if (!subscription) {

            subscription =
                await serviceWorkerRegistration
                    .pushManager
                    .subscribe({

                        userVisibleOnly: true,

                        applicationServerKey:
                            urlBase64ToUint8Array(
                                VAPID_PUBLIC_KEY
                            )
                    });
        }


        /*
         * Convert PushSubscription
         * into normal JSON.
         */

        const subscriptionJSON =
            subscription.toJSON();


        /*
         * Save subscription in PHP/MySQL.
         */

        const response =
            await fetch(
                'save_push_subscription.php',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    credentials: 'same-origin',

                    body: JSON.stringify(
                        subscriptionJSON
                    )
                }
            );


        const result =
            await response.json();


        if (!response.ok || !result.ok) {

            throw new Error(
                result.message ||
                'Could not save subscription'
            );
        }


        setNotificationStatus(
            '🔔 Call notifications are enabled.'
        );


        const button =
            document.getElementById(
                'enableNotificationsBtn'
            );

        if (button) {

            button.textContent =
                '✓ Notifications Enabled';

            button.disabled = true;
        }


        console.log(
            'Push subscription saved successfully'
        );

    } catch (error) {

        console.error(
            'Notification setup error:',
            error
        );

        setNotificationStatus(
            'Could not enable notifications: ' +
            error.message
        );
    }
}


/*
|--------------------------------------------------------------------------
| SHOW LOCAL NOTIFICATION
|--------------------------------------------------------------------------
|
| This is useful while the operator page is open.
|--------------------------------------------------------------------------
*/

async function showIncomingNotification(
    data = {}
) {

    try {

        if (!serviceWorkerRegistration) {
            return;
        }

        if (
            !('Notification' in window) ||
            Notification.permission !== 'granted'
        ) {
            return;
        }

        await serviceWorkerRegistration
            .showNotification(
                data.title ||
                    '🚗 Incoming Vehicle Call',
                {

                    body:
                        data.body ||
                        'Someone is trying to contact you.',

                    icon:
                        data.icon ||
                        'icon-192.png',

                    badge:
                        data.badge ||
                        'icon-192.png',

                    tag:
                        data.tag ||
                        'vahan-sampark-incoming-call',

                    requireInteraction: true,

                    vibrate: [
                        200,
                        100,
                        200,
                        100,
                        400
                    ],

                    data:
                        data.data || {}
                }
            );

    } catch (error) {

        console.error(
            'Could not show notification:',
            error
        );
    }
}


/*
|--------------------------------------------------------------------------
| CLOSE CALL NOTIFICATION
|--------------------------------------------------------------------------
*/

async function closeIncomingNotification() {

    try {

        if (!serviceWorkerRegistration) {
            return;
        }

        const notifications =
            await serviceWorkerRegistration
                .getNotifications({
                    tag:
                        'vahan-sampark-incoming-call'
                });

        notifications.forEach(
            notification =>
                notification.close()
        );

    } catch (error) {

        console.error(
            'Could not close notification:',
            error
        );
    }
}


/*
|--------------------------------------------------------------------------
| PWA INSTALL
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'beforeinstallprompt',
    event => {

        event.preventDefault();

        deferredInstallPrompt = event;

        const installButton =
            document.getElementById(
                'installPwaBtn'
            );

        if (installButton) {

            installButton.style.display =
                'block';
        }
    }
);


async function installPWA() {

    if (!deferredInstallPrompt) {

        alert(
            'PWA installation is not currently available.'
        );

        return;
    }

    deferredInstallPrompt.prompt();

    const choice =
        await deferredInstallPrompt.userChoice;

    console.log(
        'PWA install result:',
        choice.outcome
    );

    deferredInstallPrompt = null;

    const installButton =
        document.getElementById(
            'installPwaBtn'
        );

    if (installButton) {

        installButton.style.display =
            'none';
    }
}


/*
|--------------------------------------------------------------------------
| BUTTON EVENTS
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    async () => {

        await initPWA();


        const notificationButton =
            document.getElementById(
                'enableNotificationsBtn'
            );

        if (notificationButton) {

            notificationButton.addEventListener(
                'click',
                enableNotifications
            );
        }


        const installButton =
            document.getElementById(
                'installPwaBtn'
            );

        if (installButton) {

            installButton.addEventListener(
                'click',
                installPWA
            );
        }
    }
);