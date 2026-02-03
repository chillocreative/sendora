# WhatsApp QR Code Troubleshooting Guide

## Current Status:
- ✅ WhatsApp Server Running (Port 3000)
- ✅ QR Code Generation Working (tested via curl)
- ❓ QR Code not appearing in browser

## Quick Fix Steps:

### 1. Clear Browser Cache
- Press `Ctrl + Shift + Delete`
- Clear cached images and files
- Refresh the page

### 2. Check if WhatsApp Number exists in database
The WhatsApp server is looking for a record with:
- `user_id`: 1 (Admin User)
- `id` (phone_number): Should match the WhatsApp number ID

### 3. Manual Test:
Open browser console (F12) and run:
```javascript
// Check if number data is loaded
console.log(window.page.props.number);

// Manual refresh
fetch('/whatsapp/1/refresh-qr')
  .then(r => r.json())
  .then(data => console.log(data));
```

### 4. Check Laravel Logs:
```powershell
Get-Content storage\logs\laravel.log -Tail 20 -Wait
```

### 5. Restart WhatsApp Server with Fresh Session:
```bash
# Stop current server (Ctrl+C)
# Delete sessions
Remove-Item -Recurse -Force whatsapp-server/sessions/*
# Start again
cd whatsapp-server
npm start
```

### 6. Test Direct Connection:
```powershell
Invoke-WebRequest -Uri http://localhost:3000/connect -Method POST -ContentType "application/json" -Body '{"user_id":1,"phone_number":1}'
```

## Common Issues:

### Issue: "Generating QR Code..." stuck
**Cause**: WhatsApp server not calling webhook or webhook failing
**Fix**: Check Laravel logs for webhook calls

### Issue: Port 3000 already in use
**Cause**: Multiple instances running
**Fix**: 
```powershell
# Find process
Get-Process -Name node
# Kill it
Stop-Process -Name node -Force
# Restart
npm start
```

### Issue: QR Code generated but not showing
**Cause**: Frontend not refreshing or qr_code field empty
**Fix**: Check browser console for errors, verify auto-refresh is working

## Debug Commands:

```powershell
# Check WhatsApp server health
curl http://localhost:3000/health

# Check API routes
cmd /c "set PATH=D:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;%PATH% && php artisan route:list --name=whatsapp"

# View recent logs
Get-Content storage\logs\laravel.log -Tail 50
```

---
**If still not working, try deleting the WhatsApp number and creating a new one.**
