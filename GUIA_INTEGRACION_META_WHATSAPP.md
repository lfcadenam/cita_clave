# 📱 Guía Oficial de Integración: Meta WhatsApp Cloud API & Panel de Conectividad
**Proyecto:** Sistema de Agendamiento Inteligente y Reservas (SaaS Belleza)  
**Autor:** Nuvex Tecnología  
**Fecha:** Octubre de 2026  
**Entorno de Administración:** `/admin/configuracion-whatsapp` (o `/admin/whatsapp`)  
**Servicio Central:** `app/Services/WhatsAppNotificationService.php`  

---

## 🎯 Objetivo
Habilitar el canal oficial de **Meta WhatsApp Cloud API (Graph API v21.0)** para el envío automático y seguro de notificaciones transaccionales (confirmaciones de cita, alertas administrativas, avisos de comprobante Nequi, cancelaciones, reagendamientos y recordatorios con 24h de anticipación), manteniendo compatibilidad dual con Evolution API (QR/Baileys) como respaldo.

---

## 🛠️ 1. Paso a Paso en Meta for Developers

### Paso 1.1: Crear la Aplicación Empresarial en Meta
1. Ingresa a [developers.facebook.com](https://developers.facebook.com/) con tu cuenta de Meta / Facebook Business.
2. Haz clic en **Mis Apps** (My Apps) → botón **Crear App** (Create App).
3. Selecciona la opción **"Otro"** u **"Otros casos de uso"** → presiona **Siguiente**.
4. Selecciona el tipo de app: **"Empresarial" (Business)** → presiona **Siguiente**.
5. Asigna el nombre a la app (ej: `Agendamiento Nuvex`) y asóciala a tu cuenta comercial de **Meta Business Manager**.

---

### Paso 1.2: Agregar el Producto WhatsApp Cloud API
1. En el panel lateral de tu app, busca **WhatsApp** y presiona **Configurar**.
2. En la sección **Primeros pasos** (API Setup):
   - **Identificador del número de teléfono (Phone Number ID):** Cópialo (cadena numérica de ~15 dígitos).
   - **Identificador de la cuenta de WhatsApp Business (WABA ID):** Cópialo.
   - **Token de acceso temporal (24 horas):** Úsalo para tu primera prueba rápida.

---

### Paso 1.3: Generar Token Permanente (System User Token)
> ⚠️ **Crítico:** El token temporal expira a las 24 horas. Para producción se debe crear un token permanente desde Business Manager.

1. Abre el [Meta Business Manager](https://business.facebook.com/settings/).
2. En el menú izquierdo, navega a **Usuarios** → **Usuarios del sistema** (System Users).
3. Haz clic en **Agregar**, asigna el nombre (ej: `nuvex-agendamiento-api`) y rol **Administrador**.
4. En la ficha del usuario del sistema, haz clic en **Asignar activos** (Assign Assets):
   - Tipo de activo: **Cuentas de WhatsApp** (WhatsApp Accounts).
   - Selecciona la cuenta del salón y marca **Control total** (Administrar cuenta de WhatsApp).
5. Haz clic en el botón **Generar nuevo token**:
   - Selecciona la aplicación creada en el paso 1.1.
   - Caducidad del token: **Nunca** (Never).
   - Marca los permisos obligatorios:
     - `whatsapp_business_messaging`
     - `whatsapp_business_management`
6. Copia el token permanente generado (inicia por `EAAG...`).

---

### Paso 1.4: Configurar el Webhook de Recepción
1. En [developers.facebook.com](https://developers.facebook.com/), ve a **WhatsApp** → **Configuración** (Configuration).
2. En la tarjeta **Webhook**, presiona **Editar**:
   - **URL de devolución de llamada (Callback URL):**
     `https://<tu-dominio-o-ngrok>/api/v1/webhooks/whatsapp`
   - **Token de verificación (Verify Token):**
     El token configurado en tu panel (por defecto: `nuvex_meta_verify_2026`).
3. Presiona **Verificar y Guardar**. Meta enviará una petición `GET` que el sistema valida automáticamente con el código de desafío `hub.challenge`.
4. En **Campos de Webhook** (Webhook fields), haz clic en **Administrar** y suscribe el evento:
   - `messages` (recibe estados de entrega: `sent`, `delivered`, `read`, `failed` y respuestas del cliente).

---

## 🖥️ 2. Configuración desde el Panel de Administración

El salón o tenant administra su propia conexión sin tocar archivos `.env`:

1. Ingresa al panel de administración en la ruta:
   ```text
   /admin/configuracion-whatsapp
   ```
   *(También accesible desde el menú lateral: **Configuración de Salón** → **Conexión WhatsApp**).*

2. **Panel de Estado en Vivo (Badge Superior):**
   - El sistema consulta los servidores de Meta en tiempo real (`GET https://graph.facebook.com/v21.0/{phone_number_id}`).
   - Muestra:
     - Estado de Conexión: **Conexión Activa** (esmeralda) / **No configurado** (ámbar).
     - Nombre comercial verificado (ej: *Paola Aguilera Studio*).
     - Número telefónico formateado internacionalmente.
     - Calidad del número (`GREEN` / `YELLOW` / `RED`).

3. **Formulario de Credenciales:**
   - **Proveedor Activo:** Selecciona `Meta WhatsApp Cloud API (Oficial)`.
   - **Phone Number ID:** Pega el identificador numérico.
   - **WABA ID:** Pega el identificador de la cuenta comercial.
   - **Token de Acceso (System User):** Pega el token permanente (`EAAG...`).
   - **Token de Verificación de Webhooks:** `nuvex_meta_verify_2026` (o personalízalo).
   - Presiona **Guardar Configuración**.

4. **Tarjeta de Consulta de Webhook:**
   - Contiene la URL completa del webhook y el token de verificación con botones de copiado rápido para ingresarlos en Meta.

---

## 🧪 3. Herramienta de Prueba y Envío Inmediato

Desde el mismo panel `/admin/configuracion-whatsapp` (sección inferior) o desde la consola CLI puedes verificar el flujo de punta a punta:

### Opción A: Prueba desde la Interfaz Web
1. En **Número destinatario (E.164)**: Digita tu número personal con prefijo de país (ej. `+573001234567`).
2. **Modo Plantilla Oficial (Recomendado para pruebas fuera de la ventana de 24 horas):**
   - Activa el toggle **Enviar como Plantilla Oficial**.
   - Nombre de plantilla: `hello_world` (plantilla de bienvenida aprobada de fábrica en todas las cuentas Meta).
   - Código de idioma: `en_US`.
3. Haz clic en **Enviar Mensaje de Prueba**.
4. Recibirás de inmediato la notificación en tu móvil y verás en pantalla el ID de mensaje asignado por Meta (`wamid.HBg...`).

### Opción B: Prueba desde la Consola Artisan
```powershell
# Envío de plantilla hello_world oficial
php artisan whatsapp:test +573001234567 --template=hello_world --lang=en_US

# Envío de texto libre (requiere ventana activa de 24h)
php artisan whatsapp:test +573001234567
```

---

## 📋 4. Políticas de Mensajería y Ventana de 24 Horas de Meta

Meta clasifica los mensajes en dos categorías estrictas:

1. **Mensajes dentro de la ventana de 24 horas (Customer-Initiated):**
   - Cuando el cliente escribe al WhatsApp del salón, se abre una ventana de 24 horas donde el sistema puede responder con texto libre sin costo adicional ni aprobaciones previas.
2. **Mensajes iniciados por el negocio (Business-Initiated):**
   - Todo mensaje enviado por el sistema fuera de la ventana de 24 horas (ej. confirmación de cita al agendar desde la web, recordatorio de cita 24h antes) **DEBE ser una plantilla pre-aprobada por Meta** (`type: "template"`, categoría `UTILITY`).

### Plantillas Homologadas en el Sistema
En `config/whatsapp.php`, los siguientes eventos cuentan con soporte dual:
- `appointment_pending_nequi` (Solicitud recibida, abono Nequi pendiente)
- `appointment_confirmed` (Abono aprobado y cita confirmada)
- `appointment_rejected` (Comprobante no válido, motivo de rechazo)
- `appointment_cancelled` (Cita cancelada)
- `appointment_rescheduled` (Cita reprogramada)
- `appointment_reminder_24h` (Recordatorio 24 horas antes)

> 💡 **Fallback Inteligente:** Si no hay plantilla configurada para un evento o la cuenta aún no la tiene aprobada, el servicio realiza fallback transparente a texto libre si la ventana está abierta, o registra la traza de auditoría sin interrumpir la operación del salón.

---

## 🔒 5. Seguridad y Resiliencia
- **Nunca bloqueante:** Todos los envíos se ejecutan dentro de bloques `try-catch` estructurados; si Meta experimenta lentitud o rechaza un mensaje, la transacción de la cita se guarda con éxito.
- **Normalización E.164:** Se eliminan espacios, guiones, símbolos `+` redundantes o ceros iniciales antes de invocar los endpoints de Meta.
- **Auditoría completa:** Cada petición, respuesta e ID de mensaje devuelto por Meta queda registrado en `storage/logs/laravel.log`.
