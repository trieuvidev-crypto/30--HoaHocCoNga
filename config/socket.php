<?php

declare(strict_types=1);

return [
    // The internal bridge always listens on 127.0.0.1 (see nodeapp/internalBridge.js) —
    // constructed directly rather than derived from NODE_SOCKET_URL (which is the
    // public-facing Socket.IO URL and may differ, e.g. behind a reverse proxy).
    'internal_bridge_url' => 'http://127.0.0.1:' . env('NODE_INTERNAL_PORT', '3001'),
    'internal_secret' => env('NODE_INTERNAL_SECRET', ''),
    'timeout_seconds' => 2, // fire-and-forget: never let a slow/down Node process stall a PHP request
];
