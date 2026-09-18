<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pasarela Bold (Colombia)
    |--------------------------------------------------------------------------
    */
    'bold' => [
        'api_key' => env('BOLD_API_KEY', 'sandbox_bold_api_key_sample'),
        'secret_key' => env('BOLD_SECRET_KEY', 'sandbox_bold_secret_sample_key'),
        'environment' => env('BOLD_ENV', 'sandbox'), // sandbox | production
        'checkout_url' => env('BOLD_CHECKOUT_URL', 'https://payments.bold.co/v2/checkout'),
        'webhook_secret' => env('BOLD_WEBHOOK_SECRET', 'sandbox_webhook_secret_key'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transferencia Directa Nequi / Daviplata (0% Comisión)
    |--------------------------------------------------------------------------
    */
    'nequi' => [
        'holder_name' => env('NEQUI_HOLDER_NAME', 'Paola Andrea Aguilera Camacho'),
        'account_number' => env('NEQUI_ACCOUNT_NUMBER', '3108889900'),
        'account_type' => 'Nequi / Daviplata',
        'document_id' => env('NEQUI_DOCUMENT_ID', '1.020.304.506'),
        'qr_image_url' => env('NEQUI_QR_IMAGE_URL', '/images/qr-nequi-paola.png'),
        'instructions' => [
            '1. Abre tu aplicación de Nequi o Daviplata.',
            '2. Transfiere el monto exacto del anticipo requerido al número indicado.',
            '3. Toma una captura de pantalla clara del comprobante de transferencia exitosa.',
            '4. Sube la captura en el formulario de confirmación para que Paola valide tu cupo.',
        ],
    ],
];