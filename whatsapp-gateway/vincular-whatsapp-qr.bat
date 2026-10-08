@echo off
title Vincular WhatsApp por Codigo QR - Nuvex
echo ========================================================
echo   Vinculando Instancia WhatsApp: paola_estudio
echo ========================================================
echo.
echo 1. Solicitando conexion de instancia en Evolution API...
curl.exe -s -X POST "http://localhost:8080/instance/create" ^
  -H "apikey: nuvex_evolution_key_2026" ^
  -H "Content-Type: application/json" ^
  -d "{\"instanceName\": \"paola_estudio\", \"qrcode\": true, \"integration\": \"WHATSAPP-BAILEYS\"}" > nul 2>&1

echo.
echo 2. Abriendo pantalla interactiva con Codigo QR en tu navegador...
start "" "%~dp0escanear-qr.html"

echo.
echo ========================================================
echo Se ha abierto la pagina para escanear el codigo QR.
echo.
echo Pasos en tu celular:
echo 1. Abre WhatsApp -> Dispositivos vinculados -> Vincular un dispositivo.
echo 2. Escanea el codigo QR que ves en tu navegador.
echo ========================================================
echo.
pause
