# WhatsApp Baileys Server Setup

## Installation

1. Navigate to the whatsapp-server directory:
```bash
cd whatsapp-server
```

2. Install dependencies:
```bash
npm install
```

3. Configure environment variables:
- Edit `.env` file if needed
- Default port: 3000
- API_BASE_URL should point to your Laravel backend

4. Start the server:

**Development:**
```bash
npm run dev
```

**Production:**
```bash
npm start
```

## Usage

The WhatsApp server will:
- Generate QR codes for device pairing
- Maintain persistent connections
- Handle incoming/outgoing messages
- Auto-reconnect on disconnection
- Store session data in `sessions/` directory

## API Endpoints

- `POST /connect` - Initialize WhatsApp connection
- `GET /qr/:user_id/:phone_number` - Get QR code
- `POST /send-message` - Send WhatsApp message
- `POST /disconnect` - Disconnect and logout
- `GET /health` - Server health check

## Integration

The Laravel backend communicates with this server via HTTP requests.
Ensure both servers are running for WhatsApp functionality to work.

## Troubleshooting

- If connection fails, delete the session folder and reconnect
- Check logs for detailed error messages
- Ensure port 3000 is not in use by another application
