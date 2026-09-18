<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notifications Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('WHATSAPP_ENABLED', true),
    
    // Drivers: 'log' (desarrollo local / tests), 'meta' (WhatsApp Cloud API), 'custom_gateway'
    'driver' => env('WHATSAPP_DRIVER', 'log'),

    'admin_phone' => env('WHATSAPP_ADMIN_PHONE', '573001234567'),

    // Configuración para Meta Cloud API (si se usa la API oficial de Meta)
    'meta' => [
        'api_url' => env('WHATSAPP_META_API_URL', 'https://graph.facebook.com/v20.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
    ],
];
