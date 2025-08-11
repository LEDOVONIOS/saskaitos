<?php
return [
  'db' => [
    'host' => 'localhost',
    'name' => 'DB_NAME',
    'user' => 'DB_USER',
    'pass' => 'DB_PASS',
    'charset' => 'utf8mb4',
  ],
  'site' => [
    'name' => 'Kieno numeris?',
    'base_url' => 'https://example.com',
    'timezone' => 'Europe/Vilnius',
  ],
  'security' => [
    'enable_recaptcha' => false,
    'recaptcha_site_key' => '',
    'recaptcha_secret' => '',
    // Simple fallback token check for forms if recaptcha disabled
    'simple_token' => 'change-me-strong-token',
  ],
  'admin_email' => 'admin@example.com',
];