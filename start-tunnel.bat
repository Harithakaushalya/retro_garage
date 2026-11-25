@echo off
title Retro Garage Cloudflare Tunnel
echo ========================================================
echo   Starting Cloudflare Tunnel for Retro Garage (Port 80)
echo ========================================================
echo.
"%~dp0cloudflared.exe" tunnel --url http://localhost:80
pause
