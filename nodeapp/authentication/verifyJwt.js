'use strict';

const crypto = require('crypto');
const config = require('../config');

/**
 * Verifies a JWT issued by App\Services\Auth\TokenService (PHP side).
 * Hand-rolled to exactly match that implementation's base64url
 * header.payload.signature format and HMAC-SHA256 signing — no
 * `jsonwebtoken` npm dependency, so there is exactly one JWT format
 * definition in the whole codebase to keep in sync (this file mirrors
 * app/Services/Auth/TokenService.php; if that changes, this must too).
 *
 * @param {string} token
 * @returns {object|null} decoded payload if valid and not expired, else null
 */
function verifyAccessToken(token) {
    if (typeof token !== 'string' || token.split('.').length !== 3) {
        return null;
    }

    const [header, body, signature] = token.split('.');

    const expectedSignature = sign(`${header}.${body}`);

    if (!timingSafeEqual(expectedSignature, signature)) {
        return null;
    }

    let payload;

    try {
        payload = JSON.parse(base64UrlDecode(body));
    } catch {
        return null;
    }

    if (!payload || typeof payload.exp !== 'number' || payload.exp < Math.floor(Date.now() / 1000)) {
        return null;
    }

    if (payload.type !== 'access') {
        return null;
    }

    return payload;
}

function sign(data) {
    const hmac = crypto.createHmac('sha256', config.jwtSecret);
    hmac.update(data);

    return base64UrlEncode(hmac.digest());
}

function base64UrlEncode(buffer) {
    return Buffer.from(buffer)
        .toString('base64')
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

function base64UrlDecode(str) {
    let padded = str.replace(/-/g, '+').replace(/_/g, '/');
    const padLength = (4 - (padded.length % 4)) % 4;
    padded += '='.repeat(padLength);

    return Buffer.from(padded, 'base64').toString('utf8');
}

function timingSafeEqual(a, b) {
    const bufA = Buffer.from(a);
    const bufB = Buffer.from(b);

    if (bufA.length !== bufB.length) {
        return false;
    }

    return crypto.timingSafeEqual(bufA, bufB);
}

module.exports = { verifyAccessToken };
