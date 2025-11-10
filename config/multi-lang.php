<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Translation Table Name
    |--------------------------------------------------------------------------
    |
    | This is the name of the table that will store translations.
    |
    */

    'translations_table' => 'translations',

    /*
    |--------------------------------------------------------------------------
    | Note About Locales
    |--------------------------------------------------------------------------
    |
    | The package relies on the application's locale configuration:
    | - config('app.locale')
    | - config('app.fallback_locale')
    | - config('app.supported_locales') (optional array)
    |
    | Define your supported locales in config/app.php to control which locales
    | are available across the application and this package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Translation Cache Store
    |--------------------------------------------------------------------------
    |
    | Optionally provide a service container binding or class name that
    | implements TheJano\MultiLang\Contracts\TranslationCacheStore to share
    | cached translations beyond the lifetime of a single model instance.
    | Set to null to disable external caching. The bundled
    | ArrayTranslationCacheStore offers a simple in-memory option.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Translation Cache Store
    |--------------------------------------------------------------------------
    |
    | Set to null to disable shared caching (per-model in-memory cache still
    | works). Provide a class, binding, or array with driver/prefix/ttl to enable
    | Laravel's cache repository:
    |
    | 'cache_store' => [
    |     'driver' => 'redis', // null uses config('cache.default')
    |     'prefix' => 'multi_lang:translations',
    |     'ttl' => 3600, // seconds or null for forever
    | ],
    |
    */

    'cache_store' => null,
];
