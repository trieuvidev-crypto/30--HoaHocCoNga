'use strict';

const rooms = require('../rooms/roomNames');
const presence = require('../services/presenceTracker');
const logger = require('../utils/logger');

/**
 * Registers the connection lifecycle + basic event handlers for one
 * socket. Every handler here only relays/broadcasts — none of them
 * decide business outcomes (e.g. whether a message is "allowed"); that
 * validation happens in PHP before the state change ever reaches here.
 *
 * @param {import('socket.io').Server} io
 * @param {import('socket.io').Socket} socket
 */
function registerConnectionHandlers(io, socket) {
    const { userId, userUuid, roles } = socket.data.auth;

    socket.join(rooms.userRoom(userUuid));

    if (roles.includes('admin') || roles.includes('super_admin')) {
        socket.join(rooms.ADMIN);
    }

    if (roles.includes('teacher')) {
        socket.join(rooms.teacherRoom(userUuid));
    }

    const justCameOnline = presence.addConnection(userUuid, socket.id);

    logger.info('realtime', 'Kết nối socket mới.', { userId, userUuid, socketId: socket.id });

    if (justCameOnline) {
        io.to(rooms.ADMIN).emit('user.online', { userUuid });
    }

    socket.on('course.join', (payload) => {
        if (payload && typeof payload.courseUuid === 'string') {
            socket.join(rooms.courseRoom(payload.courseUuid));
        }
    });

    socket.on('course.leave', (payload) => {
        if (payload && typeof payload.courseUuid === 'string') {
            socket.leave(rooms.courseRoom(payload.courseUuid));
        }
    });

    socket.on('live.join', (payload) => {
        if (payload && typeof payload.liveClassUuid === 'string') {
            socket.join(rooms.liveClassRoom(payload.liveClassUuid));
            io.to(rooms.liveClassRoom(payload.liveClassUuid)).emit('live.participant_joined', { userUuid });
        }
    });

    socket.on('live.leave', (payload) => {
        if (payload && typeof payload.liveClassUuid === 'string') {
            socket.leave(rooms.liveClassRoom(payload.liveClassUuid));
            io.to(rooms.liveClassRoom(payload.liveClassUuid)).emit('live.participant_left', { userUuid });
        }
    });

    socket.on('disconnect', (reason) => {
        const justWentOffline = presence.removeConnection(userUuid, socket.id);

        logger.info('realtime', 'Socket ngắt kết nối.', { userId, userUuid, socketId: socket.id, reason });

        if (justWentOffline) {
            io.to(rooms.ADMIN).emit('user.offline', { userUuid });
        }
    });
}

module.exports = { registerConnectionHandlers };
