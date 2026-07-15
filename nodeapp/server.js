'use strict';

const http = require('http');
const config = require('./config');
const { createSocketServer } = require('./socket');
const { createInternalBridge } = require('./internalBridge');
const logger = require('./utils/logger');

if (!config.jwtSecret) {
    logger.error('realtime', 'JWT_SECRET chưa được cấu hình trong .env — server sẽ từ chối mọi kết nối.');
}

if (!config.internalSecret) {
    logger.error('realtime', 'NODE_INTERNAL_SECRET chưa được cấu hình trong .env — cầu nối nội bộ sẽ từ chối mọi request từ PHP.');
}

// Bare HTTP server for Socket.IO to attach to. No Express — Socket.IO's
// own server is sufficient and keeps the dependency list to just
// `socket.io`, per the project's minimal-dependency principle.
const httpServer = http.createServer((req, res) => {
    res.writeHead(200, { 'Content-Type': 'text/plain' }).end('HoaHocCoNga.Com realtime gateway');
});

const io = createSocketServer(httpServer);
createInternalBridge(io);

httpServer.listen(config.socketPort, () => {
    logger.info('realtime', `Socket.IO server đang lắng nghe tại cổng ${config.socketPort}`);
});

process.on('uncaughtException', (err) => {
    logger.error('realtime', 'Uncaught exception trong realtime server.', { error: err.message, stack: err.stack });
});

process.on('unhandledRejection', (reason) => {
    logger.error('realtime', 'Unhandled promise rejection trong realtime server.', { reason: String(reason) });
});

process.on('SIGTERM', () => {
    logger.info('realtime', 'Nhận SIGTERM, đang tắt server một cách an toàn.');
    httpServer.close(() => process.exit(0));
});
