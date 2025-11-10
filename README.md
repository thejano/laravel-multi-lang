# Laravel Multi-Lang Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/thejano/laravel-multi-lang.svg?style=flat-square)](https://packagist.org/packages/thejano/laravel-multi-lang)
[![Tests](https://github.com/thejano/laravel-multi-lang/actions/workflows/tests.yml/badge.svg)](https://github.com/thejano/laravel-multi-lang/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/thejano/laravel-multi-lang.svg?style=flat-square)](https://packagist.org/packages/thejano/laravel-multi-lang)
[![License](https://img.shields.io/github/license/thejano/laravel-multi-lang.svg?style=flat-square)](LICENSE)

Polymorphic translations for Laravel models with caching, eager-loading helpers, Blade directives, and convenient facades. Built with first-class support for Kurdish (ckb) and Arabic (ar).

---

## Table of Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Quick Start](#quick-start)
6. [Core Usage](#core-usage)
7. [Facade & Helpers](#facade--helpers)
8. [Blade Directives](#blade-directives)
9. [Automatic Locale Detection](#automatic-locale-detection)
10. [Performance & Caching](#performance--caching)
11. [Testing](#testing)
12. [Contributing](#contributing)
13. [Security](#security)
14. [Credits](#credits)
15. [License](#license)

---

## Features

- Polymorphic translations for any Eloquent model
- Trait-based API with attribute accessors
- Respects `config('app.locale')`, fallback, and supported locales
- Facade, helper functions, and Blade directives
- Cached translations with eager-loading helpers to prevent N+1 queries
- Eager-load one or many locales in a single query
- Middleware for automatic locale detection
- Artisan-publishable migrations & configuration
- Factories for rapid testing and seeding

---

## Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x

---

## Installation

```bash
composer require thejano/laravel-multi-lang
```

Publish the configuration and migration if you wish to customise them:

```bash
php artisan vendor:publish --tag=multi-lang-config
php artisan vendor:publish --tag=multi-lang-migrations
php artisan migrate
```

---

## Configuration

The package ships with a minimal `config/multi-lang.php` file that defines the translations table name:

```php
return [
    'translations_table' => 'translations',
];
```

Application locale behaviour is derived entirely from Laravel’s own configuration:

- `config('app.locale')`
- `config('app.fallback_locale')`
- `config('app.supported_locales')` *(optional but recommended)*

Add Kurdish and Arabic to your `config/app.php` if you want them available globally:

```php
'locale' => 'en',
'fallback_locale' => 'en',
'supported_locales' => ['en', 'ckb', 'ar'],
```

---

## Quick Start

### 1. Prepare your model

```php
use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Traits\Translatable;

class Post extends Model
{
    use Translatable;

    protected $fillable = ['title', 'content', 'description'];

    // List the attributes that should be translated
    protected array $translatableFields = ['title', 'content', 'description'];
}
```

### 2. Seed some translations

```php
$post = Post::create([
    'title' => 'Original Title',
    'content' => 'Original Content',
]);

$post->setTranslation('title', 'ناونیشان', 'ckb');
$post->setTranslation('title', 'عنوان', 'ar');

$post->setTranslations([
    'title' => 'ناونیشان',
    'content' => 'ناوەڕۆک',
], 'ckb');
```

### 3. Retrieve translations

```php
$title = $post->translate('title', 'ckb');                 // ناونیشان
$fallback = $post->translateOrOriginal('title', 'ckb');    // ناونیشان or original value
$kurdish = $post->getTranslations('ckb');                  // ['title' => 'ناونیشان', ...]
$all = $post->getAllTranslations();                        // ['ckb' => [...], 'ar' => [...]]
```

### 4. Attribute access

```php
App::setLocale('ckb');

// Returns Kurdish translation if it exists, falls back otherwise
echo $post->title;
```

### 5. Eager-load specific locales

```php
$posts = Post::withTranslations(['ckb', 'ar'])->get();

foreach ($posts as $post) {
    $post->translate('title', 'ckb'); // uses eager-loaded cache
    $post->translate('title', 'ar');
}

$post = Post::first();
$post->loadTranslations(['ckb', 'ar']); // caches both locales with one call
```

The trait will always:

1. Attempt the current locale
2. Fall back to `config('app.fallback_locale')`
3. Return the original attribute

---

## Core Usage

### Checking translations

```php
if ($post->hasTranslation('title', 'ckb')) {
    // Translation exists
}
```

### Deleting translations

```php
$post->deleteTranslation('title', 'ckb');  // single field & locale
$post->deleteTranslations('ckb');          // entire locale
$post->deleteTranslations();               // all locales (automatic on model delete)
```

### Working with the relationship

```php
$translations = $post->translations;                             // Collection
$kurdishOnly = $post->translations()->where('locale', 'ckb')->get();
```

---

## Facade & Helpers

```php
use TheJano\MultiLang\Facades\MultiLang;

MultiLang::setLocale('ckb');
$available = MultiLang::getAvailableLocales();           // ['en', 'ckb', 'ar']
$translated = MultiLang::getLocalesWithTranslations();   // e.g. ['ckb', 'ar']
$count = MultiLang::getTranslationsCount('ckb');
$translation = MultiLang::getModelTranslation($post, 'title', 'ckb');
```

Helper functions provide the same power in global scope:

```php
$multiLang = multi_lang();
$title = trans_model($post, 'title', 'ckb');
$fallback = trans_model_or_original($post, 'title', 'ckb');
$locales = get_available_locales();          // ['en', 'ckb', 'ar']
$has = has_translations('ckb');              // true / false
```

> **Tip:** Define `supported_locales` in `config/app.php` to control the list returned by `getAvailableLocales()`. Use `MultiLang::getLocalesWithTranslations()` when you need to inspect which locales actually have persisted translations.

---

## Blade Directives

```blade
{{-- Render translation --}}
@transModel($post, 'title', 'ckb')

{{-- Render translation or original --}}
@transModelOrOriginal($post, 'title', 'ckb')

{{-- Render current locale --}}
Current locale: @currentLocale
```

---

## Automatic Locale Detection

The package ships with a `SetLocale` middleware capable of detecting the locale from:

- Route parameter (`/{locale}/...`)
- Query parameter (`?locale=ckb`)
- Session (`session('locale')`)
- `Accept-Language` header

Register the middleware manually:

```php
// Laravel 10 - app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
        \TheJano\MultiLang\Middleware\SetLocale::class,
    ],
];

// Laravel 11+ - bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \TheJano\MultiLang\Middleware\SetLocale::class,
    ]);
});
```

Then define locale-aware routes:

```php
Route::get('/{locale}/posts', function ($locale) {
    // Locale is automatically set by middleware
    return view('posts.index');
})->where('locale', '[a-z]{2}');
```

---

## Performance & Caching

Use the eager-loading scopes to prevent N+1 queries:

```php
$posts = Post::withTranslations('ckb')->get();   // Single locale
$posts = Post::withAllTranslations()->get();     // All locales

foreach ($posts as $post) {
    echo $post->translate('title', 'ckb');       // No extra queries
}

$post = Post::first();
$post->loadTranslations('ckb');                  // Manual eager load (also cached)
```

Behind the scenes the trait caches translations per model instance so repeated lookups are free from additional database calls. Updating a translation automatically invalidates the cache.

---

## Testing

Any field you translate must be listed in `translatableFields`. Attempting to translate or store a value for a field outside this list throws an `InvalidArgumentException`, helping catch mistakes early.

The package includes factories and a comprehensive Pest suite. Run the tests with:

```bash
composer test
```

If you need factories in your own tests you can reference `TheJano\MultiLang\Database\Factories\TranslationFactory`.

---

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests for any additions
4. Submit a pull request describing your changes

---

## Security

If you discover any security-related issues, please email [pshtiwan@janocode.com](mailto:pshtiwan@janocode.com) instead of using the issue tracker.

---

## Credits

- [Dr Pshtiwan](https://github.com/drpshtiwan)
- [All contributors](../../contributors)

---

## License

The MIT License (MIT). Please see the [LICENSE](LICENSE) file for more information.
