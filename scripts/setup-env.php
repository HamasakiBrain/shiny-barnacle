<?php

// Generates local credentials without logging them. Existing .env is never replaced.
$root = dirname(__DIR__);

if (file_exists($root . '/.env')) {
    fwrite(STDOUT, ".env already exists; left unchanged.\n");
    exit(0);
}

$content = file_get_contents($root . '/.env.example');

foreach ([
    'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)),
    'DB_PASSWORD' => bin2hex(random_bytes(24)),
    'DB_ROOT_PASSWORD' => bin2hex(random_bytes(24)),
    'DEMO_PASSWORD' => bin2hex(random_bytes(12)),
] as $key => $value) {
    $content = preg_replace('/^' . preg_quote($key, '/') . '=\r?$/m', $key . '=' . $value, $content);
}
file_put_contents($root . '/.env', $content);
fwrite(STDOUT, ".env created. Read DEMO_PASSWORD locally for demo login.\n");
