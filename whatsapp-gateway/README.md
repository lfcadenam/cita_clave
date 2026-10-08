# 📲 Gateway de WhatsApp (Evolution API / Baileys) — Nuvex Tecnología

Este módulo permite enviar notificaciones y recordatorios automáticos de citas por **WhatsApp** a costo **$0 por mensaje** utilizando el motor de WhatsApp Web (Baileys) mediante **Evolution API v2**.

---

## 📋 Requisitos Previos

1. **Docker Desktop** instalado y abierto en Windows.
2. Tu teléfono con WhatsApp abierto (el número del estudio o de Paola).

---

## 🚀 Paso a Paso de Puesta en Marcha

### Paso 1: Iniciar el Gateway
1. Abre **Docker Desktop** en tu computadora.
2. En la carpeta `whatsapp-gateway/`, haz doble clic en:
   ```cmd
   iniciar-gateway.bat
   ```
   *(O ejecuta en la terminal: `docker compose up -d`)*
3. El servicio quedará corriendo en segundo plano en `http://localhost:8080`.

---

### Paso 2: Escanear el Código QR
1. En la misma carpeta `whatsapp-gateway/`, haz doble clic en:
   ```cmd
   vincular-whatsapp-qr.bat
   ```
2. Se abrirá una pestaña en tu navegador mostrando el **Código QR**.
3. En tu celular, abre WhatsApp:
   * **Ajustes / Menú de 3 puntos** → **Dispositivos vinculados** → **Vincular un dispositivo**.
4. Escanea el código QR que aparece en pantalla.
5. ¡Listo! Tu WhatsApp quedará vinculado como una sesión web persistente.

---

### Paso 3: Probar el Envío desde Laravel

Desde la carpeta `backend/`, ejecuta en tu terminal:

```bash
# Envía un mensaje de prueba al celular indicado
php artisan whatsapp:test 3106080402
```

Si todo está conectado, recibirás de inmediato el mensaje de prueba en el chat de WhatsApp.

---

### ⏰ ¿Cómo se envían los recordatorios 24 horas antes?

El sistema ya tiene programado el Cron en Laravel (`appointments:send-reminders`) que corre automáticamente a las 8:00 AM todos los días.

Para forzar y probar el envío de recordatorios de citas de mañana ahora mismo:

```bash
# Probar el envío de recordatorios de citas programadas para mañana
php artisan appointments:send-reminders

# O forzar una fecha específica:
php artisan appointments:send-reminders --date=2026-10-08 --force
```

El mensaje incluye:
* Nombre de la clienta
* Servicio, fecha y hora
* Saldo pendiente en local
* Enlace de 1 clic para **Confirmar Asistencia** directamente en el sistema
* Enlace al comprobante digital

---

## ⚙️ Configuración en Laravel (`backend/.env`)

Las variables ya quedaron configuradas por defecto:

```env
WHATSAPP_ENABLED=true
WHATSAPP_DRIVER=evolution
WHATSAPP_ADMIN_PHONE=573106080402
EVOLUTION_API_URL=http://localhost:8080
EVOLUTION_API_KEY=nuvex_evolution_key_2026
EVOLUTION_INSTANCE_NAME=paola_estudio
```

* Si en algún momento deseas desactivar el gateway o probar en modo simulado sin Docker, simplemente cambia:
  `WHATSAPP_DRIVER=log`
