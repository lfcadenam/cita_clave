@echo off
title Iniciar Evolution API (WhatsApp Gateway) - Nuvex
echo ========================================================
echo   Iniciando Evolution API v2 (WhatsApp Baileys Gateway)
echo ========================================================
echo.
echo Verificando que Docker Desktop este abierto...
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker Desktop no esta en ejecucion.
    echo Por favor abre "Docker Desktop" en Windows y vuelve a ejecutar este archivo.
    echo.
    pause
    exit /b
)

echo Iniciando contenedor de Evolution API...
docker compose up -d

echo.
echo ========================================================
echo  Gateway iniciado exitosamente en: http://localhost:8080
echo  API Key: nuvex_evolution_key_2026
echo ========================================================
echo.
echo Siguiente paso: Ejecuta "vincular-whatsapp-qr.bat" para escanear el codigo QR.
echo.
pause
