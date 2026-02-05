const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const {
    makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    getUrlInfo
} = require('@whiskeysockets/baileys');
const qrcode = require('qrcode');
const pino = require('pino');
const fs = require('fs');
const path = require('path');
const axios = require('axios');
require('dotenv').config();

const app = express();
const port = process.env.PORT || process.env.WA_SERVER_PORT || 3000;
const API_BASE_URL = process.env.API_BASE_URL || 'http://localhost:8000';
const sessionsDir = path.join(__dirname, 'sessions');

// Middlewares
app.use(cors({ origin: '*' }));
app.use(bodyParser.json({ limit: '50mb' }));
app.use(bodyParser.urlencoded({ extended: true }));

// Health Check & Log Viewer
app.get('/health', (req, res) => res.json({ status: 'ok', uptime: process.uptime() }));
app.get('/debug/logs', (req, res) => {
    const logPath = path.join(__dirname, 'server.log');
    if (fs.existsSync(logPath)) {
        res.sendFile(logPath);
    } else {
        res.status(404).json({ error: 'Log file not found' });
    }
});

// cPanel Subfolder Fix: Strip the base path from the URL if it exists
app.use((req, res, next) => {
    const basePath = '/whatsapp-server-temp';
    if (req.url.startsWith(basePath)) {
        req.url = req.url.replace(basePath, '') || '/';
    }
    next();
});

// Store multiple connections (one per user)
const connections = new Map();
const logger = pino({ level: 'error' });

// Ensure sessions directory exists
if (!fs.existsSync(sessionsDir)) {
    fs.mkdirSync(sessionsDir, { recursive: true });
}

// Log file for debugging
const logFile = path.join(__dirname, 'server.log');
const logStream = fs.createWriteStream(logFile, { flags: 'a' });

function safeLog(msg, data = null) {
    const timestamp = new Date().toISOString();
    let formattedData = '';
    if (data) {
        if (typeof data === 'string') formattedData = data;
        else {
            try { formattedData = JSON.stringify(data); } catch (e) { formattedData = '[Circular]'; }
        }
    }
    const output = `[${timestamp}] ${msg} ${formattedData}\n`;
    process.stdout.write(output);
    logStream.write(output);
}

safeLog('Server initializing...');

