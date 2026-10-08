<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Mail\AppointmentBookedAdminMail;
use App\Mail\AppointmentBookedClientMail;
use App\Mail\AppointmentPaymentConfirmedClientMail;
use App\Mail\AppointmentReminder24hClientMail;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class EmailTemplatesPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-envelope';
    protected static string | UnitEnum | null $navigationGroup = 'SISTEMA & PLATAFORMA';
    protected static ?string $title = 'Plantillas de Correo Electrónico';
    protected static ?string $navigationLabel = 'Plantillas de Correo';
    protected static ?string $slug = 'plantillas-correo';
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.super-admin.pages.email-templates';

    public string $selectedTemplate = 'reminder-24h';
    public string $previewDevice = 'desktop'; // 'desktop' (620px) | 'mobile' (380px)
    public ?int $selectedAppointmentId = null;
    public string $testEmail = '';
    public bool $isSendingTest = false;

    public function mount(): void
    {
        $this->testEmail = auth()->user()?->email ?? 'luisferdeveloper@gmail.com';
        $this->selectedAppointmentId = Appointment::latest('id')->first()?->id;
    }

    public function selectTemplate(string $key): void
    {
        if (array_key_exists($key, $this->templates)) {
            $this->selectedTemplate = $key;
        }
    }

    public function setPreviewDevice(string $device): void
    {
        if (in_array($device, ['desktop', 'mobile'])) {
            $this->previewDevice = $device;
        }
    }

    public function getTemplatesProperty(): array
    {
        return [
            'reminder-24h' => [
                'id' => 'reminder-24h',
                'title' => 'Recordatorio 24 Horas Antes',
                'badge' => 'CRON DIARIO 24H',
                'badge_color' => 'amber',
                'recipient' => 'Cliente que agendó',
                'trigger' => 'Ejecutado por el cron diario (appointments:send-reminders) 24 horas antes del horario de la cita.',
                'subject_example' => '⏰ Recordatorio: Tu cita es mañana a las 08:00 en {Salón} — Confirma tu asistencia',
                'description' => 'Notifica al cliente el día previo a su cita. Cuenta con una caja destacada con el horario exacto, saldo pendiente a pagar en local y un botón directo a WhatsApp de la dueña con mensaje precargado para confirmar asistencia en 1 clic.',
                'mailable_class' => AppointmentReminder24hClientMail::class,
                'variables' => [
                    '$appointment' => 'Cita (código, fecha, hora, saldo restante, cliente)',
                    '$tenant' => 'Datos del salón (nombre comercial, dirección física, WhatsApp)',
                    '$service' => 'Servicio a realizar (nombre, duración, precio total)',
                    '$ownerName' => 'Nombre de la dueña o especialista del salón',
                    '$confirmWhatsappUrl' => 'Enlace prearmado para confirmación directa por WhatsApp',
                    '$voucherUrl' => 'Enlace directo a los detalles y resumen en línea de la cita',
                ],
            ],
            'booked-admin' => [
                'id' => 'booked-admin',
                'title' => 'Nueva Cita Agendada (Dueña / Admin)',
                'badge' => 'INSTANTÁNEO',
                'badge_color' => 'teal',
                'recipient' => 'Dueña / Administradora del salón',
                'trigger' => 'Inmediato cuando cualquier cliente completa una reserva en el portal web.',
                'subject_example' => '🔔 Nueva Cita Agendada: {Cliente} — {Servicio}',
                'description' => 'Avisa al equipo del salón de una nueva solicitud de servicio. Incluye datos completos del cliente, notas, servicio, hora, anticipo y un botón directo para abrir WhatsApp con la clienta o gestionar la cita en Filament.',
                'mailable_class' => AppointmentBookedAdminMail::class,
                'variables' => [
                    '$appointment' => 'Datos completos de la reserva y cliente',
                    '$tenant' => 'Salón asociado',
                    '$service' => 'Servicio contratado',
                    '$adminPanelUrl' => 'Enlace al panel de administración para gestionar la cita',
                    '$clientWhatsappUrl' => 'Enlace rápido para abrir WhatsApp con el cliente',
                ],
            ],
            'booked-client' => [
                'id' => 'booked-client',
                'title' => 'Reserva Recibida (Cliente)',
                'badge' => 'INSTANTÁNEO',
                'badge_color' => 'blue',
                'recipient' => 'Cliente que agendó',
                'trigger' => 'Inmediato al completar el formulario de agendamiento y registrar el comprobante.',
                'subject_example' => '✨ Reserva Recibida: {Servicio} en {Salón} — Cita #{Código}',
                'description' => 'Comprobante digital para el cliente confirmando que la solicitud de turno fue recibida y está en proceso de verificación. Contiene resumen de la cita e instrucciones para consultar su estado en vivo.',
                'mailable_class' => AppointmentBookedClientMail::class,
                'variables' => [
                    '$appointment' => 'Cita creada con código único de reserva (#PA-...)',
                    '$tenant' => 'Datos y dirección física del salón',
                    '$service' => 'Tratamiento agendado',
                    '$voucherUrl' => 'Enlace público para consultar el comprobante en vivo',
                    '$whatsappUrl' => 'Enlace de WhatsApp para dudas con el negocio',
                ],
            ],
            'payment-confirmed' => [
                'id' => 'payment-confirmed',
                'title' => 'Pago Validado & Cita Confirmada (Cliente)',
                'badge' => 'APROBACIÓN VIP',
                'badge_color' => 'emerald',
                'recipient' => 'Cliente',
                'trigger' => 'Al verificar el comprobante Nequi en el panel administrativo o confirmación automática de pasarela Bold.',
                'subject_example' => '🎉 ¡Pago verificado y cita confirmada! — {Servicio} en {Salón}',
                'description' => 'Notifica al cliente que su anticipo fue aprobado y su turno está 100% garantizado en la agenda. Incluye botón para descargar archivo de calendario (.ics para Google / Apple Calendar) y ubicación física.',
                'mailable_class' => AppointmentPaymentConfirmedClientMail::class,
                'variables' => [
                    '$appointment' => 'Cita en estado Confirmada con verificación registrada',
                    '$tenant' => 'Ubicación y contacto de la empresa',
                    '$service' => 'Servicio confirmado',
                    '$voucherUrl' => 'Comprobante oficial',
                    '$calendarIcsUrl' => 'Descarga directa de archivo .ics para el calendario del cliente',
                ],
            ],
        ];
    }

    public function getActiveTemplateProperty(): array
    {
        $templates = $this->templates;
        return $templates[$this->selectedTemplate] ?? $templates['reminder-24h'];
    }

    public function getRecentAppointmentsProperty(): array
    {
        return Appointment::with(['service'])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Appointment $apt) => [
                'id' => $apt->id,
                'label' => "#{$apt->appointment_number} — {$apt->client_name} ({$apt->service?->name})",
            ])
            ->toArray();
    }

    public function getPreviewAppointment(): Appointment
    {
        if ($this->selectedAppointmentId) {
            $apt = Appointment::with(['tenant', 'service'])->find($this->selectedAppointmentId);
            if ($apt) {
                return $apt;
            }
        }

        $latest = Appointment::with(['tenant', 'service'])->latest('id')->first();
        if ($latest) {
            return $latest;
        }

        // Fallback robust mock
        $tenant = Tenant::first() ?? new Tenant([
            'name' => 'Estudio Paola Aguilera',
            'short_name' => 'Paola Aguilera',
            'business_type' => 'ESTUDIO DE BELLEZA',
            'slug' => 'paola-aguilera',
            'whatsapp_number' => '3103248385',
            'address' => 'Carrera 15 # 93-75, Chicó, Bogotá',
        ]);

        $service = Service::first() ?? new Service([
            'name' => 'Pestañas Volumen Ruso',
            'duration_minutes' => 120,
            'price' => 120000,
            'required_deposit' => 50000,
        ]);

        $mock = new Appointment([
            'appointment_number' => 'PA-DEMO-2026',
            'client_name' => 'Luis Cadena',
            'client_phone' => '3187756857',
            'client_email' => 'luis@ejemplo.com',
            'appointment_date' => now()->addDay(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'total_amount' => 120000,
            'deposit_amount' => 50000,
            'deposit_paid' => 50000,
            'balance_due' => 70000,
        ]);
        $mock->setRelation('tenant', $tenant);
        $mock->setRelation('service', $service);

        return $mock;
    }

    public function getPreviewHtmlProperty(): string
    {
        $appointment = $this->getPreviewAppointment();
        $tpl = $this->activeTemplate;
        $mailableClass = $tpl['mailable_class'];

        try {
            /** @var \Illuminate\Mail\Mailable $mailable */
            $mailable = new $mailableClass($appointment);
            return $mailable->render();
        } catch (\Throwable $e) {
            return '<div style="padding: 32px; color: #dc2626; font-family: sans-serif;"><strong>Error al renderizar plantilla:</strong> ' . e($e->getMessage()) . '</div>';
        }
    }

    public function getSmtpStatusProperty(): array
    {
        $mailer = config('mail.default', 'smtp');
        $host = config('mail.mailers.smtp.host', 'smtp.gmail.com');
        $port = config('mail.mailers.smtp.port', 587);
        $encryption = config('mail.mailers.smtp.encryption', 'tls');
        $username = config('mail.mailers.smtp.username');
        $fromAddress = config('mail.from.address');
        $isConfigured = !empty($username) && !empty(config('mail.mailers.smtp.password'));

        return [
            'mailer' => $mailer,
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'from_address' => $fromAddress,
            'username' => $username,
            'is_configured' => $isConfigured,
        ];
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'testEmail' => 'required|email',
        ], [
            'testEmail.required' => 'Ingresa un correo electrónico de destino.',
            'testEmail.email' => 'El correo electrónico no tiene un formato válido.',
        ]);

        $this->isSendingTest = true;

        try {
            $appointment = $this->getPreviewAppointment();
            $tpl = $this->activeTemplate;
            $mailableClass = $tpl['mailable_class'];

            Mail::to($this->testEmail)->send(new $mailableClass($appointment));

            Notification::make()
                ->title('Correo de prueba enviado')
                ->body("La plantilla '{$tpl['title']}' ha sido enviada exitosamente a {$this->testEmail}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error al enviar correo de prueba')
                ->body('Fallo en el servidor SMTP: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } finally {
            $this->isSendingTest = false;
        }
    }

    public static function renderStandalonePreview(Request $request, string $key): Response
    {
        $instance = new static();
        $instance->selectedTemplate = $key;
        if ($request->has('appointment_id')) {
            $instance->selectedAppointmentId = (int) $request->get('appointment_id');
        }

        $html = $instance->previewHtml;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
