'use strict';

const { Server } = require('socket.io');
const config = require('./config');
const { verifyAccessToken } = require('./authentication/verifyJwt');
const { registerConnectionHandlers } = require('./handlers/connectionHandlers');
const logger = require('./utils/logger');

/**
 * Creates and configures the Socket.IO server bound to the given HTTP
 * server. Every connection must present a valid JWT (issued by the PHP
 * app) as `auth.token` in the handshake — there is no anonymous/guest
 * realtime access.
 *
 * @param {import('http').Server} httpServer
 * @returns {import('socket.io').Server}
 */
function createSocketServer(httpServer) {
    const io = new Server(httpServer, {
        cors: {
            origin: config.corsOrigin,
            credentials: true,
        },
        // Heartbeat tuning per PROJECT.md §Heartbeat — detect dead
        // connections without being trigger-happy on mobile networks.
        pingInterval: 25000,
        pingTimeout: 20000,
    });

    io.use((socket, next) => {
        const token = socket.handshake.auth && socket.handshake.auth.token;
        const payload = verifyAccessToken(token);

        if (payload === null) {
            logger.warning('realtime', 'Từ chối kết nối socket: token không hợp lệ.', {
                socketId: socket.id,
            });

            return next(new Error('UNAUTHENTICATED'));
        }

        socket.data.auth = {
            userId: payload.sub,
            userUuid: payload.uuid,
            roles: Array.isArray(payload.roles) ? payload.roles : [],
        };

        next();
    });

    io.on('connection', (socket) => {
        registerConnectionHandlers(io, socket);
    });

    return io;
}

module.exports = { createSocketServer };
