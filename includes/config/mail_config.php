<?php
// Email Configuration
return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'minatoflash82@gmail.com', // Your Gmail address
        'password' => 'guex clso znpw tuga', // You'll need to generate an App Password
        'encryption' => 'tls',
        'from_email' => 'minatoflash82@gmail.com', // Same as username for Gmail
        'from_name' => 'ASO Online Market',
        'admin_email' => 'minatoflash82@gmail.com' // Your admin email
    ],
    'debug' => 2 // 0 = off, 1 = client messages, 2 = client and server messages
];
