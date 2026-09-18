# 📋 Plan de Trabajo y Hoja de Ruta de Desarrollo
## Plataforma de Agendamiento Inteligente y Reservas para Servicios de Belleza
**Cliente:** Paola Andrea Aguilera Camacho  
**Líder Técnico:** Luis Cadena — Nuvex Tecnología  
**Ubicación del Proyecto:** `D:\Users\Usuario\Documents\Nuvex\Proyecto_agendamiento`  
**Fecha de Inicio:** Septiembre de 2026  
**Fecha Estimada de Salida a Producción:** Noviembre / Diciembre de 2026  

---

## 1. Ficha Técnica y Arquitectura del Sistema

```
┌────────────────────────────────────────────────────────────────────────┐
│                          ARQUITECTURA GENERAL                          │
└────────────────────────────────────────────────────────────────────────┘

 [ CLIENTAS (Móvil / PWA) ]              [ ADMINISTRACIÓN (Paola) ]
             │                                        │
             ▼                                        ▼
 ┌───────────────────────────┐            ┌───────────────────────────┐
 │ Portal Web Responsive     │            │ Panel Backoffice          │
 │ Catálogo • Horarios       │            │ FilamentPHP v5            │
 │ Anti-Huecos • Checkout    │            │ Calendario • Validación 1c│
 └─────────────┬─────────────┘            └─────────────┬─────────────┘
               │                                        │
               └───────────────────┬────────────────────┘
                                   ▼
                      ┌───────────────────────────┐
                      │    API REST / BACKEND     │
                      │    Laravel 12 + PHP 8.2   │
                      └─────────────┬─────────────┘
                                    │
         ┌──────────────────────────┼──────────────────────────┐
         ▼                          ▼                          ▼
┌──────────────────┐       ┌──────────────────┐       ┌──────────────────┐
│  BASE DE DATOS   │       │ PASARELA DE PAGO │       │  NOTIFICACIONES  │
│  MySQL 8.0+ /    │       │ Bold / Wompi API │       │ Correo Digital + │
│  MariaDB         │       │ + Nequi Directo  │       │ WhatsApp API     │
└──────────────────┘       └──────────────────┘       └──────────────────┘
```

* **Backend:** Laravel 12 (PHP 8.2+) con arquitectura modular y limpia.
* **Panel Administrativo:** FilamentPHP v5 (Livewire 3 + Tailwind CSS) con diseño claro, elegante y responsivo.
* **Frontend de Clientes:** Single Page Application (SPA / PWA) móvil-first de carga ultra-rápida.
* **Pasarela de Pagos:** Bold / Wompi (Webhooks con validación de firmas) + Módulo de Transferencias Nequi directas con validación manual en 1 clic.
* **Seguridad:** Cifrado SSL, protección CSRF, sanitización de entradas y cumplimiento de Habeas Data (Ley 1581 de 2012).

---

## 2. Cronograma Faseado (4 Semanas de Desarrollo)

```
Gantt Chart - Fases de Desarrollo (4 Semanas):
════════════════════════════════════════════════════════════════════════════════
Fase 1: Configuración, Modelado de BD y Catálogo   [████████]
Fase 2: Motor Inteligente Anti-Huecos & Agenda              [████████]
Fase 3: Pagos Híbridos (Bold + Nequi Directo)                        [████████]
Fase 4: Portal Clientes & Panel de Control Paola                              [████████]
Fase 5: Pruebas, Despliegue, Dominio y Salida                                          [████████]
════════════════════════════════════════════════════════════════════════════════
Semana:          Semana 1              Semana 2              Semana 3              Semana 4
```

---

## 3. Desglose Detallado de Tareas por Fases

### 🔹 FASE 1: Inicialización, Modelado de Datos y Catálogo (Semana 1)
* [ ] **1.1 Configuración del Entorno:**
  * Inicializar proyecto Laravel 12 en `Proyecto_agendamiento/backend`.
  * Instalar Filament v5 y componentes base.
  * Configurar variables de entorno (`.env`), conexión a base de datos y llaves de cifrado.
* [ ] **1.2 Diseño y Migraciones de Base de Datos:**
  * `users`: Control de acceso con roles (`ADMIN`, `CLIENT`).
  * `services`: Servicios de belleza (nombre, descripción, duración en minutos, precio base, monto de abono, foto, estado activo).
  * `working_schedules`: Horarios laborales por día de la semana (hora apertura, hora cierre, activo).
  * `blocked_slots`: Bloqueos de almuerzo, descansos, imprevistos o días festivos (fecha, hora inicio, hora fin, motivo).
  * `appointments`: Citas (código de orden, datos del cliente, servicio, fecha, hora inicio, hora fin, estado, método de abono, comprobante Nequi adjunto, notas).
  * `waitlists`: Lista de espera por fecha y servicio solicitado.
* [ ] **1.3 Semillas de Datos (Seeders):**
  * Crear usuario administrador de Paola.
  * Cargar catálogo inicial de servicios con duraciones reales y precios.

---

### 🔹 FASE 2: Motor de Agendamiento Inteligente ("Anti-Huecos") (Semana 1-2)
* [ ] **2.1 Algoritmo de Cálculo de Disponibilidad Dinámica:**
  * Servicio que recibe la fecha y la duración del servicio solicitado ($N$ minutos).
  * Cruce en tiempo real con horarios laborales, citas ya agendadas y franjas de almuerzo bloqueadas.