async function connectToWhatsApp(userId, whatsappNumberId) {
    const key = `${userId}_${whatsappNumberId}`;
    const sessionPath = path.join(sessionsDir, `session_${userId}_${whatsappNumberId}`);

    // If already connecting/connected, return existing
    if (connections.has(key)) {
        console.log(`[${key}] Connection already exists`);
        return connections.get(key);
    }

    try {
        console.log(`[${key}] Starting connection... ${new Date().toISOString()}`);
        safeLog(`[${key}] Initiating connection process...`);

        // Ensure session folder exists
        if (!fs.existsSync(sessionPath)) {
            fs.mkdirSync(sessionPath, { recursive: true });
        }

        safeLog(`[${key}] Fetching latest Baileys version...`);
        let version;
        try {
            const v = await fetchLatestBaileysVersion().catch(() => ({ version: [2, 2413, 1] }));
            version = v.version;
        } catch (vErr) {
            version = [2, 2413, 1]; // Fallback to a known stable version
        }

        safeLog(`[${key}] Using Baileys version: ${version.join('.')}`);
        const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

        const sock = makeWASocket({
            version,
            auth: {
                creds: state.creds,
                keys: makeCacheableSignalKeyStore(state.keys, logger),
            },
            printQRInTerminal: false,
            logger,
            browser: ['Blaster User', 'Chrome', '120.0.0.0'],
            connectTimeoutMs: 60000,
            defaultQueryTimeoutMs: 30000,
            keepAliveIntervalMs: 30000,
            generateHighQualityLinkPreview: true,
        });

        const connectionData = {
            sock,
            userId,
            whatsappNumberId,
            qr: null,
            status: 'starting',
            phoneInfo: null,
            lastActivity: new Date(),
        };

        connections.set(key, connectionData);

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                connectionData.qr = await qrcode.toDataURL(qr);
                connectionData.status = 'awaiting_scan';
                connectionData.lastActivity = new Date();
                console.log(`[${key}] QR Generated`);

                // Notify Laravel backend
                try {
                    await axios.post(`${API_BASE_URL}/api/whatsapp/qr-update`, {
                        user_id: userId,
                        phone_number: whatsappNumberId,
                        qr_code: connectionData.qr,
                        status: 'qr_ready',
                    });
                    console.log(`[${key}] Backend notified of QR`);
                } catch (error) {
                    console.error(`[${key}] Backend notification failed:`, error.message);
                }
            }

            if (connection === 'close') {
                connectionData.status = 'disconnected';
                connectionData.qr = null;
                connectionData.phoneInfo = null;

                const statusCode = lastDisconnect?.error?.output?.statusCode;
                console.log(`[${key}] Connection closed: ${statusCode}`);

                if (statusCode === DisconnectReason.loggedOut) {
                    // User logged out - clean session
                    connections.delete(key);
                    cleanupSession(sessionPath);

                    // Notify backend
                    try {
                        await axios.post(`${API_BASE_URL}/api/whatsapp/status-update`, {
                            user_id: userId,
                            phone_number: whatsappNumberId,
                            status: 'disconnected',
                        });
                    } catch (error) {
                        console.error(`[${key}] Backend notification failed`);
                    }
                } else {
                    // Temporary disconnect - try to reconnect
                    connectionData.status = 'reconnecting';
                    setTimeout(() => {
                        connections.delete(key);
                        connectToWhatsApp(userId, whatsappNumberId);
                    }, 5000);
                }
            } else if (connection === 'open') {
                safeLog(`[${key}] ✅ Connected!`, sock.user);
                connectionData.status = 'connected';
                connectionData.qr = null;
                connectionData.phoneInfo = sock.user;
                connectionData.lastActivity = new Date();

                // Notify backend
                try {
                    await axios.post(`${API_BASE_URL}/api/whatsapp/status-update`, {
                        user_id: userId,
                        phone_number: whatsappNumberId,
                        status: 'connected',
                        phone_info: connectionData.phoneInfo,
                    });
                    safeLog(`[${key}] Backend notified of connection`);
                } catch (error) {
                    safeLog(`[${key}] Backend notification failed: ${error.message}`);
                }
            }
        });

        // Handle incoming messages
        sock.ev.on('messages.upsert', async ({ messages }) => {
            for (const msg of messages) {
                if (!msg.message || msg.key.fromMe) continue;

                const from = msg.key.remoteJid;

                // Comprehensive text extraction
                let text = '';
                if (msg.message) {
                    text = msg.message.conversation ||
                        msg.message.extendedTextMessage?.text ||
                        msg.message.imageMessage?.caption ||
                        msg.message.videoMessage?.caption ||
                        msg.message.documentMessage?.caption ||
                        msg.message.templateButtonReplyMessage?.selectedId ||
                        msg.message.interactiveResponseMessage?.body?.text ||
                        '';

                    // If still empty, check for nested message in viewed/ephemeral
                    if (!text && msg.message.viewOnceMessageV2?.message) {
                        const m = msg.message.viewOnceMessageV2.message;
                        text = m.conversation || m.extendedTextMessage?.text || '';
                    }
                    if (!text && msg.message.ephemeralMessage?.message) {
                        const m = msg.message.ephemeralMessage.message;
                        text = m.conversation || m.extendedTextMessage?.text || '';
                    }
                }

                if (!text && msg.message?.buttonsResponseMessage) {
                    text = msg.message.buttonsResponseMessage.selectedButtonId;
                }

                if (!text && msg.message?.listResponseMessage) {
                    text = msg.message.listResponseMessage.singleSelectReply.selectedRowId;
                }

                console.log(`[${key}] Raw Message Structure:`, JSON.stringify(msg.message).substring(0, 200));
                console.log(`[${key}] Extracted Text: "${text}"`);

                connectionData.lastActivity = new Date();

                // Skip if no text and no specific interaction
                if (!text && !msg.message?.buttonsResponseMessage && !msg.message?.listResponseMessage) {
                    console.log(`[${key}] Skipping empty message`);
                    continue;
                }

                // Notify backend for auto-reply processing
                try {
                    await axios.post(`${API_BASE_URL}/api/whatsapp/incoming-message`, {
                        user_id: userId,
                        phone_number: whatsappNumberId,
                        from,
                        message: text,
                        message_id: msg.key.id,
                    });
                } catch (error) {
                    console.error(`[${key}] Failed to process message: ${error.message}${error.response ? ' - ' + JSON.stringify(error.response.data) : ''}`);
                }
            }
        });

        // Handle message receipts (delivered, read)
        sock.ev.on('message-receipt.update', async (updates) => {
            for (const update of updates) {
                // status 2 = delivered, 3 = read
                if (update.receipt.receiptTimestamp) {
                    try {
                        await axios.post(`${API_BASE_URL}/api/whatsapp/message-receipt`, {
                            user_id: userId,
                            phone_number: whatsappNumberId,
                            message_id: update.key.id,
                            status: update.receipt.type === 'read' ? 3 : 2,
                            timestamp: update.receipt.receiptTimestamp,
                        });
                    } catch (error) {
                        // Silent error for receipts to avoid log spam
                    }
                }
            }
        });

        return connectionData;

    } catch (error) {
        console.error(`[${key}] Fatal Launch Error:`, error);
        connections.delete(key);
        throw error;
    }
}

