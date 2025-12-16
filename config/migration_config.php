<?php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'hrisku_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    'paths' => [
        'migrations' => __DIR__ . '/../migrations/',
        'rollback' => __DIR__ . '/../migrations/rollback/'
    ],
    'table' => 'migrations'
];