* [ ] **2.2 Regla de Contigüidad ("Anti-Huecos"):**
  * Filtrar únicamente los turnos que inicien inmediatamente después de una cita previa, inmediatamente después del almuerzo o al inicio de la jornada.
  * Impedir que una clienta reserve un turno que deje un espacio libre menor a 45 minutos (tiempo no utilizable).
* [ ] **2.3 Bloqueo Temporal de Cita (Lock de Reserva):**
  * Bloqueo provisional de 15 minutos mientras la clienta completa el pago o sube el comprobante de transferencia.

---

### 🔹 FASE 3: Módulo Híbrido de Abonos y Pagos (Semana 2)
* [ ] **3.1 Integración de Pasarela de Pagos (Bold / Wompi):**
  * Creación de sesión de checkout para pago en línea (PSE, Tarjetas, Nequi pasarela).
  * Controlador de Webhooks para confirmación automática instantánea vía firma criptográfica.
* [ ] **3.2 Módulo de Transferencia Directa a Nequi (0% Comisión):**
  * Pantalla de checkout con datos bancarios y código QR de Paola.
  * Campo de carga segura de archivos (`FileUpload`) para capturas de pantalla de la transferencia.
  * Transición de estado a `PENDING_VERIFICATION`.
* [ ] **3.3 Bandeja de Validación Rápida en Panel de Paola:**
  * Visor de comprobante en alta resolución.
  * Botones de acción rápida en 1 clic: **`[✓ Aprobar Abono y Confirmar]`** y **`[✕ Rechazar]`**.

---

### 🔹 FASE 4: Portal Público de Clientes (PWA) y Panel de Paola (Semana 3)
* [ ] **4.1 Portal Público de Clientas (Mobile-First):**
  * Catálogo visual interactivo con duración y precios.
  * Selector de fecha con vista de turnos compactos consecutivos.
  * Formulario de reserva ágil (Nombre, WhatsApp, Correo).
  * Pantalla de confirmación con comprobante digital descargable y botón para añadir a Google Calendar / Apple Calendar.
  * Módulo de autogestión de cancelación / reprogramación (sujeto a regla de 24 horas).
  * Formulario de inscripción a Lista de Espera en horarios agotados.
* [ ] **4.2 Panel Administrativo para Paola (Filament v5):**
  * **Calendario Visual:** Vista semanal y diaria con código de colores por estado de cita y pago.
  * **Botón de Bloqueo Rápido:** Bloqueo de almuerzos o descansos en 1 clic sin configuraciones complejas.
  * **CRM de Clientas:** Directorio con historial de citas, preferencias y notas de tratamiento.
  * **Gestor de Lista de Espera:** Reasignación ágil de cupos cancelados.
  * **Reportes Financieros:** Métricas de abonos recaudados y dinero pendiente por cobrar en el local.

---

### 🔹 FASE 5: Notificaciones, Pruebas y Despliegue en Producción (Semana 4)
* [ ] **5.1 Sistema de Notificaciones:**
  * Envío de confirmación digital inmediata por correo electrónico.
  * Plantilla y enlace de WhatsApp para recordatorio 24 horas antes de la cita.
* [ ] **5.2 Pruebas de Calidad (QA & Feature Tests):**
  * Pruebas del motor anti-huecos en diferentes escenarios de duración.
  * Pruebas de pagos con pasarela y subida de comprobantes Nequi.
  * Pruebas de políticas de cancelación de 24 horas.
  * Validación de diseño responsivo en iPhone, Android y tablets.
* [ ] **5.3 Despliegue en Servidor Cloud y Dominio:**
  * Configuración de servidor en la nube de alto rendimiento.
  * Instalación de certificado de seguridad SSL (HTTPS).
  * Vinculación del dominio web propio de Paola.
* [ ] **5.4 Capacitación y Entrega:**
  * Sesión de capacitación personalizada a Paola.
  * Puesta en marcha oficial lista para la temporada de diciembre.

---

## 4. Estructura de Directorios del Proyecto

```
Proyecto_agendamiento/
├── backend/                   # API Laravel 12 + Filament v5
│   ├── app/
│   │   ├── Enums/             # AppointmentStatus, PaymentMethod, UserRole
│   │   ├── Filament/          # Resources, Pages, Widgets de Administración
│   │   ├── Http/Controllers/  # BookingController, PaymentController, WebhookController
│   │   ├── Models/            # Appointment, Service, BlockedSlot, Waitlist
│   │   └── Services/          # BookingEngineService (Lógica Anti-Huecos)
│   ├── database/
│   │   ├── migrations/        # Esquema relacional
│   │   └── seeders/           # Catálogo inicial de Paola
│   └── tests/                 # Suite de pruebas automatizadas
├── frontend/                  # Portal Web de Clientas (PWA / Responsive)
│   ├── src/
│   │   ├── components/        # Catálogo, Calendario, Checkout, Comprobante
│   │   └── services/          # Conexión API Backend
│   └── public/                # Assets, logos, iconos PWA
└── docs/                      # Manual de usuario y propuesta comercial
```

---

## 5. Próximos Pasos para Iniciar
1. **Aprobación de la Propuesta por parte de Paola.**
2. **Definición de la Modalidad Comercial** (*Modalidad 1: Pago Único* vs. *Modalidad 2: Suscripción SaaS Mensual*).
3. **Recopilación de Insumos:** Catálogo de servicios de Paola (nombres, fotos, precios y duraciones exactas), horarios de trabajo y datos de cuenta Nequi para abonos.
4. **Ejecución del Sprint 1:** Inicialización del repositorio y base de datos en `Proyecto_agendamiento`.
