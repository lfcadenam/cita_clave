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
        'base_url' => rtrim(env('EVOLUTION_API_URL', 'http://localhost:8080'), '/'),
        'api_key' => env('EVOLUTION_API_KEY', 'nuvex_evolution_key_2026'),
        'instance_name' => env('EVOLUTION_INSTANCE_NAME', 'paola_estudio'),
    ],

    // Configuración para Meta Cloud API (si se usa la API oficial de Meta)
    'meta' => [
        'api_url' => env('WHATSAPP_META_API_URL', 'https://graph.facebook.com/v20.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
    ],
];
