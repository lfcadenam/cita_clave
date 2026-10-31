# 📱 Guía: Migración a Número Real y Plantillas Oficiales en Meta WhatsApp Cloud API

**Proyecto:** Cita Clave / Nuvex Tecnología  
**Destinado a:** Configuración en producción con número real y plantillas de recordatorio de citas.

---

## ⚠️ Regla de Oro antes de Iniciar (Crítico)

> [!CAUTION]
> **WhatsApp Cloud API no permite coexistencia con la app móvil:**  
> Un número de teléfono **NO puede estar registrado al mismo tiempo en la aplicación WhatsApp o WhatsApp Business de un teléfono celular físico** y en la Cloud API.  
> Si intentas verificar un número que está activo en un celular, Meta arrojará error de registro.
> 
> * **Opción Recomendada (100% segura):** Adquirir una nueva SIM Card prepago o número virtual exclusivo para el asistente virtual del negocio.
> * **Si vas a usar un número existente:** Debes hacer copia de respaldo de tus chats y luego en el celular abrir WhatsApp e ir a:  
>   `Ajustes > Cuenta > Eliminar mi cuenta`. Esto libera el número ante los servidores globales de Meta para que pueda asociarse a la API en la nube.

---

## PARTE 1: Registro y Verificación de tu Número Real

1. **Acceder a la Consola de Desarrolladores:**
   * Entra a [developers.facebook.com](https://developers.facebook.com/) y abre tu aplicación empresarial.
   * En el menú lateral izquierdo, ve a **WhatsApp** > **Configuración de la API** (*API Setup*).

2. **Agregar Número de Teléfono (Paso 5):**
   * Desplázate hacia abajo hasta la sección **"Paso 5: Agregar un número de teléfono"** (*Step 5: Add a phone number*).
   * Haz clic en el botón azul **Agregar número de teléfono** (*Add phone number*).

3. **Configurar el Perfil Empresarial:**
   * **Nombre para mostrar (Display Name):** Ingresa el nombre comercial formal de la clínica, salón o empresa (ej: *Clínica Dental Nuvex* o *La Belle Nails Bogotá*).  
     *(Meta revisa que coincida con la marca o que no infrinja derechos de autor)*.
   * **Categoría:** Selecciona la correspondiente (ej. *Medicina y salud*, *Belleza, cosmética y cuidado personal*).
   * **Zona horaria:** Configura tu zona horaria local (ej: `America/Bogota`).
   * **Descripción:** Breve resumen de los servicios ofrecidos.

4. **Verificación del Número:**
   * Selecciona el código de país (ej: `+57` para Colombia) e ingresa el número telefónico.
   * Elige el método de verificación: **Mensaje de texto (SMS)** o **Llamada de voz**.
   * Recibirás un código de 6 dígitos. Ingrésalo en la pantalla de Meta para completar la vinculación.

5. **Copiar tu nuevo `Phone Number ID` de Producción:**
   * Al finalizar la verificación, Meta te mostrará un nuevo **Identificador de número de teléfono (Phone Number ID)** exclusivo para tu línea real.
   * Guárdalo: Este ID reemplaza al número de pruebas temporal en tu plataforma Cita Clave (`/admin/configuracion-whatsapp`).

---

## PARTE 2: Creación y Aprobación de Plantillas Reales de Citas

> [!IMPORTANT]
> Meta prohíbe iniciar conversaciones de forma proactiva con texto libre si el paciente no ha interactuado en las últimas 24 horas. Para enviar recordatorios o confirmaciones automáticas es **obligatorio usar plantillas aprobadas**.

### Paso 1: Abrir el Administrador de Plantillas
1. En el menú izquierdo de Meta Developers, haz clic en **WhatsApp** > **Plantillas de mensajes** (o ingresa directamente al **WhatsApp Manager** / Administrador de WhatsApp en Meta Business Suite).
2. Haz clic en el botón superior **Crear plantilla** (*Create template*).

### Paso 2: Datos Generales
* **Categoría:** Selecciona **Utilidad** (*Utility*).  
  *(No elijas Marketing: las plantillas de Utilidad son mucho más económicas, se aprueban casi de inmediato y son las exigidas para confirmaciones y citas).*
* **Nombre de la plantilla:** En minúsculas y separado por guiones bajos (sin espacios ni caracteres especiales).  
  *Ejemplo sugerido:* `recordatorio_cita_medica` o `confirmacion_cita_cliente`.
* **Idioma:** Selecciona **Español** (*Spanish*).

### Paso 3: Redactar el Mensaje con Variables
En la sección del cuerpo del mensaje (**Body**), escribe el texto insertando variables `{1}`, `{2}`, `{3}`:

```text
Hola {{1}}, te recordamos que tienes una cita programada en {{2}} para el día {{3}} a las {{4}}.

Por favor confírmanos si podrás asistir o si requieres reagendarla.
```

### Paso 4: Añadir Botones Interactivos de Respuesta Rápida (Quick Replies)
En la parte inferior de la plantilla, dentro de la sección **Botones**:
1. Selecciona tipo: **Respuesta rápida** (*Quick reply*).
2. **Botón 1:** `Confirmar asistencia`
3. **Botón 2:** `Reagendar cita`

*(Estos botones facilitan la respuesta del paciente con un solo toque y disparan los webhooks automáticos hacia Cita Clave)*.

### Paso 5: Cargar Ejemplos y Enviar a Revisión
Meta solicitará valores de muestra para validar la coherencia de la plantilla antes de aprobarla:
* `{{1}}` = *Juan Pérez*
* `{{2}}` = *Clínica Odontológica Nuvex*
* `{{3}}` = *Lunes 27 de Octubre*
* `{{4}}` = *10:30 AM*

Presiona **Enviar** (*Submit*). Las plantillas de categoría Utilidad suelen ser aprobadas por el algoritmo de Meta en un plazo de **1 a 15 minutos** y quedarán marcadas en verde con estado **Aprobado** (*Approved*).

---

## PARTE 3: Disparo de la Plantilla desde Cita Clave / API

Una vez aprobada tu plantilla, el backend enviará a la API de Meta el siguiente payload JSON estructurado:

```bash
curl -X POST \
  'https://graph.facebook.com/v21.0/TU_PHONE_NUMBER_ID_REAL/messages' \
  -H 'Authorization: Bearer TU_SYSTEM_USER_TOKEN_PERMANENTE' \
  -H 'Content-Type: application/json' \
  -d '{
    "messaging_product": "whatsapp",
    "to": "573001234567",
    "type": "template",
    "template": {
      "name": "recordatorio_cita_medica",
      "language": { "code": "es" },
      "components": [
        {
          "type": "body",
          "parameters": [
            { "type": "text", "text": "Juan Pérez" },
            { "type": "text", "text": "La Belle Nails Bogotá" },
            { "type": "text", "text": "Viernes 24 de Octubre" },
            { "type": "text", "text": "03:00 PM" }
          ]
        }
      ]
    }
  }'
```

---

## Resumen del Flujo Operativo en Producción

```
[ Sistema Cita Clave (Cron Job 24h antes) ]
                 │
                 ▼ Envía Plantilla Aprobada (Utilidad)
   [ Meta WhatsApp Cloud API ]
                 │
                 ▼ Notificación con Botones
         [ Paciente en WhatsApp ]
                 │
                 ▼ Presiona: "Confirmar asistencia"
       [ Webhook POST /api/v1/webhooks/whatsapp ]
                 │
                 ▼ Actualiza estado en BD
  [ Panel Cita Clave: Cita Confirmada (Verde) ]
```
