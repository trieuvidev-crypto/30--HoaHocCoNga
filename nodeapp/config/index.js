'use strict';

const fs = require('fs');
const path = require('path');

/**
 * Loads the same .env file the PHP application uses (project root),
 * so both sides always agree on JWT secret, app URL, etc. No `dotenv`
 * package — this mirrors bootstrap/helpers.php's manual parser so the
 * two runtimes can never drift in how they interpret the file.
 */
function loadEnv() {
    const envPath = path.resolve(__dirname, '..', '..', '.env');

    if (!fs.existsSync(envPath)) {
        return;
    }

    const lines = fs.readFileSync(envPath, 'utf8').split(/\r?\n/);

    for (const rawLine of lines) {
        const line = rawLine.trim();

        if (line === '' || line.startsWith('#') || !line.includes('=')) {
            continue;
        }

        const eqIndex = line.indexOf('=');
        const key = line.slice(0, eqIndex).trim();
        let value = line.slice(eqIndex + 1).trim();

        value = value.replace(/^['"]|['"]$/g, '');

        if (process.env[key] === undefined) {
            process.env[key] = value;
        }
    }
}

loadEnv();

module.exports = {
    appUrl: process.env.APP_URL || 'https://hoahoconga.com',
    jwtSecret: process.env.JWT_SECRET || '',
    jwtAlgo: process.env.JWT_ALGO || 'HS256',
    internalSecret: process.env.NODE_INTERNAL_SECRET || '',
    socketPort: parseInt(process.env.SOCKET_PORT || '3000', 10),
    internalBridgePort: parseInt(process.env.NODE_INTERNAL_PORT || '3001', 10),
    corsOrigin: process.env.APP_URL || '*',
};
