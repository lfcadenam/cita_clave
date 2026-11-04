# 📱 Plantillas Oficiales de WhatsApp (Meta Cloud API) — Cita Clave

> **Proyecto:** Sistema de Agendamiento Inteligente y Reservas (Cita Clave / Nuvex Tecnología)  
> **Entorno:** Meta WhatsApp Manager ([business.facebook.com/wa/manage/message-templates](https://business.facebook.com/wa/manage/message-templates/))  
> **Categoría Obligatoria para Todas:** `Utilidad` (*Utility*)  
> **Idioma Configurado:** `Spanish (COL)` (`es_CO`)  

---

## 📌 Guía Rápida de Creación en Meta

1. Entra a [Meta WhatsApp Manager > Plantillas de mensajes](https://business.facebook.com/wa/manage/message-templates/).
2. Haz clic en **Crear plantilla** (*Create template*).
3. Selecciona siempre:
   - **Categoría:** `Utilidad` (*Utility*). *(No uses Marketing; Utilidad se aprueba en 1 a 5 minutos y tiene tarifas más bajas).*
   - **Idioma:** `Spanish (COL)` (o `Español`).
4. Copia y pega el **Nombre exacto** y el **Texto del cuerpo (Body)**.
5. En **Muestras de variables**, ingresa los valores de ejemplo que figuran en cada ficha.
6. Pulsa **Enviar** (*Submit*).

---

## 📋 Catálogo Completo de Plantillas

### 1. `confirmacion_cita` / `reserva_confirmada`
* **Propósito:** Notificación principal de confirmación de cita al agendar.
* **Nombre de la plantilla:** `confirmacion_cita` *(o `reserva_confirmada`)*
* **Categoría:** `Utilidad`
* **Idioma:** `Spanish (COL)`
* **Texto del Cuerpo (Body):**
```text
Hola {{1}}, te confirmamos que tu cita #{{2}} para {{3}} ha sido agendada con éxito para el día {{4}}.

Puedes consultar tu comprobante aquí: {{5}}

¡Te esperamos puntualmente!
```
* **Muestras de variables:**
  * `{{1}}`: `Camila`
  * `{{2}}`: `1045`
  * `{{3}}`: `Manicura Semipermanente`
  * `{{4}}`: `Viernes 24 de Octubre a las 3:00 PM`
  * `{{5}}`: `https://labellenailsbog.com/comprobante/1045`

---

### 2. `recordatorio_cita_24h`
* **Propósito:** Recordatorio automático enviado 24 horas antes para reducir ausencias (*no-shows*).
* **Nombre de la plantilla:** `recordatorio_cita_24h`
* **Categoría:** `Utilidad`
* **Idioma:** `Spanish (COL)`
* **Texto del Cuerpo (Body):**
```text
Hola {{1}}, te recordamos que tienes una cita programada para {{2}} el día {{3}}.

Saldo restante a pagar en el salón: {{4}}.
Puedes ver los detalles y ubicación en tu comprobante: {{5}}

¡Te esperamos puntualmente!
```
* **Muestras de variables:**
  * `{{1}}`: `Camila`
  * `{{2}}`: `Manicura Semipermanente`
  * `{{3}}`: `Mañana Viernes a las 3:00 PM`
  * `{{4}}`: `$25.000 COP`
  * `{{5}}`: `https://labellenailsbog.com/comprobante/1045`

---

### 3. `cita_reagendada`
* **Propósito:** Notificar a la clienta cuando se actualiza el día o la hora de su turno.
* **Nombre de la plantilla:** `cita_reagendada`
* **Categoría:** `Utilidad`
* **Idioma:** `Spanish (COL)`
* **Texto del Cuerpo (Body):**
```text
Hola {{1}}, tu cita #{{2}} para {{3}} ha sido reagendada con éxito para el día {{4}}.

Puedes consultar tu comprobante actualizado aquí: {{5}}

¡Te esperamos puntualmente!
```
* **Muestras de variables:**
  * `{{1}}`: `Camila`
  * `{{2}}`: `1045`
  * `{{3}}`: `Manicura Semipermanente`
  * `{{4}}`: `Sábado 25 de Octubre a las 4:00 PM`
  * `{{5}}`: `https://labellenailsbog.com/comprobante/1045`

---

### 4. `cita_cancelada`
* **Propósito:** Aviso oficial cuando una cita es cancelada por el cliente o el salón.
* **Nombre de la plantilla:** `cita_cancelada`
* **Categoría:** `Utilidad`
* **Idioma:** `Spanish (COL)`
* **Texto del Cuerpo (Body):**
```text
Hola {{1}}, te informamos que tu cita #{{2}} para {{3}} programada para el día {{4}} ha sido cancelada.

Si deseas programar un nuevo horario, puedes ingresar nuevamente a nuestra plataforma de agendamiento.
```
* **Muestras de variables:**
  * `{{1}}`: `Camila`
  * `{{2}}`: `1045`
  * `{{3}}`: `Manicura Semipermanente`
  * `{{4}}`: `Viernes 24 de Octubre a las 3:00 PM`

---

### 5. `cita_confirmada_abono`
* **Propósito:** Confirmar a la clienta que su comprobante de pago/abono Nequi o transferencia fue aprobado.
* **Nombre de la plantilla:** `cita_confirmada_abono`
* **Categoría:** `Utilidad`
* **Idioma:** `Spanish (COL)`
* **Texto del Cuerpo (Body):**
```text
Hola {{1}}, hemos validado exitosamente el abono de tu cita #{{2}} para {{3}} del día {{4}}.

Tu reserva está 100% confirmada. Puedes ver tu comprobante aquí: {{5}}

¡Nos vemos pronto!
```
* **Muestras de variables:**
  * `{{1}}`: `Camila`
  * `{{2}}`: `1045`
  * `{{3}}`: `Manicura Semipermanente`
  * `{{4}}`: `Viernes 24 de Octubre a las 3:00 PM`
  * `{{5}}`: `https://labellenailsbog.com/comprobante/1045`

---

### 6. `novedad_comprobante_cita`
* **Propósito:** Informar a la clienta cuando su comprobante de pago es ilegible, incompleto o rechazado.
* **Nombre de la plantilla:** `novedad_comprobante_cita`
* **Categoría:** `Utilidad`
* **Idioma:** `Spanish (COL)`
* **Texto del Cuerpo (Body):**
```text
Hola {{1}}, tenemos una novedad con el comprobante de tu cita #{{2}} para {{3}}.

Motivo: {{4}}

Por favor sube nuevamente tu comprobante desde el enlace para no perder tu turno: {{5}}
```
* **Muestras de variables:**
  * `{{1}}`: `Camila`
  * `{{2}}`: `1045`
  * `{{3}}`: `Manicura Semipermanente`
  * `{{4}}`: `El valor transferido no coincide con el anticipo requerido`
  * `{{5}}`: `https://labellenailsbog.com/comprobante/1045`

---

### 7. `cita_recibida`
* **Propósito:** Acuse de recibo inmediato al registrar la cita (cuando requiere abono previo para apartar el turno).
* **Nombre de la plantilla:** `cita_recibida`
* **Categoría:** `Utilidad`
* **Idioma:** `Spanish (COL)`
* **Texto del Cuerpo (Body):**
```text
Hola {{1}}, hemos recibido tu solicitud de cita #{{2}} para {{3}} el día {{4}}.

Para asegurar tu turno, recuerda adjuntar tu comprobante de abono en el siguiente enlace: {{5}}
```
* **Muestras de variables:**
  * `{{1}}`: `Camila`
  * `{{2}}`: `1045`
  * `{{3}}`: `Manicura Semipermanente`
  * `{{4}}`: `Viernes 24 de Octubre a las 3:00 PM`
  * `{{5}}`: `https://labellenailsbog.com/comprobante/1045`

---

### 8. `alerta_nueva_cita_admin`
* **Propósito:** Alerta instantánea enviada al WhatsApp del administrador del salón cuando entra una nueva reserva.
* **Nombre de la plantilla:** `alerta_nueva_cita_admin`
* **Categoría:** `Utilidad`
* **Idioma:** `Spanish (COL)`
* **Texto del Cuerpo (Body):**
```text
Nueva cita registrada. Cliente: {{1}} (Tel: {{2}}). Servicio: {{3}} para el día {{4}}.

Revisa los detalles en el panel de administración: {{5}}
```
* **Muestras de variables:**
  * `{{1}}`: `Camila Morales`
  * `{{2}}`: `3194086814`
  * `{{3}}`: `Manicura Semipermanente`
  * `{{4}}`: `Viernes 24 de Octubre a las 3:00 PM`
  * `{{5}}`: `https://labellenailsbog.com/admin/appointments`

---

## ⚙️ Mapeo en el `.env` del Backend

En tu archivo [`backend/.env`](file:///d:/Users/Usuario/Documents/Nuvex/Proyecto_agendamiento/backend/.env), estas plantillas ya están enlazadas:

```env
WHATSAPP_ENABLED=true
WHATSAPP_DRIVER=meta
WHATSAPP_META_API_URL=https://graph.facebook.com/v25.0
WHATSAPP_PHONE_NUMBER_ID=1095482376978675
WHATSAPP_ACCESS_TOKEN=EAAQT4Halq...
WHATSAPP_META_LANGUAGE=es_CO
WHATSAPP_META_USE_TEMPLATES=true

# Mapeo de Plantillas
WHATSAPP_TEMPLATE_BOOKING_CONFIRMED=confirmacion_cita
WHATSAPP_TEMPLATE_REMINDER_24H=recordatorio_cita_24h
WHATSAPP_TEMPLATE_RESCHEDULED=cita_reagendada
WHATSAPP_TEMPLATE_CANCELLED=cita_cancelada
WHATSAPP_TEMPLATE_DEPOSIT_APPROVED=cita_confirmada_abono
WHATSAPP_TEMPLATE_DEPOSIT_REJECTED=novedad_comprobante_cita
WHATSAPP_TEMPLATE_BOOKING_CREATED=cita_recibida
WHATSAPP_TEMPLATE_ADMIN_NEW_BOOKING=alerta_nueva_cita_admin
```
