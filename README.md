# 🗓️ Cita Clave — Plataforma SaaS Multi-Tenant de Agendamiento y Control

> **AGENDA · CLIENTES · CONTROL**  
> Solución SaaS empresarial de agendamiento inteligente, control operativo de salones y cobro híbrido de abonos, desarrollada por **Nuvex Tecnología**.

---

## 🌟 Descripción General

**Cita Clave** es una plataforma tecnológica diseñada para optimizar los ingresos y la operación diaria de salones de belleza, clínicas estéticas, barberías, spas y estudios profesionales independientes.

La plataforma resuelve los desafíos críticos del sector:
- **Cero huecos muertos en agenda:** Algoritmo propietario *Anti-Gaps* que compacta las citas continuas respetando descansos y horarios de almuerzo.
- **Cobro híbrido de anticipos:** Reducción de inasistencias (*no-shows*) mediante pasarela en línea con **Bold** y validación asistida de transferencias por **Nequi** con visor de comprobantes.
- **Arquitectura Multi-Tenant real:** Aislamiento total de datos por salón (`/admin/{slug}`), permitiendo aprovisionar nuevos negocios en segundos desde un panel de **Super Administrador** centralizado (`/superadmin`).
- **Experiencia de clienta ultra-fluida:** Portal web público y SPA en Angular 19 optimizada para smartphones, con catálogo de tratamientos, modal de detalle completo y sincronización con Google/Apple Calendar.

---

## 🛠️ Stack Tecnológico

| Capa / Componente | Tecnología | Descripción |
| :--- | :--- | :--- |
| **Backend & API** | **Laravel 12 (PHP 8.2+)** | API RESTful, Eloquent Multi-Tenancy con Global Scopes, Form Requests y Seeders. |
| **Backoffice SaaS** | **Filament v5** | Doble panel administrativo: Super Admin central (`/superadmin`) y Salones (`/admin/{tenant}`). |
| **Frontend Web** | **Angular 19 (Standalone)** | Arquitectura reactiva basada en Signals, Reactive Forms y componentes Standalone. |
| **Diseño & Estilos** | **Tailwind CSS v3** | Sistema de diseño sobrio inspirado en SaaS *Cierra*, paleta Navy `#1e3a5f` y Teal `#0d9488`. |
| **Base de Datos** | **SQLite / MySQL** | Migraciones con llaves foráneas `tenant_id` e índices únicos compuestos. |
| **Pagos & Integraciones** | **Bold API + Nequi** | Webhooks criptográficos con verificación HMAC y gestión de comprobantes de pago. |

---

## 🏛️ Arquitectura del Sistema

```
                            [ Cliente / Navegador ]
                                      │
                 ┌────────────────────┴────────────────────┐
                 ▼                                         ▼
       [ Frontend Angular 19 ]                   [ Portal Web Blade ]
       (http://localhost:4200)                   (http://localhost:8000)
                 │                                         │
                 └────────────────────┬────────────────────┘
                                      │  REST API / Rutas Web
                                      ▼
                        [ Laravel 12 API Gateway ]
                                      │
            ┌─────────────────────────┼─────────────────────────┐
            ▼                         ▼                         ▼
   [ BelongsToTenant ]      [ Motor Anti-Huecos ]     [ Pasarelas de Pago ]
   (Aislamiento de datos)   (Hold Locks & Horarios)   (Nequi & Webhooks Bold)
            │                         │                         │
            └─────────────────────────┼─────────────────────────┘
                                      ▼
                        [ Base de Datos Multi-Tenant ]
```

---

## 📂 Estructura del Repositorio

```
Proyecto_agendamiento/
├── backend/                       # Núcleo Laravel 12 + Filament v5
│   ├── app/
│   │   ├── Enums/                 # AppointmentStatus, ServiceCategory, UserRole
│   │   ├── Filament/              # Recursos y páginas del panel de Salón
│   │   │   └── SuperAdmin/        # Recursos exclusivos del Super Administrador Nuvex
│   │   ├── Http/Controllers/      # Controladores API y vistas web públicas
│   │   ├── Models/                # Tenant, User, Service, Appointment, Waitlist...
│   │   ├── Providers/Filament/    # AdminPanelProvider y SuperAdminPanelProvider
│   │   ├── Services/              # BookingAvailabilityService (Motor Anti-Gaps)
│   │   └── Traits/                # BelongsToTenant (Global Scope Eloquent)
│   ├── database/
│   │   ├── migrations/            # Migraciones multi-tenant con tenant_id
│   │   └── seeders/               # Base de datos inicial (Paola Aguilera Tenant #1)
│   ├── public/                    # Logotipos oficiales Cita Clave, favicon, CSS admin
│   └── tests/                     # Suite de pruebas automatizadas (Feature & Unit)
│
├── frontend/                      # SPA en Angular 19
│   ├── src/
│   │   ├── app/
│   │   │   ├── core/              # Modelos, ApiService y StorageService
│   │   │   └── features/booking/  # Componente principal de agendamiento y catálogo
│   │   ├── assets/brand/          # Recursos gráficos de marca
│   │   └── public/                # Favicon multiformato y activos estáticos
│   └── angular.json               # Configuración de build con activos mapeados
│
├── BITACORA.md                    # Historial cronológico detallado de ingeniería
├── PLAN_DE_TRABAJO.md             # Plan maestro de trabajo, hitos y arquitectura
└── README.md                      # Documentación ejecutiva y técnica del proyecto
```

