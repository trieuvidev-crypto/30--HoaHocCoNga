'use strict';

/**
 * Tracks connected sockets per user for presence (online/offline status,
 * REALTIME ONLINE STATUS in PROJECT.md) and for the admin realtime
 * dashboard's connection count. In-memory only — acceptable for a
 * single-process Socket.IO deployment on shared cPanel hosting per
 * MASTER_CLAUDE_RULES.md ("Do not require Redis"). If the platform ever
 * needs multiple Socket.IO instances, this must be replaced with a
 * shared store — documented here so that future work isn't a surprise.
 */
class PresenceTracker {
    constructor() {
        /** @type {Map<string, Set<string>>} userUuid -> set of socket ids */
        this.userSockets = new Map();
    }

    addConnection(userUuid, socketId) {
        if (!this.userSockets.has(userUuid)) {
            this.userSockets.set(userUuid, new Set());
        }

        this.userSockets.get(userUuid).add(socketId);

        return this.userSockets.get(userUuid).size === 1; // true if user just came online
    }

    removeConnection(userUuid, socketId) {
        const sockets = this.userSockets.get(userUuid);

        if (!sockets) {
            return false;
        }

        sockets.delete(socketId);

        if (sockets.size === 0) {
            this.userSockets.delete(userUuid);

            return true; // true if user just went fully offline
        }

        return false;
    }

    isOnline(userUuid) {
        return this.userSockets.has(userUuid);
    }

    totalConnections() {
        let total = 0;

        for (const sockets of this.userSockets.values()) {
            total += sockets.size;
        }

        return total;
    }

    onlineUserCount() {
        return this.userSockets.size;
    }
}

module.exports = new PresenceTracker();
