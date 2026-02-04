const { default: makeWASocket, DisconnectReason, useMultiFileAuthState, makeCacheableSignalKeyStore, PHONENUMBER_MCC } = require('@whiskeysockets/baileys');
const express = require('express');
const QRCode = require('qrcode');
const pino = require('pino');
const axios = require('axios');
const fs = require('fs');
const path = require('path');
const readline = require('readline');
require('dotenv').config();

const app = express();
app.use(express.json());

const PORT = process.env.WA_SERVER_PORT || 3000;
const API_BASE_URL = process.env.API_BASE_URL || 'http://localhost:8000';

const connections = new Map();
const logger = pino({ level: 'info' });

const sessionsDir = path.join(__dirname, 'sessions');
if (!fs.existsSync(sessionsDir)) {
    fs.mkdirSync(sessionsDir, { recursive: true });
}

async function createConnection(userId, phoneNumber, usePairingCode = false, mobileNumber = null) {
    const sessionPath = path.join(sessionsDir, `session_${userId}_${phoneNumber}`);
    const key = `${userId}_${phoneNumber}`;

    // Clean old session
    if (!connections.has(key) && fs.existsSync(sessionPath)) {
        console.log(`[${userId}_${phoneNumber}] Cleaning old session...`);
        fs.rmSync(sessionPath, { recursive: true, force: true });
    }

    if (!fs.existsSync(sessionPath)) {
        fs.mkdirSync(sessionPath, { recursive: true });
    }

    const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

    const sock = makeWASocket({
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, pino({ level: 'silent' })),
        },
        printQRInTerminal: !usePairingCode,
        logger: pino({ level: 'silent' }),
        browser: ['Chrome (Linux)', '', ''],
        mobile: false,
    });

    const connectionData = {
        sock,
        userId,
        phoneNumber,
        qr: null,
        pairingCode: null,
        status: 'connecting',
    };

    connections.set(key, connectionData);

    // Handle pairing code
    if (usePairingCode && !state.creds.registered && mobileNumber) {
        console.log(`[${userId}_${phoneNumber}] Requesting pairing code for ${mobileNumber}...`);
        try {
            const code = await sock.requestPairingCode(mobileNumber);
            connectionData.pairingCode = code;
            connectionData.status = 'pairing_code_ready';
            console.log(`[${userId}_${phoneNumber}] Pairing code: ${code}`);

            await axios.post(`${API_BASE_URL}/api/whatsapp/qr-update`, {
                user_id: userId,
                phone_number: phoneNumber,
                pairing_code: code,
                status: 'pairing_code_ready',
            }).catch(err => console.error('Backend notification failed:', err.message));
        } catch (error) {
            console.error(`[${userId}_${phoneNumber}] Pairing code error:`, error.message);
        }
    }

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        console.log(`[${userId}_${phoneNumber}] Update:`, {
            connection,
            hasQR: !!qr,
            error: lastDisconnect?.error?.message
        });

        if (qr && !usePairingCode) {
            try {
                connectionData.qr = await QRCode.toDataURL(qr);
                connectionData.status = 'qr_ready';
                console.log(`[${userId}_${phoneNumber}] QR generated`);

                await axios.post(`${API_BASE_URL}/api/whatsapp/qr-update`, {
                    user_id: userId,
                    phone_number: phoneNumber,
                    qr_code: connectionData.qr,
                    status: 'qr_ready',
                }).catch(err => console.error('Backend failed:', err.message));
            } catch (error) {
                console.error(`[${userId}_${phoneNumber}] QR error:`, error.message);
            }
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            console.log(`[${userId}_${phoneNumber}] Closed. Reconnect: ${shouldReconnect}`);

            if (shouldReconnect) {
                setTimeout(() => createConnection(userId, phoneNumber, usePairingCode, mobileNumber), 5000);
            } else {
                connections.delete(key);
                await axios.post(`${API_BASE_URL}/api/whatsapp/status-update`, {
                    user_id: userId,
                    phone_number: phoneNumber,
                    status: 'disconnected',
                }).catch(() => { });
            }
        } else if (connection === 'open') {
            connectionData.status = 'connected';
            connectionData.qr = null;
            connectionData.pairingCode = null;
            console.log(`[${userId}_${phoneNumber}] ✅ CONNECTED!`);

            await axios.post(`${API_BASE_URL}/api/whatsapp/status-update`, {
                user_id: userId,
                phone_number: phoneNumber,
                status: 'connected',
                phone_info: sock.user,
            }).catch(() => { });
        }
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('messages.upsert', async ({ messages }) => {
        for (const msg of messages) {
            if (!msg.message || msg.key.fromMe) continue;
            const from = msg.key.remoteJid;
            const text = msg.message.conversation || msg.message.extendedTextMessage?.text || '';

            await axios.post(`${API_BASE_URL}/api/whatsapp/incoming-message`, {
                user_id: userId,
                phone_number: phoneNumber,
                from,
                message: text,
                message_id: msg.key.id,
            }).catch(() => { });
        }
    });

    return connectionData;
}

// Routes
app.post('/connect', async (req, res) => {
    const { user_id, phone_number, use_pairing_code, mobile_number } = req.body;
    const key = `${user_id}_${phone_number}`;

    if (connections.has(key)) {
        const conn = connections.get(key);
        return res.json({ status: conn.status, qr: conn.qr, pairing_code: conn.pairingCode });
    }

    try {
        const conn = await createConnection(user_id, phone_number, use_pairing_code, mobile_number);
        res.json({ status: conn.status, qr: conn.qr, pairing_code: conn.pairingCode });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.get('/qr/:user_id/:phone_number', (req, res) => {
    const conn = connections.get(`${req.params.user_id}_${req.params.phone_number}`);
    if (!conn) return res.status(404).json({ error: 'Not found' });
    res.json({ status: conn.status, qr: conn.qr, pairing_code: conn.pairingCode });
});

app.post('/send-message', async (req, res) => {
    const { user_id, phone_number, to, message } = req.body;
    const conn = connections.get(`${user_id}_${phone_number}`);

    if (!conn || conn.status !== 'connected') {
        return res.status(400).json({ error: 'Not connected' });
    }

    try {
        const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
        await conn.sock.sendMessage(jid, { text: message });
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.post('/disconnect', async (req, res) => {
    const { user_id, phone_number } = req.body;
    const key = `${user_id}_${phone_number}`;
    const conn = connections.get(key);

    if (!conn) return res.status(404).json({ error: 'Not found' });

    try {
        await conn.sock.logout();
        connections.delete(key);
        fs.rmSync(path.join(sessionsDir, `session_${user_id}_${phone_number}`), { recursive: true, force: true });
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.get('/health', (req, res) => {
    res.json({ status: 'ok', connections: connections.size, uptime: process.uptime() });
});

app.listen(PORT, () => {
    console.log(`🚀 WhatsApp Server on port ${PORT}`);
    console.log(`💡 TIP: If QR doesn't work, try pairing code method`);
});
