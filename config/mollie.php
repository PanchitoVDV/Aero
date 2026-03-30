<?php

return [
    'key' => env('MOLLIE_KEY', ''),
    'webhook_url' => env('MOLLIE_WEBHOOK_URL', ''),
    'currency' => env('CLOUDITO_CURRENCY', 'EUR'),
    'locale' => 'nl_NL',
    'redirect_url' => env('APP_URL') . '/dashboard/orders',
];
