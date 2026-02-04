@echo off
echo Restarting WhatsApp Server...
taskkill /F /IM node.exe /FI "WINDOWTITLE eq whatsapp-server*" 2>nul
timeout /t 2 /nobreak >nul
cd /d d:\laragon\www\blaster\whatsapp-server
start "whatsapp-server" node server.js
echo Server restarted!
