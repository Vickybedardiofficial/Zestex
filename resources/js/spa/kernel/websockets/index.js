import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.ZESTEXBRConnected = false;
window.Pusher = Pusher;
window.Echo = Echo;
Pusher.logToConsole = import.meta.env.PUSHER_DEBUG_CONSOLE;
const REVERB_CONNECTION_STATUS = import.meta.env.VITE_REVERB_CONNECTION_STATUS;

try {
    if (REVERB_CONNECTION_STATUS == 'on') {
        window.ZESTEXBRD = new Echo({
            namespace: 'null',
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            cluster: false
        });

        window.ZESTEXBRD.connector.pusher.connection.bind('connected', function() {
            console.log('📶 Websockets connection is established.');

            window.ZESTEXBRConnected = true;
        });
    }

    else {
        console.info("📶 Websockets connection is disabled. Please configure your broadcaster server and enable Reverb connection in your app settings. (Zestex)");
    }
}

catch (error) {
    console.log(error);
}