'use strict';

const fs = require('fs');
const path = require('path');

const LOG_DIR = path.resolve(__dirname, '..', 'logs');

if (!fs.existsSync(LOG_DIR)) {
    fs.mkdirSync(LOG_DIR, { recursive: true });
}

/**
 * Writes one JSON line per log entry to nodeapp/logs/{category}.log —
 * same shape/convention as App\Core\Logger on the PHP side, so admin
 * tooling built later can read both with one parser.
 */
function log(level, category, message, context = {}) {
    const entry = {
        timestamp: new Date().toISOString(),
        level,
        category,
        message,
        context,
    };

    const line = JSON.stringify(entry) + '\n';

    fs.appendFile(path.join(LOG_DIR, `${category}.log`), line, (err) => {
        if (err) {
            // Last-resort fallback so a logging failure is never silent
            // and never crashes the realtime server.
            // eslint-disable-next-line no-console
            console.error('[logger] failed to write log file:', err.message);
        }
    });

    // Also echo to stdout/stderr for `pm2 logs` / process manager visibility.
    if (level === 'error' || level === 'critical') {
        // eslint-disable-next-line no-console
        console.error(`[${category}] ${message}`, context);
    } else {
        // eslint-disable-next-line no-console
        console.log(`[${category}] ${message}`);
    }
}

module.exports = {
    info: (category, message, context) => log('info', category, message, context),
    warning: (category, message, context) => log('warning', category, message, context),
    error: (category, message, context) => log('error', category, message, context),
};
