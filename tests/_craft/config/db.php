<?php

return [
    'server' => getenv('DB_SERVER') ?: 'db',
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: 'root',
    'database' => 'testing',
    'schema' => getenv('DB_SCHEMA'),
    'tablePrefix' => '',
    'driver' => getenv('DB_DRIVER') ?: 'mysql',
    'port' => getenv('DB_PORT') ?: 3306,
];
