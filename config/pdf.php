<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDF Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the external PDF generation service.
    | This service runs on a separate VPS and handles PDF generation
    | using Puppeteer/Chrome.
    |
    */

    'url' => env('PDF_SERVICE_URL', 'http://localhost:3000'),

    'token' => env('PDF_SERVICE_TOKEN', ''),

    'timeout' => env('PDF_SERVICE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default PDF Options
    |--------------------------------------------------------------------------
    |
    | Default options for PDF generation. These can be overridden
    | when calling the generatePdf method.
    |
    */

    'default_options' => [
        'format' => 'A4',
        'margins' => [5, 5, 5, 5], // top, right, bottom, left in mm
        'background' => true,
        'waitUntilNetworkIdle' => true,
    ],

];
