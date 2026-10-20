const CACHE_NAME =
    'vahan-sampark-static-v2';


const STATIC_FILES = [
    './pwa.js',
    './style.css',
    './icon-192.png',
    './icon-512.png'
];


/*
|--------------------------------------------------------------------------
| INSTALL
|--------------------------------------------------------------------------
*/

self.addEventListener(
    'install',
    event => {

        event.waitUntil(

            caches.open(
                CACHE_NAME
            ).then(
                cache =>
                    cache.addAll(
                        STATIC_FILES
                    )
            )

        );

        self.skipWaiting();
    }
);


/*
|--------------------------------------------------------------------------
| ACTIVATE
|--------------------------------------------------------------------------
*/

self.addEventListener(
    'activate',
    event => {

        event.waitUntil(

            caches.keys().then(
                cacheNames =>
                    Promise.all(
                        cacheNames
                            .filter(
                                name =>
                                    name !==
                                    CACHE_NAME
                            )
                            .map(
                                name =>
                                    caches.delete(
                                        name
                                    )
                            )
                    )
            )

        );

        self.clients.claim();
    }
);


/*
|--------------------------------------------------------------------------
| PUSH EVENT
|--------------------------------------------------------------------------
*/

self.addEventListener(
    'push',
    event => {

        let data = {};

        try {

            data =
                event.data
                    ? event.data.json()
                    : {};

        } catch (error) {

            console.error(
                'Push payload error:',
                error
            );
        }


        const title =
            data.title ||
            '🚗 VAHAN SAMPARK';


        const options = {

            body:
                data.body ||
                'Incoming vehicle call',

            icon:
                data.icon ||
                './icon-192.png',

            badge:
                data.badge ||
                './icon-192.png',

            tag:
                data.tag ||
                'vahan-sampark-incoming-call',

            requireInteraction:
                data.requireInteraction !== false,

            vibrate:
                data.vibrate ||
                [
                    200,
                    100,
                    200,
                    100,
                    400
                ],

            data:
                data.data || {}
        };


        event.waitUntil(

            self.registration
                .showNotification(
                    title,
                    options
                )

        );
    }
);


/*
|--------------------------------------------------------------------------
| NOTIFICATION CLICK
|--------------------------------------------------------------------------
*/

self.addEventListener(
    'notificationclick',
    event => {

        event.notification.close();


        const notificationData =
            event.notification.data || {};


        const targetUrl =
            notificationData.url ||
            './operator.php';


        event.waitUntil(

            clients
                .matchAll({
                    type: 'window',
                    includeUncontrolled: true
                })
                .then(clientList => {

                    /*
                     * First try to find the
                     * exact operator page.
                     */

                    for (
                        const client
                        of clientList
                    ) {

                        if (
                            client.url ===
                                targetUrl &&
                            'focus' in client
                        ) {

                            return client.focus();
                        }
                    }


                    /*
                     * Otherwise focus
                     * an existing VAHAN SAMPARK
                     * browser window.
                     */

                    for (
                        const client
                        of clientList
                    ) {

                        if (
                            'focus' in client
                        ) {

                            return client.focus();
                        }
                    }


                    /*
                     * No window exists.
                     * Open operator page.
                     */

                    if (
                        clients.openWindow
                    ) {

                        return clients.openWindow(
                            targetUrl
                        );
                    }

                })

        );
    }
);