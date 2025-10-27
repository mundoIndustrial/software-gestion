import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

try {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: '4386370bd0253398af16', // Tu Pusher key
        cluster: 'us2', // Tu cluster
        wsHost: `ws-us2.pusherapp.com`,
        wsPort: 80,
        wssPort: 443,
        forceTLS: true,
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel, options) => {
            return {
                authorize: (socketId, callback) => {
                    axios.post('/broadcasting/auth', {
                        socket_id: socketId,
                        channel_name: channel.name
                    })
                    .then(response => {
                        callback(false, response.data);
                    })
                    .catch(error => {
                        callback(true, error);
                    });
                }
            };
        },
    });

    console.log('✅ Laravel Echo configurado correctamente');
} catch (error) {
    console.error('❌ Error configurando Laravel Echo:', error);
}
