<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            // Tanpa rute storage/{path}: foto laporan dan berkas pribadi hanya disajikan
            // lewat rute yang memeriksa Policy, bukan URL bertanda tangan dari APP_KEY.
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // Supabase Storage speaks the S3 protocol. Keys are server-side only (PRD §79).
        'supabase' => [
            'driver' => 's3',
            'key' => env('SUPABASE_STORAGE_KEY'),
            'secret' => env('SUPABASE_STORAGE_SECRET'),
            'region' => env('SUPABASE_STORAGE_REGION', 'ap-southeast-1'),
            'bucket' => env('SUPABASE_STORAGE_BUCKET'),
            'endpoint' => env('SUPABASE_STORAGE_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Community Photo Disk
    |--------------------------------------------------------------------------
    |
    | Disk used for community trail condition photos. Defaults to the local public
    | disk so the feature works without Supabase Storage credentials; set it to
    | "supabase" once SUPABASE_STORAGE_* is configured.
    |
    */

    'report_photos_disk' => env('REPORT_PHOTOS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Profile Photo Disk
    |--------------------------------------------------------------------------
    |
    | Disk untuk foto profil (W7). Bawaannya disk privat lokal; ganti ke "supabase"
    | bersama foto laporan. Berkasnya tidak pernah publik: selalu disajikan lewat
    | rute pemiliknya sendiri (profile.photo.show).
    |
    */

    'avatars_disk' => env('AVATARS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Evidence Archive Disk
    |--------------------------------------------------------------------------
    |
    | Disk arsip bukti status resmi (W8, spec v3 bagian 10.7). Bawaannya disk privat
    | lokal; berkasnya tidak pernah publik dan hanya diunduh lewat rute
    | curator.evidence.show sesudah sidik SHA-256-nya cocok.
    |
    */

    'evidence_disk' => env('EVIDENCE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Mountain Photo Disk
    |--------------------------------------------------------------------------
    |
    | Disk foto gunung berlisensi (W8, DK-37). Bawaannya disk privat lokal; fotonya
    | disajikan lewat rute mountains.photo kepada pengguna masuk, bersama atribusinya.
    |
    */

    'mountain_photos_disk' => env('MOUNTAIN_PHOTOS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
