<?php

declare(strict_types=1);

return [
    'paths' => [
        'migrations' => __DIR__ . '/../db/migrations',
        'seeds' => __DIR__ . '/../db/seeds',
    ],
    'environments' => [
        'default_migration_table' => '_phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'smps',
            'user' => 'root',
            'pass' => '',
        ],
        // Use only a dedicated disposable database whose name ends in `_test`.
        'testing' => [
            'adapter' => 'mysql',
            'host' => '127.0.0.1',
            'name' => 'smps_test',
            'user' => 'smps_test',
            'pass' => '',
        ],
    ],
    'version_order' => 'creation',
];
