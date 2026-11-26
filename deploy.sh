#!/usr/bin/env bash
# ==============================================================================
# Script Oficial de Despliegue Automatizado en Producción
# Proyecto: Cita Clave / La Belle Nails Bogotá (Nuvex Tecnología)
# Dominio: https://labellenailsbog.com
# ==============================================================================
set -euo pipefail

# Paleta de colores Nuvex
GREEN='\033[0;32m'
EMERALD='\033[38;2;0;201;117m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color
BOLD='\033[1m'

START_TIME=$(date +%s)

echo -e "\n${EMERALD}${BOLD}======================================================================${NC}"
echo -e "${EMERALD}${BOLD}       🚀 DESPLIEGUE AUTOMATIZADO EN PRODUCCIÓN — CITA CLAVE         ${NC}"
echo -e "${EMERALD}${BOLD}======================================================================${NC}\n"

# 1. Validar directorio de ejecución
if [ ! -f "docker-compose.yml" ]; then
    echo -e "${RED}❌ Error: Este script debe ejecutarse desde la raíz del proyecto donde reside docker-compose.yml${NC}"
    exit 1
fi

# 2. Descargar últimos cambios de Git
echo -e "${CYAN}📥 Paso 1/5: Descargando últimos cambios desde Git (origin/main)...${NC}"
git fetch origin main
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

if [ "$CURRENT_BRANCH" != "main" ]; then
    echo -e "${YELLOW}⚠️ Advertencia: No estás en la rama 'main' (rama actual: ${CURRENT_BRANCH}). Cambiando a 'main'...${NC}"
    git checkout main
fi

git pull origin main
echo -e "${GREEN}✓ Código actualizado exitosamente.${NC}\n"

# 3. Asegurar existencia de red Docker externa
echo -e "${CYAN}🌐 Paso 2/5: Verificando red Docker para proxy inverso (elan_default)...${NC}"
if ! docker network inspect elan_default >/dev/null 2>&1; then
    echo -e "${YELLOW}Creando red externa 'elan_default'...${NC}"
    docker network create elan_default
fi
echo -e "${GREEN}✓ Red Docker verificada.${NC}\n"

# 4. Reconstruir imágenes y levantar servicios
echo -e "${CYAN}🔨 Paso 3/5: Reconstruyendo contenedores (Angular 19, PHP 8.3 & Nginx)...${NC}"
docker compose up -d --build
echo -e "${GREEN}✓ Contenedores reconstruidos y levantados en segundo plano.${NC}\n"

# 5. Ejecutar migraciones de base de datos seguras
echo -e "${CYAN}📦 Paso 4/5: Ejecutando migraciones pendientes (con salvaguarda de datos)...${NC}"
docker compose exec -T app php artisan migrate --force
echo -e "${GREEN}✓ Migraciones e índices de base de datos al día.${NC}\n"

# 6. Limpiar y optimizar cachés en producción
echo -e "${CYAN}⚡ Paso 5/5: Optimizando cachés de Laravel (config, rutas y vistas)...${NC}"
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
echo -e "${GREEN}✓ Cachés optimizadas para alto rendimiento.${NC}\n"

# 7. Resumen de estado de contenedores
echo -e "${EMERALD}${BOLD}📊 Estado de los Contenedores:${NC}"
docker compose ps

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo -e "\n${EMERALD}${BOLD}======================================================================${NC}"
echo -e "${GREEN}${BOLD}  ✨ ¡DESPLIEGUE FINALIZADO CON ÉXITO EN ${DURATION} SEGUNDOS! ✨       ${NC}"
echo -e "${CYAN}  Portal Clientes:   ${BOLD}https://labellenailsbog.com${NC}"
echo -e "${CYAN}  Panel de Citas:    ${BOLD}https://labellenailsbog.com/admin/calendario${NC}"
echo -e "${CYAN}  Landing Comercial: ${BOLD}https://cita-clave.nuvex-tecnologia.com${NC}"
echo -e "${EMERALD}${BOLD}======================================================================${NC}\n"
