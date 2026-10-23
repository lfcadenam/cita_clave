<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notifications Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('WHATSAPP_ENABLED', true),
    
    // Drivers: 'evolution' (Gateway QR Baileys), 'meta' (WhatsApp Cloud API), 'log' (desarrollo local / tests)
    'driver' => env('WHATSAPP_DRIVER', 'evolution'),

    'admin_phone' => env('WHATSAPP_ADMIN_PHONE', '573106080402'),

    // Configuración para Evolution API (Gateway QR Baileys - Sin costo)
    'evolution' => [
        'base_url' => rtrim(env('EVOLUTION_API_URL', 'http://host.docker.internal:8085'), '/'),
        'api_key' => env('EVOLUTION_API_KEY', 'nuvex_evolution_key_2026'),
        'instance_name' => env('EVOLUTION_INSTANCE_NAME', 'paola_estudio'),
    ],

    // Configuración para Meta Cloud API (API Oficial de WhatsApp Business)
    'meta' => [
        'api_url' => rtrim(env('WHATSAPP_META_API_URL', 'https://graph.facebook.com/v21.0'), '/'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
        'waba_id' => env('WHATSAPP_WABA_ID', ''),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'nuvex_meta_verify_2026'),
        'app_secret' => env('WHATSAPP_APP_SECRET', ''),
        'language' => env('WHATSAPP_META_LANGUAGE', 'es'),
        'use_templates' => (bool) env('WHATSAPP_META_USE_TEMPLATES', false),

        // Nombres de plantillas aprobadas en Meta WhatsApp Manager (Categoría: UTILITY)
        'templates' => [
            'booking_created' => env('WHATSAPP_TEMPLATE_BOOKING_CREATED', 'cita_recibida'),
            'admin_new_booking' => env('WHATSAPP_TEMPLATE_ADMIN_NEW_BOOKING', 'alerta_nueva_cita_admin'),
            'deposit_approved' => env('WHATSAPP_TEMPLATE_DEPOSIT_APPROVED', 'cita_confirmada_abono'),
            'deposit_rejected' => env('WHATSAPP_TEMPLATE_DEPOSIT_REJECTED', 'novedad_comprobante_cita'),
            'appointment_cancelled' => env('WHATSAPP_TEMPLATE_CANCELLED', 'cita_cancelada'),
            'appointment_rescheduled' => env('WHATSAPP_TEMPLATE_RESCHEDULED', 'cita_reagendada'),
            'reminder_24h' => env('WHATSAPP_TEMPLATE_REMINDER_24H', 'recordatorio_cita_24h'),
            'booking_confirmed' => env('WHATSAPP_TEMPLATE_BOOKING_CONFIRMED', 'reserva_confirmada'),
        ],
    ],
];

