<?php

/*
 * Nightly backups (php artisan backup:run): the database and the uploaded files in one zip. See docs/backups.md.
 */
return [

    // Where the zips go. Best on another drive or a network share, so one disk failing can't take both.
    'path' => env('BACKUP_PATH') ?: storage_path('app/backups'),

    // Backups older than this many days are deleted (the newest one is always kept)
    'keep_days' => (int) (env('BACKUP_KEEP_DAYS') ?: 30),

    // A second copy of every backup OFF this server: another PC's shared folder or a NAS, e.g. \\192.168.2.25\backups\pbo-mbm
    // (the account running the scheduler needs write access). Empty: no second copy.
    'copy_path' => env('BACKUP_COPY_PATH') ?: null,

    // Copies older than this many days are deleted there (the newest is always kept)
    'copy_keep_days' => (int) (env('BACKUP_COPY_KEEP_DAYS') ?: (env('BACKUP_KEEP_DAYS') ?: 30)),

    // mysqldump: on PATH, or its full path (XAMPP: D:\xampp\mysql\bin\mysqldump.exe)
    'mysqldump' => env('BACKUP_MYSQLDUMP') ?: 'mysqldump',

    // Folders zipped with the database (the Records uploads are in storage/app/private/records)
    'folders' => [
        'storage/app/public' => storage_path('app/public'),
        'storage/app/private' => storage_path('app/private'),
    ],

    // Seconds mysqldump may take
    'timeout' => (int) env('BACKUP_TIMEOUT', 900),
];