function cleanupSession(sessionPath) {
    console.log(`[WA] Cleaning session: ${sessionPath}`);
    try {
        if (fs.existsSync(sessionPath)) {
            fs.rmSync(sessionPath, { recursive: true, force: true });
        }
        console.log(`[WA] Session cleaned`);
    } catch (err) {
        console.error(`[WA] Cleanup failure:`, err.message);
    }
}

// Routes

// Connect a user's WhatsApp
app.post('/connect', async (req, res) => {
    const { user_id, phone_number } = req.body;

    if (!user_id || !phone_number) {
        return res.status(400).json({ error: 'user_id and phone_number required' });
    }

    try {
        const conn = await connectToWhatsApp(user_id, phone_number);
        res.json({
            status: conn.status,
            qr: conn.qr,
            phone_info: conn.phoneInfo,
        });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Get status for a specific user's connection
app.get('/status/:user_id/:phone_number', (req, res) => {
    const { user_id, phone_number } = req.params;
    const key = `${user_id}_${phone_number}`;
    const conn = connections.get(key);

    if (!conn) {
        return res.json({
            connected: false,
            status: 'disconnected',
            qr: null,
            phone_info: null,
        });
    }

    res.json({
        connected: conn.status === 'connected',
        status: conn.status,
        qr: conn.qr,
        phone_info: conn.phoneInfo,
        last_activity: conn.lastActivity,
    });
});

// Send message from a specific user's connection
app.post('/send-message', async (req, res) => {
    const { user_id, phone_number, to, message, media_url, media_type, filename } = req.body;

    if (!user_id || !phone_number || !to) {
        return res.status(400).json({ error: 'Missing required fields' });
    }

    const key = `${user_id}_${phone_number}`;
    const conn = connections.get(key);

    safeLog(`[${key}] Sending message to ${to}`, { hasMedia: !!media_url, type: media_type });

    if (!conn || conn.status !== 'connected') {
        safeLog(`[${key}] Failed: WhatsApp not connected. Status: ${conn ? conn.status : 'not_init'}`);
        return res.status(503).json({ error: 'WhatsApp not connected' });
    }

    try {
        let cleanTo = to.replace(/\D/g, '');
        if (cleanTo.startsWith('0')) {
            cleanTo = '6' + cleanTo; // Default to Malaysia if starting with 0
        }
        const jid = to.includes('@') ? to : (cleanTo + '@s.whatsapp.net');

        conn.lastActivity = new Date();
        let sentResult;

        if (media_url) {
            safeLog(`[${key}] Downloading media: ${media_url}`);
            try {
                // Manually download media to handle 404s and timeouts gracefully
                const mediaResponse = await axios.get(media_url, {
                    responseType: 'arraybuffer',
                    timeout: 15000 // 15s timeout
                });
                const mediaBuffer = Buffer.from(mediaResponse.data);

                let sendObj = {};
                if (media_type === 'image') {
                    sendObj = { image: mediaBuffer, caption: message || '' };
                } else if (media_type === 'video') {
                    sendObj = { video: mediaBuffer, caption: message || '' };
                } else if (media_type === 'audio') {
                    sendObj = { audio: mediaBuffer, mimetype: 'audio/mp4' };
                } else {
                    sendObj = { document: mediaBuffer, mimetype: media_type || 'application/pdf', fileName: filename || 'file', caption: message || '' };
                }

                sentResult = await conn.sock.sendMessage(jid, sendObj);
            } catch (dlError) {
                safeLog(`[${key}] Media download failed: ${dlError.message}`);
                return res.status(422).json({
                    error: `Media download failed: ${dlError.message}. Accessing: ${media_url}. Ensure storage link is correct.`
                });
            }
        } else {
            const urlRegex = /(https?:\/\/[^\s]+)/gi;
            const urls = message.match(urlRegex);

            if (urls && urls.length > 0) {
                try {
                    const preview = await getUrlInfo(urls[0]);
                    if (preview) {
                        await new Promise(resolve => setTimeout(resolve, 3000));
                        sentResult = await conn.sock.sendMessage(jid, { text: message, ...preview });
                    } else {
                        sentResult = await conn.sock.sendMessage(jid, { text: message });
                    }
                } catch (e) {
                    sentResult = await conn.sock.sendMessage(jid, { text: message });
                }
            } else {
                sentResult = await conn.sock.sendMessage(jid, { text: message });
            }
        }

        safeLog(`[${key}] Message sent! ID: ${sentResult?.key?.id}`);

        res.status(200).json({
            success: true,
            message_id: sentResult?.key?.id
        });
    } catch (e) {
        safeLog(`[${key}] Send error: ${e.message}`, e);
        res.status(500).json({ error: e.message });
    }
});

// Disconnect a specific user's connection
app.post('/disconnect', async (req, res) => {
    const { user_id, phone_number } = req.body;
    const key = `${user_id}_${phone_number}`;
    const conn = connections.get(key);

    if (!conn) {
        return res.status(404).json({ error: 'Connection not found' });
    }

    try {
        if (conn.sock) {
            conn.sock.ev.removeAllListeners();
            await conn.sock.logout();
        }
    } catch (e) {
        console.error(`[${key}] Logout error:`, e);
    }

    connections.delete(key);

    const sessionPath = path.join(sessionsDir, `session_${user_id}_${phone_number}`);
    cleanupSession(sessionPath);

    res.json({ success: true, message: 'Disconnected' });
});

// Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        total_connections: connections.size,
        connections: Array.from(connections.keys()),
        uptime: process.uptime()
    });
});

