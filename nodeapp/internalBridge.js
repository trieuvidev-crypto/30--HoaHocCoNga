'use strict';

const http = require('http');
const crypto = require('crypto');
const config = require('./config');
const rooms = require('./rooms/roomNames');
const logger = require('./utils/logger');

/**
 * A tiny internal HTTP server, bound to 127.0.0.1 only, that the PHP
 * application calls to ask Node to broadcast something over Socket.IO
 * (e.g. after NotificationService::notify() creates a notification row,
 * PHP POSTs here so it appears in the browser instantly). This is the
 * ONLY way business events reach Socket.IO — Node never reads MySQL
 * directly and never decides what "should" be broadcast, it just relays
 * exactly what PHP tells it to, per REALTIME ARCHITECTURE in PROJECT.md.
 *
 * Authentication: a shared secret (NODE_INTERNAL_SECRET in .env) must be
 * present in the `X-Internal-Secret` header. Binding to 127.0.0.1 means
 * this is not reachable from the public internet even without the
 * secret, but the secret is required anyway (defense in depth).
 *
 * @param {import('socket.io').Server} io
 * @returns {import('http').Server}
 */
function createInternalBridge(io) {
    const server = http.createServer((req, res) => {
        if (req.method !== 'POST') {
            res.writeHead(405).end();

            return;
        }

        const providedSecret = req.headers['x-internal-secret'] || '';

        if (config.internalSecret === '' || !timingSafeEqualStrings(providedSecret, config.internalSecret)) {
            logger.warning('realtime', 'Từ chối request nội bộ: sai secret.', { path: req.url });
            res.writeHead(401).end();

            return;
        }

        let body = '';

        req.on('data', (chunk) => {
            body += chunk;

            // Defensive cap — this endpoint only ever carries small
            // notification/event payloads, never large data.
            if (body.length > 1_000_000) {
                req.destroy();
            }
        });

        req.on('end', () => {
            try {
                const payload = JSON.parse(body || '{}');
                handleBroadcastRequest(io, req.url, payload);
                res.writeHead(200, { 'Content-Type': 'application/json' }).end(JSON.stringify({ success: true }));
            } catch (err) {
                logger.error('realtime', 'Lỗi xử lý request nội bộ.', { error: err.message });
                res.writeHead(400, { 'Content-Type': 'application/json' }).end(JSON.stringify({ success: false }));
            }
        });
    });

    server.listen(config.internalBridgePort, '127.0.0.1', () => {
        logger.info('realtime', `Internal bridge listening on 127.0.0.1:${config.internalBridgePort}`);
    });

    return server;
}

/**
 * Routes:
 *   POST /broadcast/user    { userUuid, event, data }
 *   POST /broadcast/course  { courseUuid, event, data }
 *   POST /broadcast/admin   { event, data }
 */
function handleBroadcastRequest(io, url, payload) {
    switch (url) {
        case '/broadcast/user':
            if (payload.userUuid && payload.event) {
                io.to(rooms.userRoom(payload.userUuid)).emit(payload.event, payload.data || {});
            }
            break;

        case '/broadcast/course':
            if (payload.courseUuid && payload.event) {
                io.to(rooms.courseRoom(payload.courseUuid)).emit(payload.event, payload.data || {});
            }
            break;

        case '/broadcast/admin':
            if (payload.event) {
                io.to(rooms.ADMIN).emit(payload.event, payload.data || {});
            }
            break;

        default:
            logger.warning('realtime', 'Route nội bộ không xác định.', { url });
    }
}

function timingSafeEqualStrings(a, b) {
    const bufA = Buffer.from(String(a));
    const bufB = Buffer.from(String(b));

    if (bufA.length !== bufB.length) {
        return false;
    }

    return crypto.timingSafeEqual(bufA, bufB);
}

module.exports = { createInternalBridge };
