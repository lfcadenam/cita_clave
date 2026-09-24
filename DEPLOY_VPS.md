# Guía de Despliegue en Servidor VPS con Docker - La Belle Nails Bogotá
## Plataforma SaaS de Agendamiento Cita Clave

Esta guía describe el procedimiento paso a paso para desplegar y poner en marcha **La Belle Nails Bogotá** en cualquier servidor VPS (Ubuntu 22.04 / 24.04 LTS o Debian 12) utilizando **Docker** y **Docker Compose** bajo el dominio oficial **`https://labellenailsbog.com`**.

---

## 1. Configuración de Dominio y DNS (Paso Previo Indispensable)

Antes de iniciar los servicios en el servidor, debes configurar los registros DNS en el proveedor donde compraste el dominio `labellenailsbog.com` (GoDaddy, Namecheap, DonDominio, Hostinger, etc.).

### A. Registros DNS Necesarios (Zona DNS)

| Tipo | Nombre / Host | Valor / Destino | TTL | Propósito |
| :--- | :--- | :--- | :--- | :--- |
| **A** | `@` (o `labellenailsbog.com`) | `[IP_PÚBLICA_DE_TU_VPS]` | 300 s (o Automático) | Dirige el dominio raíz al servidor. |
| **CNAME** (o **A**) | `www` | `@` (o `[IP_PÚBLICA_DE_TU_VPS]`) | 300 s (o Automático) | Permite acceso con `www.labellenailsbog.com`. |

> [!IMPORTANT]
> Reemplaza `[IP_PÚBLICA_DE_TU_VPS]` por la dirección IPv4 pública asignada a tu servidor VPS (ejemplo: `198.51.100.25`).

### B. Recomendación de Arquitectura SSL: Cloudflare (Recomendado)
Para máxima velocidad, protección DDoS y certificado SSL automático sin mantenimiento local:
1. Agrega `labellenailsbog.com` a tu cuenta de **Cloudflare**.
2. Cambia los Name Servers en tu registrador por los que te indique Cloudflare.
3. Asegúrate de que los registros DNS `@` y `www` tengan la nube naranja encendida (**Proxied**).
4. En el panel de Cloudflare ve a **SSL/TLS**:
   - Modo de Cifrado: Selecciona **Full** (o **Flexible** si el VPS solo expone el puerto 80).
   - En **Edge Certificates**: Activa **Always Use HTTPS** y **Automatic HTTPS Rewrites**.
5. ¡El certificado SSL para `labellenailsbog.com` estará activo en cuestión de minutos!

---

## 2. Requisitos Previos en el Servidor VPS

Asegúrate de tener instalado en tu servidor:
- **Docker Engine** (v24.0+)
- **Docker Compose Plugin** (v2.20+)
- **Git**

Si aún no tienes Docker en tu servidor Ubuntu/Debian, instálalo con:
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
newgrp docker
```

Si vas a usar la red compartida de proxies de Nuvex (`elan_default`), asegúrate de que la red exista:
```bash
docker network create elan_default || true
```

---

## 3. Clonar el Proyecto y Configurar Entorno

1. Clona el repositorio en tu VPS:
   ```bash
   git clone https://github.com/lfcadenam/cita_clave.git /var/www/cita_clave
   cd /var/www/cita_clave
   ```

2. Crea tu archivo `.env` a partir de la plantilla:
   ```bash
   cp .env.docker.example .env
   ```

3. Genera una clave única para Laravel:
   ```bash
   docker run --rm -v $(pwd)/backend:/app -w /app composer:2 php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
   ```

4. Edita el archivo `.env`:
   ```bash
   nano .env
   ```
   **Verifica y ajusta los siguientes valores:**
   ```env
   APP_NAME="La Belle Nails Bogotá"
   APP_ENV=production
   APP_KEY=base64:EL_VALOR_GENERADO_EN_EL_PASO_3
   APP_DEBUG=false
   APP_URL=https://labellenailsbog.com
   DOMAIN_NAME=labellenailsbog.com

   # Contraseñas seguras para la Base de Datos
   DB_PASSWORD=TuPasswordSeguroParaLaBD2026!
   DB_ROOT_PASSWORD=TuPasswordRootSeguro2026!

   # Datos de Pago Nequi Paola
   NEQUI_HOLDER_NAME="Paola Andrea Aguilera Camacho"
   NEQUI_ACCOUNT_NUMBER="3103248385"
   INITIAL_SALON_ADMIN_PASSWORD="TuPasswordAdminPaola2026!"
   ```

---

## 4. Iniciar la Plataforma con Docker

Inicia los contenedores en segundo plano:

```bash
docker compose up -d --build
```

### ¿Qué hace el sistema automáticamente al arrancar?
1. **Compila la SPA Angular 19:** Minifica y optimiza los bundles del portal de clientes.
2. **Construye el contenedor PHP 8.3-FPM:** Instala dependencias Composer optimizadas (`--no-dev`).
3. **Inicia MySQL 8.0:** Base de datos con persistencia en el volumen `citaclave_db_data`.
4. **Ejecuta las migraciones de base de datos:** `php artisan migrate --force`.
5. **Crea el Super Administrador central:** Para gestión global del SaaS.
6. **Aprovisiona el salón La Belle Nails Bogotá:** Crea el tenant oficial con dominio `labellenailsbog.com`, horarios de lunes a sábado y catálogo inicial de servicios con precios y abonos.
7. **Configura el almacenamiento:** Crea el enlace simbólico y permisos para comprobantes de pago e imágenes.
8. **Inicia Nginx de alto rendimiento:** Sirve el frontend, proxy a la API y el panel Filament.

Verifica el estado de los contenedores:
```bash
docker compose ps
```
Los 4 contenedores (`citaclave_db`, `citaclave_app`, `citaclave_queue`, `citaclave_web`) deben aparecer en estado `Up` o `healthy`.

---

## 5. Accesos y Paneles de Control

Una vez levantado el sistema, tendrás disponibles los siguientes accesos:

### 🌸 1. Portal Público de Clientes (Agenda Online)
* **URL:** **`https://labellenailsbog.com`**
* Catálogo interactivo de servicios, disponibilidad anti-huecos en tiempo real, apartado de turnos y carga de comprobantes Nequi.

