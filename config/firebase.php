<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Project Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración del proyecto Firebase para almacenamiento de imágenes
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID', 'mundo-software-images'),
    
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/credentials.json')),
    
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'mundo-software-images.appspot.com'),
    
    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    
    'storage' => [
        'default_folder' => env('FIREBASE_DEFAULT_FOLDER', 'images'),
        'max_file_size' => env('FIREBASE_MAX_FILE_SIZE', 5242880), // 5MB en bytes
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | URL Configuration
    |--------------------------------------------------------------------------
    */
    
    'url' => [
        'base' => env('FIREBASE_STORAGE_URL', 'https://firebasestorage.googleapis.com/v0/b/mundo-software-images.appspot.com/o/'),
        'token' => env('FIREBASE_URL_TOKEN', ''), // Token opcional para URLs públicas
    ],
    
    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    | Deshabilitar en desarrollo local si hay problemas con certificados SSL
    */
    
    'verify_ssl' => env('FIREBASE_VERIFY_SSL', false),
];
