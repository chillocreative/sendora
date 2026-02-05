# Sendora API Testing Guide

## Quick Start

### 1. Create API Token

1. Login to https://sendora.cc
2. Make sure you have a **Business plan** subscription (API access required)
3. Go to **API Tokens** page
4. Click **Create New Token**
5. Give it a name (e.g., "Testing Token")
6. Select permissions (abilities)
7. Copy the token (you'll only see it once!)

### 2. Base URL

```
Production: https://sendora.cc/api/v1
Local: http://localhost:8000/api/v1
```

### 3. Authentication

All API requests require a Bearer token in the Authorization header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## API Endpoints

### 1. Get User Profile

**GET** `/profile`

```bash
curl -X GET https://sendora.cc/api/v1/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "subscription": {
      "plan": "Business",
      "status": "active",
      "limits": {
        "whatsapp_nos": 10,
        "contacts": 10000,
        "messages": 50000
      },
      "messages_used": 150
    }
  }
}
```

---

### 2. Get Usage Statistics

**GET** `/usage`

```bash
curl -X GET https://sendora.cc/api/v1/usage \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "devices": {
      "used": 2,
      "limit": 10
    },
    "contacts": {
      "used": 450,
      "limit": 10000
    },
    "messages": {
      "used": 150,
      "limit": 50000
    },
    "features": {
      "api_access": true,
      "auto_reply": true
    }
  }
}
```

---

### 3. Get Contacts

**GET** `/contacts?search=john&per_page=50`

```bash
curl -X GET "https://sendora.cc/api/v1/contacts?search=john&per_page=50" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "John Doe",
        "phone": "60123456789",
        "email": "john@example.com",
        "created_at": "2026-02-01T10:30:00.000000Z"
      }
    ],
    "total": 1,
    "per_page": 50
  }
}
```

---

### 4. Create Contact

**POST** `/contacts`

```bash
curl -X POST https://sendora.cc/api/v1/contacts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "phone": "60123456789",
    "country_code": "60"
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "phone_number": "60123456789",
    "country_code": "60"
  },
  "message": "Contact created successfully."
}
```

---

### 5. Get WhatsApp Devices

**GET** `/devices`

```bash
curl -X GET https://sendora.cc/api/v1/devices \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status": "connected",
      "phone_number": "60123456789",
      "phone_info": {
        "id": "60123456789@s.whatsapp.net",
        "name": "My Business"
      },
      "created_at": "2026-02-01T10:00:00.000000Z"
    }
  ]
}
```

---

### 6. Send WhatsApp Message

**POST** `/messages/send`

```bash
curl -X POST https://sendora.cc/api/v1/messages/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "60123456789",
    "message": "Hello from Sendora API!",
    "device_id": 1
  }'
```

**Request Body:**
- `phone` (required): Recipient phone number (international format)
- `message` (required): Message text (max 4096 chars)
- `device_id` (optional): WhatsApp device ID. If not provided, uses first connected device

**Response (Success):**
```json
{
  "success": true,
  "message": "Message sent successfully.",
  "data": {
    "phone": "60123456789",
    "device_id": 1
  }
}
```

**Response (Error - No Device):**
```json
{
  "success": false,
  "message": "No connected WhatsApp device found."
}
```

**Response (Error - Limit Reached):**
```json
{
  "success": false,
  "message": "Message limit reached for this month."
}
```

---

### 7. Get Campaigns

**GET** `/campaigns?per_page=20`

```bash
curl -X GET "https://sendora.cc/api/v1/campaigns?per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Product Launch",
        "status": "completed",
        "total_recipients": 150,
        "sent_count": 150,
        "failed_count": 0,
        "scheduled_at": null,
        "whatsapp_number": {
          "id": 1,
          "phone_number": "60123456789"
        },
        "created_at": "2026-02-01T15:00:00.000000Z"
      }
    ],
    "total": 1
  }
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized. Please provide a valid API token."
}
```

### 403 Forbidden (No API Access)
```json
{
  "success": false,
  "message": "API access is only available on the Business plan. Please upgrade your subscription."
}
```

### 422 Validation Error
```json
{
  "success": false,
  "errors": {
    "phone": ["The phone field is required."],
    "message": ["The message field is required."]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "WhatsApp server connection failed.",
  "error": "Connection timeout"
}
```

---

## Testing with cURL

### Full Example: Send a Message

```bash
# 1. Set your token
export API_TOKEN="YOUR_TOKEN_HERE"

# 2. Check your profile
curl -X GET https://sendora.cc/api/v1/profile \
  -H "Authorization: Bearer $API_TOKEN"

# 3. Check your connected devices
curl -X GET https://sendora.cc/api/v1/devices \
  -H "Authorization: Bearer $API_TOKEN"

# 4. Send a test message
curl -X POST https://sendora.cc/api/v1/messages/send \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "60123456789",
    "message": "Test message from Sendora API"
  }'

# 5. Check usage
curl -X GET https://sendora.cc/api/v1/usage \
  -H "Authorization: Bearer $API_TOKEN"
```

---

## Rate Limiting

- All API endpoints are rate-limited per user
- Default: 60 requests per minute
- If you exceed the limit, you'll receive a 429 Too Many Requests response

---

## Best Practices

1. **Store tokens securely** - Never commit tokens to version control
2. **Use environment variables** - Store tokens in .env files
3. **Handle errors gracefully** - Always check the `success` field
4. **Respect rate limits** - Implement exponential backoff
5. **Validate input** - Check phone numbers are in international format
6. **Check device status** - Ensure device is 'connected' before sending

---

## Phone Number Format

Always use international format without `+`:
- ✅ Correct: `60123456789` (Malaysia)
- ❌ Wrong: `+60123456789`
- ❌ Wrong: `0123456789`

---

## Support

For API issues or questions, contact support@sendora.cc