### 🏢 2. Panel Administrativo del Salón (Paola Aguilera)
* **URL:** **`https://labellenailsbog.com/admin/labellenails`**
* **Usuario:** `paola@labellenailsbog.com`
* **Contraseña:** La configurada en `INITIAL_SALON_ADMIN_PASSWORD` (por defecto: `Paola2026!LaBelle`).
* **Funciones:** Calendario de citas, validación de comprobantes de abono Nequi en 1 clic, bloqueo rápido de descansos, reportes y catálogo.

### 👑 3. Panel Super Administrador Global (Nuvex Central)
* **URL:** **`https://labellenailsbog.com/superadmin`**
* **Usuario:** `admin@nuvex-tecnologia.com`
* **Contraseña:** `C3be7x33ygh`
* **Funciones:** Creación y aprovisionamiento de nuevos salones, control multi-tenant, asignación de dominios y planes.

---

## 6. Configuración de SSL Gratuito (Si NO usas Cloudflare)

Si decides no usar Cloudflare y gestionar los certificados Let's Encrypt directamente en el VPS:

1. Instala Certbot:
   ```bash
   sudo apt update && sudo apt install certbot -y
   ```

2. Emite el certificado usando el reto web (gracias al soporte ACME configurado en Nginx):
   ```bash
   sudo certbot certonly --webroot -w /var/www/cita_clave/backend/public -d labellenailsbog.com -d www.labellenailsbog.com
   ```

3. Copia los certificados generados a la carpeta SSL de Nginx:
   ```bash
   sudo cp /etc/letsencrypt/live/labellenailsbog.com/fullchain.pem /var/www/cita_clave/docker/nginx/ssl/cert.pem
   sudo cp /etc/letsencrypt/live/labellenailsbog.com/privkey.pem /var/www/cita_clave/docker/nginx/ssl/key.pem
   ```

4. Reinicia el contenedor web:
   ```bash
   docker compose restart web
   ```

---

## 7. Comandos de Mantenimiento Frecuentes

* **Ver registros (logs) en tiempo real:**
  ```bash
  docker compose logs -f app
  docker compose logs -f web
  docker compose logs -f queue
  ```

* **Limpiar y regenerar cachés:**
  ```bash
  docker compose exec app php artisan optimize:clear
  docker compose exec app php artisan config:cache
  docker compose exec app php artisan route:cache
  docker compose exec app php artisan view:cache
  ```

* **Copia de seguridad (Backup) de la Base de Datos:**
  ```bash
  docker compose exec db mysqldump -u citaclave_user -pTuPasswordSeguroParaLaBD2026! citaclave > backup_$(date +%Y%m%d_%H%M%S).sql
  ```

* **Actualizar a la última versión del repositorio:**
  ```bash
  git pull origin main
  docker compose up -d --build
  ```