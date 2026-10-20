<?php

return [
    'base_url' => env('CAR_RENT_DASHBOARD_API_BASE', 'https://dev.rentacar-orange.com/api/agency'),
    // Full Authorization header value, including Bearer when required by the provider.
    'authorization' => env('CAR_RENT_DASHBOARD_API_TOKEN', ''),
];
