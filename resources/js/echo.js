import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;

// A build without the VITE_REVERB_* variables (they are inlined at build time)
// would otherwise hand pusher-js an undefined host and let it retry a
// connection that never resolves. Fail loudly and run without live updates
// instead — every consumer accesses Echo optionally.
if (!reverbKey || !reverbHost) {
  console.warn(
    'Reverb is not configured (VITE_REVERB_APP_KEY / VITE_REVERB_HOST missing at build time) — live updates are disabled.'
  );

  window.Echo = null;
} else {
  window.Echo = new Echo({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbHost,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
  });
}
