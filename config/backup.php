<?php

return [
    'encryption_keys' => [
        'v1' => env('BACKUP_KEY_V1'),
        'v2' => env('BACKUP_KEY_V2'),
        // Add more versions as needed
    ],
    'current_key_version' => env('BACKUP_KEY_CURRENT', 'v1'),

    // Linux SFTP / Immutable backup settings
    'linux_host' => env('BACKUP_LINUX_HOST', '192.168.56.106'),
    'linux_user' => env('BACKUP_LINUX_USER', 'laravel_user'),
    'linux_pass' => env('BACKUP_LINUX_PASS', 'your_password'),
    'remote_path' => env('BACKUP_LINUX_PATH', '/srv/backups/laravel'),
];
