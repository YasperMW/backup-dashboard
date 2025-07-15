<?php

return [
    'encryption_keys' => [
        'v1' => env('BACKUP_KEY_V1'),
        'v2' => env('BACKUP_KEY_V2'),
        // Add more versions as needed
    ],
    'current_key_version' => env('BACKUP_KEY_CURRENT', 'v1'),
]; 