// Root route
app.get('/', (req, res) => {
    res.send('🚀 Blaster WhatsApp Server is Running!');
});

// Debug logs
app.get('/debug-logs', (req, res) => {
    const logFile = path.join(__dirname, 'server.log');
    if (fs.existsSync(logFile)) {
        res.sendFile(logFile);
    } else {
        res.send('Log file not found');
    }
});

// Global status (for backward compatibility)
app.get('/status', (req, res) => {
    const allConnections = Array.from(connections.entries()).map(([key, conn]) => ({
        key,
        user_id: conn.userId,
        whatsapp_number_id: conn.whatsappNumberId,
        status: conn.status,
        connected: conn.status === 'connected',
        has_qr: !!conn.qr,
    }));

    res.json({
        total_connections: connections.size,
        connections: allConnections,
    });
});

app.listen(port, () => {
    console.log(`🚀 Blaster Multi-User WhatsApp Engine on port ${port}`);
    console.log(`📡 API Base URL: ${API_BASE_URL}`);
    console.log(`👥 Ready for multiple user connections`);

    // Auto-reconnect existing sessions
    if (fs.existsSync(sessionsDir)) {
        fs.readdirSync(sessionsDir).forEach(file => {
            if (file.startsWith('session_')) {
                const parts = file.split('_');
                if (parts.length === 3) {
                    const userId = parts[1];
                    const whatsappNumberId = parts[2];
                    console.log(`[Startup] Restoring session for User ${userId}, Number ${whatsappNumberId}`);
                    connectToWhatsApp(userId, whatsappNumberId).catch(err => {
                        console.error(`[Startup] Failed to restore session ${file}:`, err.message);
                    });
                }
            }
        });
    }
});
