<?php
// Email Configuration
return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'infoasogh@gmail.com', // Your Gmail address
        'password' => 'ddav ftjq mrhz jvhi', // App Password for infoasogh@gmail.com
        'encryption' => 'tls',
        'from_email' => 'infoasogh@gmail.com', // Same as username for Gmail
        'from_name' => 'ASO Online Market',
        'admin_email' => 'infoasogh@gmail.com' // Your admin email
    ],
    'debug' => 2 // 0 = off, 1 = client messages, 2 = client and server messages
];