---

## 🚀 Puesta en Marcha (Instalación Local)

### 1. Requisitos Previos
- **PHP:** `>= 8.2` (con extensiones `pdo_sqlite`, `mbstring`, `openssl`, `curl`, `gd`)
- **Composer:** `>= 2.5`
- **Node.js:** `>= 18.x` y **NPM**
- **Git**

---

### 2. Configuración del Backend (Laravel 12)

```bash
# 1. Ingresar a la carpeta del backend
cd backend

# 2. Instalar dependencias PHP
composer install

# 3. Configurar variables de entorno
cp .env.example .env

# 4. Generar clave de aplicación
php artisan key:generate

# 5. Ejecutar migraciones y poblar datos iniciales
php artisan migrate:fresh --seed

# 6. Levantar el servidor de desarrollo
php artisan serve
```
El backend estará disponible en: **`http://localhost:8000`**

---

### 3. Configuración del Frontend (Angular 19)

```bash
# 1. Ingresar a la carpeta del frontend
cd frontend

# 2. Instalar paquetes de npm
npm install

# 3. Iniciar el servidor local de desarrollo
npm start
```
El frontend estará disponible en: **`http://localhost:4200`**

---

## 🔐 Credenciales de Acceso a Paneles

### 👑 Super Administrador Global (Control Central Nuvex)
Acceso al aprovisionamiento de salones, configuración de pasarelas y métricas globales de la plataforma:
- **URL:** `http://localhost:8000/superadmin`
- **Usuario:** `admin@nuvex.co`
- **Contraseña:** `Nuvex2026!*`
- **Rol:** `SUPER_ADMIN`

### 🏢 Administrador de Salón (Tenant #1: Paola Aguilera)
Acceso exclusivo a la agenda, catálogo de servicios, bloqueos de horario y lista de espera del salón:
- **URL:** `http://localhost:8000/admin/paola-aguilera`
- **Usuario:** `paola@nuvex-belleza.com`
- **Contraseña:** `Paola12345!`
- **Rol:** `ADMIN`

---

## 🧪 Pruebas Automatizadas y Calidad

El proyecto cuenta con una cobertura integral de pruebas automatizadas que certifican la integridad del motor de disponibilidad, el aislamiento multi-tenant y los flujos de pago:

```bash
# Ejecutar suite de pruebas del backend (34 tests / 223 aserciones)
cd backend
php artisan test
```

```bash
# Verificar compilación estricta de producción del frontend
cd frontend
npx ng build
```

### Resultados Certificados:
- 🟢 **Backend:** `34 passed (223 assertions)` en ~5 segundos.
- 🟢 **Frontend:** `Application bundle generation complete` (0 errores / 0 advertencias críticas).

---

## 💡 Características Clave del Producto

1. **Catálogo Inteligente con Filtro Select:** Búsqueda reactiva por nombre y categoría con dropdown estético para móvil y escritorio.
2. **Modal de Detalle Completo:** Consulta detallada de procedimiento, tiempos de sesión, desglose financiero y beneficios antes de reservar.
3. **Control de Abono Requerido:** Visualización clara de la inversión total, el anticipo para congelar el cupo y el saldo a pagar en estudio.
4. **Lista de Espera Automatizada (Waitlist):** Si la agenda está llena, los clientes pueden postularse para ser notificados ante cualquier cancelación.
5. **Comprobante Digital y Calendarios:** Descarga instantánea de citas en formato `.ics` para sincronización con Google Calendar, Apple Calendar y Outlook.
6. **Integración WhatsApp en 1 Clic:** Notificación preformateada con los datos de la cita y enlace de soporte inmediato.

---

## 📄 Licencia y Créditos

Proyecto desarrollado y mantenido por **Nuvex Tecnología**.  
Todos los derechos reservados © 2026.