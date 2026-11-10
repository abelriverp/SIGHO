<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezonesssssssssssssssssssssssssssssssssssssssssssssssssssssssss
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    // Configurable por propiedad: Mazatlán = America/Mazatlan (UTC-7),
    // Cancún = America/Cancun (UTC-5). El default conserva el
    // comportamiento de la instalación original de Mazatlán.
    'timezone' => env('APP_TIMEZONE', 'America/Mazatlan'),

    /*
    |--------------------------------------------------------------------------
    | Propiedad de esta instalación (SIGHO)
    |--------------------------------------------------------------------------
    |
    | Única fuente de verdad para el código y los textos de la propiedad.
    | IMPORTANTE: el código nunca debe llamar env('HOTEL_CODE') directamente;
    | con `php artisan config:cache` env() regresa null fuera de /config y
    | todo caería al default. Usar siempre config('app.hotel_code').
    |
    | Los defaults conservan la instalación original de Mazatlán para que
    | un .env que solo tenga HOTEL_CODE siga funcionando igual.
    |
    */

    'hotel_code'      => strtoupper(env('HOTEL_CODE', 'HEMZT')),
    'hotel_nombre'    => env('HOTEL_NOMBRE', 'Hotel Emporio Mazatlán'),
    'hotel_ciudad'    => env('HOTEL_CIUDAD', 'Mazatlán'),
    'hotel_ubicacion' => env('HOTEL_UBICACION', 'Hotel Emporio, Mazatlán, Sin.'),

    /*
    | Portal de huesped por QR (/qr/{token}). Apagado por decision de Abel
    | (nov 2026): por ahora solo el personal levanta tickets. Para activarlo
    | poner PORTAL_HUESPED=true en el .env.
    */
    'portal_huesped' => (bool) env('PORTAL_HUESPED', false),

    /*
    | Respaldos (Fase 0, nov 2026) — ver App\Console\Commands\RespaldarSigho.
    | BACKUP_PATH: carpeta extra (ej. una carpeta compartida en otra PC) donde copiar el ZIP.
    | BACKUP_DIAS: cuantos respaldos conservar. MYSQLDUMP_PATH: ruta a mysqldump.
    */
    'backup_path'    => env('BACKUP_PATH'),
    'backup_dias'    => (int) env('BACKUP_DIAS', 14),
    'mysqldump_path' => env('MYSQLDUMP_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'es'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'es'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'es_MX'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
    

];
