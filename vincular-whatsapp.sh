#!/usr/bin/env bash
# ==============================================================================
# Script de Vinculación Directa de WhatsApp en el Servidor (Terminal)
# Nuvex Tecnología — La Belle Nails Bogotá
# ==============================================================================

set -e

API_PORT="8085"
API_KEY="nuvex_evolution_key_2026"
INSTANCE="paola_estudio"
BASE_URL="http://localhost:${API_PORT}"

echo "========================================================"
echo "  📲 Vinculador Oficial de WhatsApp — La Belle Nails"
echo "========================================================"
echo ""

# 0. Si se solicitó reset explícito, borrar instancia previa
if [ "$1" = "reset" ] || [ "$1" = "--reset" ]; then
    echo "🔄 Purgando y reseteando sesión previa de '${INSTANCE}'..."
    curl -s -X DELETE "${BASE_URL}/instance/logout/${INSTANCE}" -H "apikey: ${API_KEY}" > /dev/null 2>&1 || true
    curl -s -X DELETE "${BASE_URL}/instance/delete/${INSTANCE}" -H "apikey: ${API_KEY}" > /dev/null 2>&1 || true
    echo "Instancia anterior eliminada limpiamente."
    shift || true
fi

# 1. Asegurar que la instancia existe en Evolution API
echo "1. Verificando/creando la instancia '${INSTANCE}'..."
curl -s -X POST "${BASE_URL}/instance/create" \
  -H "apikey: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d "{\"instanceName\":\"${INSTANCE}\",\"qrcode\":true,\"integration\":\"WHATSAPP-BAILEYS\"}" > /dev/null 2>&1 || true

# 2. Si el usuario pasó un número como argumento, solicitar código de 8 dígitos para WhatsApp
if [ -n "$1" ]; then
    CLEAN_PHONE=$(echo "$1" | tr -cd '0-9')
    if [ ${#CLEAN_PHONE} -eq 10 ]; then
        CLEAN_PHONE="57${CLEAN_PHONE}"
    fi

    echo "2. Solicitando código de 8 dígitos para el teléfono: +${CLEAN_PHONE}..."
    PAIRING_RES=$(curl -s -X GET "${BASE_URL}/instance/connect/${INSTANCE}?number=${CLEAN_PHONE}" \
      -H "apikey: ${API_KEY}")

    PAIRING_CODE=$(echo "$PAIRING_RES" | grep -o '"pairingCode":"[^"]*"' | cut -d'"' -f4)

    if [ -n "$PAIRING_CODE" ]; then
        echo ""
        echo "========================================================"
        echo "  ✨ TU CÓDIGO DE VINCULACIÓN ES:  ${PAIRING_CODE}"
        echo "========================================================"
        echo ""
        echo "Pasos en tu celular:"
        echo "1. Abre WhatsApp -> Dispositivos vinculados -> Vincular un dispositivo."
        echo "2. Toca abajo: 'Vincular con el número de teléfono'."
        echo "3. Digita este código de 8 caracteres: ${PAIRING_CODE}"
        echo ""
        exit 0
    fi
fi

# 3. Obtener el código de WhatsApp
echo "2. Consultando código de conexión..."
CONNECT_RES=$(curl -s -X GET "${BASE_URL}/instance/connect/${INSTANCE}" \
  -H "apikey: ${API_KEY}")

QR_CODE=$(echo "$CONNECT_RES" | grep -o '"code":"[^"]*"' | cut -d'"' -f4)

# 4. Si se tiene el código y qrencode, dibujarlo en la terminal
if [ -n "$QR_CODE" ]; then
    if ! command -v qrencode &> /dev/null; then
        echo "Instalando herramienta para dibujar el código QR en la terminal..."
        apt-get update -qq > /dev/null 2>&1 && apt-get install -y -qq qrencode > /dev/null 2>&1 || true
    fi

    if command -v qrencode &> /dev/null; then
        echo ""
        echo "✨ ESCANEA ESTE CÓDIGO QR DIRECTO DE LA PANTALLA:"
        echo ""
        qrencode -t ANSIUTF8 "${QR_CODE}"
        echo ""
        echo "Pasos:"
        echo "1. Abre WhatsApp -> Dispositivos vinculados -> Vincular un dispositivo."
        echo "2. Apunta la cámara al código QR de arriba."
        echo ""
        echo "👉 O si prefieres la web, entra a: https://labellenailsbog.com/admin/whatsapp"
        exit 0
    fi
fi

echo ""
echo "👉 Abre directamente en tu navegador web para ver el QR:"
echo "   https://labellenailsbog.com/admin/whatsapp"
echo ""
echo "O genera el código de 8 dígitos ejecutando:"
echo "   bash vincular-whatsapp.sh 3106080402"
echo ""
