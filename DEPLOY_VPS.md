# Guía de Despliegue en Servidor VPS con Docker - Cita Clave SaaS

Esta guía describe el procedimiento paso a paso para instalar y ejecutar **Cita Clave SaaS** en cualquier servidor VPS (Ubuntu 22.04 / 24.04 LTS o Debian 12) utilizando **Docker** y **Docker Compose**.

---

## 1. Requisitos Previos en el VPS

Asegúrate de tener instalado en tu servidor:
- **Docker Engine** (v24.0+)
- **Docker Compose Plugin** (v2.20+)
- **Git**
- Tu dominio o subdominio apuntando mediante registro **A** a la dirección IP pública del VPS (por ejemplo: `app.citaclave.com`).

Si aún no tienes Docker en tu servidor Ubuntu/Debian, instálalo rápidamente con:
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
newgrp docker
```

---

## 2. Clonar el Proyecto y Preparar Entorno

1. Clona el repositorio en tu VPS:
   ```bash
   git clone https://github.com/lfcadenam/cita_clave.git /var/www/cita_clave
   cd /var/www/cita_clave
   ```

2. Crea tu archivo de variables de entorno `.env` a partir de la plantilla:
   ```bash
   cp .env.docker.example .env
   ```

3. Genera una clave segura para Laravel y edita tu `.env`:
   ```bash
   # Generar clave única
   docker run --rm -v $(pwd)/backend:/app -w /app composer:2 php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
   ```
   Copia el resultado generado y edita el archivo `.env`:
   ```bash
   nano .env
   ```
   **Campos indispensables a configurar:**
   - `APP_KEY=base64:EL_VALOR_GENERADO`
   - `APP_URL=https://cita-clave.nuvex-tecnologia.com`
   - `DB_PASSWORD=TuPasswordSeguroParaLaBD`
   - `DB_ROOT_PASSWORD=TuPasswordRootSeguro`

---

## 3. Iniciar la Plataforma

Construye las imágenes e inicia los contenedores en segundo plano:

```bash
docker compose up -d --build
```

### ¿Qué hace Docker automáticamente al arrancar?
1. **Compila el frontend Angular 19** en modo producción optimizado.
2. **Construye el contenedor PHP 8.3-FPM** e instala las dependencias de Composer sin herramientas de desarrollo (`--no-dev`).
3. **Inicia MySQL 8.0** con almacenamiento persistente en el volumen `citaclave_db_data`.
4. **Ejecuta las migraciones de base de datos** automáticamente (`php artisan migrate --force`).
5. **Crea el Super Administrador global exclusivo** (`SuperAdminSeeder`) sin datos basura ni registros de prueba.
6. **Optimiza la caché** de rutas, configuración y vistas de Laravel.
7. **Configura el proxy inverso Nginx** para responder con alto rendimiento.

Puedes comprobar que todos los servicios estén activos (`healthy` / `Up`) con:
```bash
docker compose ps
```

---

## 4. Acceso al Panel de Super Administrador

Una vez los contenedores estén arriba, abre tu navegador web e ingresa a:

👉 **`https://cita-clave.nuvex-tecnologia.com/superadmin`**

### Credenciales de Acceso:
- **Correo Electrónico:** `admin@nuvex-tecnologia.com`
- **Contraseña:** `C3be7x33ygh`
- **Rol:** `Super Administrador Nuvex`

Desde este panel podrás:
- Crear nuevos Salones / Negocios (Tenants) para tus clientes.
- Asignar dominios y colores de marca a cada salón.
- Gestionar planes de suscripción y límites de reservas.
- Acceder como administrador a cualquier salón con un solo clic.

---

## 5. Configurar Certificado SSL Gratuito (HTTPS)

Para activar HTTPS en `cita-clave.nuvex-tecnologia.com` con **Let's Encrypt** usando Certbot:

1. Instala Certbot en tu VPS:
   ```bash
   sudo apt install certbot -y
   ```

2. Detén temporalmente el contenedor web para liberar el puerto 80:
   ```bash
   docker compose stop web
   ```

3. Emite el certificado SSL para el dominio:
   ```bash
   sudo certbot certonly --standalone -d cita-clave.nuvex-tecnologia.com
   ```

4. Copia o vincula los certificados a la carpeta `docker/nginx/ssl`:
   ```bash
   sudo cp /etc/letsencrypt/live/cita-clave.nuvex-tecnologia.com/fullchain.pem docker/nginx/ssl/cert.pem
   sudo cp /etc/letsencrypt/live/cita-clave.nuvex-tecnologia.com/privkey.pem docker/nginx/ssl/key.pem
   ```

5. Reinicia el contenedor web:
   ```bash
   docker compose up -d web
   ```

*(Alternativamente, si usas **Cloudflare**, puedes dejar el SSL en modo "Flexible" o "Full" apuntando directamente al puerto 80 de tu VPS).*

---

## 6. Comandos Útiles de Mantenimiento

- **Ver logs en tiempo real:**
  ```bash
  docker compose logs -f app
  docker compose logs -f web
  docker compose logs -f queue
  ```

- **Ejecutar comandos Artisan dentro del contenedor:**
  ```bash
  docker compose exec app php artisan route:list
  docker compose exec app php artisan cache:clear
  ```

- **Crear un respaldo (Backup) de la Base de Datos MySQL:**
  ```bash
  docker compose exec db mysqldump -u citaclave_user -pTuPasswordSeguroParaLaBD citaclave > backup_$(date +%Y%m%d_%H%M%S).sql
  ```

- **Restaurar un respaldo:**
  ```bash
  docker compose exec -T db mysql -u citaclave_user -pTuPasswordSeguroParaLaBD citaclave < backup.sql
  ```

- **Actualizar la plataforma a una nueva versión:**
  ```bash
  git pull origin main
  docker compose up -d --build
  ```