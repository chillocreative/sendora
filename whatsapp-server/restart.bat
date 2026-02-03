@echo off
echo Restarting WhatsApp Baileys Server...
echo.

REM Kill existing node processes
taskkill /F /IM node.exe 2>nul
timeout /t 2 /nobreak >nul

REM Clean up old sessions
echo Cleaning up old sessions...
if exist sessions rmdir /s /q sessions
mkdir sessions

echo.
echo Starting WhatsApp server...
npm start
