# Laravel Multi-Lang Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/thejano/laravel-multi-lang.svg?style=flat-square)](https://packagist.org/packages/thejano/laravel-multi-lang)
[![Tests](https://github.com/thejano/laravel-multi-lang/actions/workflows/tests.yml/badge.svg)](https://github.com/thejano/laravel-multi-lang/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/thejano/laravel-multi-lang.svg?style=flat-square)](https://packagist.org/packages/thejano/laravel-multi-lang)
[![License](https://img.shields.io/github/license/thejano/laravel-multi-lang.svg?style=flat-square)](LICENSE)

Polymorphic translations for Laravel models with caching, eager-loading helpers, Blade directives, and convenient facades. 

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
- Soft delete ready translation records with automatic restoration helpers
- Batch translation helpers for seeding or admin workflows
- Locale-aware query scopes with optional fallback support
- Grouped translation predicates for complex query logic
- Extensible caching hooks for shared stores
- Translation coverage audit Artisan command
- Import/export CLI workflows for collaborating with translators
- Pluralization helpers with Laravel-style choice syntax
- Optional database indexes for high-volume datasets

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

### Batch updating translations

```php
$post->setTranslationsBatch([
    'ckb' => [
        'title' => 'ناونیشانی نوێ',
        'content' => 'ناوەڕۆکی نوێ',
    ],
    'ar' => [
        'title' => 'عنوان جديد',
    ],
]);

// Remove any translations not included in the payload
$post->setTranslationsBatch([
    'ckb' => [
        'title' => 'ناونیشان',
    ],
], detachMissing: true);
```

### Soft deletes & restoring translations

- The `translations` table uses soft deletes by default.
- `deleteTranslation()` and `deleteTranslations()` mark records as deleted so you can recover them later.
- Writing the same locale/field again (via `setTranslation()` or batch helpers) automatically restores the soft-deleted row and updates the value. You don’t need to call `restore()` manually unless you want to revive a record without changing its content.
- `deleteTranslations()` without a locale still wipes every translation for the model (hard delete behaviour by calling `forceDelete()` internally), so use it carefully when you want permanent removal.

```php
// Soft delete a single translation
$post->deleteTranslation('title', 'ckb');  // row remains for possible restore

// Restore by writing again (row is un-soft-deleted)
$post->setTranslation('title', 'ناونیشان', 'ckb');

// Remove an entire locale (also soft deletes)
$post->deleteTranslations('ckb');

// Force delete a single translation immediately
$post->translations()
    ->where('locale', 'ckb')
    ->where('field', 'title')
    ->forceDelete();

// Force delete everything (no restore) – e.g. when the post itself is deleted
$post->deleteTranslations();  // calls forceDelete() under the hood
```

### Pluralization helpers

Use Laravel’s familiar choice syntax (`{0}`, `{1}`, `[2,*]`, etc.) inside your stored translations:

```php
$post->setTranslation('comments', '{0}No comments|{1}One comment|[2,*]:count comments', 'en');

echo $post->translatePlural('comments', 0, [], 'en');            // "No comments"
echo $post->translatePlural('comments', 1, [], 'en');            // "One comment"
echo $post->translatePlural('comments', 5, ['count' => 5], 'en'); // "5 comments"

// Helper function
echo trans_model_choice($post, 'comments', 12, ['count' => 12], 'en');
```

### Filtering by translations

```php
// Match exact translation in the current locale (or pass the desired locale)
$kurdishPosts = Post::whereTranslate('title', 'ناونیشان', 'ckb')->get();
$defaultLocalePosts = Post::whereTranslate('title', 'ناونیشان')->get();     // uses App::getLocale()

// Use the same operator syntax as Laravel's where clauses
$arabicSummaries = Post::whereTranslate('summary', 'like', 'مقدمة%', 'ar')->get();
$nonHeadlinePosts = Post::whereTranslate('title', '!=', 'ناونیشان', 'ckb')->get();
$excludeTitles = Post::whereTranslateNotIn('title', ['ناونیشان', 'ناونیشانێکی تر'], 'ckb')->get();
$defaultLocaleExcludes = Post::whereTranslateNotIn('title', ['ناونیشان'])->get(); // uses current locale

// Match any of the provided translations
$arabicTitles = Post::whereTranslateIn('title', ['عنوان', 'عنوان ثانوي'], 'ar')->get();

// Exclude specific translations (also accepts operators like !=, not like, etc.)
$noKurdishHeadline = Post::whereTranslateNot('title', 'ناونیشان', 'ckb')->get();
$missingSummaries = Post::whereTranslateNull('summary', 'ar')->get();         // missing or NULL
$defaultLocaleMissing = Post::whereTranslateNull('summary')->get();           // uses App::getLocale()
$withSummaries = Post::whereTranslateNotNull('summary', 'ckb')->get();        // must exist
$fallbackMatches = Post::whereTranslateWithFallback('title', 'عنوان', 'ckb', 'ar')->get();
$grouped = Post::whereTranslateGroup(function (\TheJano\MultiLang\Support\TranslationScopeGroup $group) {
    $group->where('title', 'ناونیشان', 'ckb')
        ->orWhereWithFallback('title', 'عنوان', 'ckb', 'ar');
})->get();

// Combine with other conditions using OR
$posts = Post::where('status', 'published')
    ->orWhereTranslate('summary', 'مقدمة موجزة', 'ar')
    ->orWhereTranslateNot('summary', 'کورتە', 'ckb')
    ->orWhereTranslateNotIn('summary', ['مقدمة موجزة'], 'ar')
    ->orWhereTranslateNull('summary', 'ckb')
    ->get();
```

**Available scopes**

- `whereTranslate(...)`, `orWhereTranslate(...)`
- `whereTranslateNot(...)`, `orWhereTranslateNot(...)`
- `whereTranslateLike(...)`, `orWhereTranslateLike(...)`
- `whereTranslateIn(...)`, `orWhereTranslateIn(...)`
- `whereTranslateNotIn(...)`, `orWhereTranslateNotIn(...)`
- `whereTranslateNull(...)`, `orWhereTranslateNull(...)`
- `whereTranslateNotNull(...)`, `orWhereTranslateNotNull(...)`
- `whereTranslateWithFallback(...)`, `orWhereTranslateWithFallback(...)`
- `whereTranslateGroup(fn (TranslationScopeGroup $group) => ...)`, `orWhereTranslateGroup(...)`

Each scope follows Laravel’s `where` signature:

- `field` (string) – the translated attribute name defined in `translatableFields`
- `value` (mixed) – the comparison value (for `In` scopes this is an array)
- `operator` (string, optional) – defaults to `=`; accepts `!=`, `<`, `like`, etc. where relevant
- `locale` (string, optional) – defaults to `App::getLocale()`
- `fallbackLocale` (string, optional, fallback scopes only) – defaults to Laravel’s configured fallback locale
- `includeOriginal` (bool, optional, fallback scopes only) – when `true`, includes the base attribute value in the comparison

When using scopes without an operator (e.g. `->whereTranslate('title', 'ناونیشان')`), the package assumes equality. Provide a locale as the last argument to override the default locale. Pass `'*'` for locale to run the comparison across all locales instead of restricting to a single language.

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
$plural = MultiLang::getModelTranslationPlural($post, 'comments', 3, ['count' => 3], 'en');
```

Helper functions provide the same power in global scope:

```php
$multiLang = multi_lang();
$title = trans_model($post, 'title', 'ckb');
$fallback = trans_model_or_original($post, 'title', 'ckb');
$plural = trans_model_choice($post, 'comments', 2, ['count' => 2], 'en');
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

Enable the shared cache store hook to persist translations across requests:

```php
use TheJano\MultiLang\Support\ArrayTranslationCacheStore;

Post::setTranslationCacheStore(new ArrayTranslationCacheStore());
// or set the class name in config/multi-lang.php under 'cache_store'
```

**Scaling tips**

- Keep indexes from the default migration (`translatable_type/translatable_id/locale/field` and `locale/field`) in place; they keep lookups fast even when the table grows large.
- Lean on eager-loading helpers (`withTranslations`, `withAllTranslations`) and the new query scopes to avoid per-row hits.
- For cross-request performance, back `TranslationCacheStore` with Redis or your cache of choice so hot content rarely reaches the database.
  - Set `config('multi-lang.cache_store')` to `['driver' => 'redis', 'prefix' => 'multi_lang:translations', 'ttl' => 3600]` (or leave `driver` null to use the default cache store) and the trait will automatically use Laravel’s cache repository.
- Use `setTranslationsBatch()` for imports/sync jobs; it cuts round-trips and lets you optionally trim missing locales in one go.
- Build periodic pruning jobs if you soft delete aggressively—removing old `deleted_at` rows keeps indexes tight.
- The bundled `PerformanceTest` suite is a handy smoke test; extend it with your dataset to catch regressions before production.

---

## Artisan Commands

### Translation audit

```bash
php artisan multi-lang:audit "App\Models\Post" --locales=ckb,ar --detailed
```

- `--locales=` overrides the locales to inspect (defaults to `config('app.supported_locales')` or the app locale + fallback)
- `--fields=` limits the audit to specific translatable fields
- `--chunk=` adjusts the chunk size when iterating large datasets
- `--detailed` prints each model identifier missing a translation (use sparingly on large tables)

### Export translations

```bash
php artisan multi-lang:export "App\Models\Post" --locales=ckb,ar --path=storage/app/posts.json
```

- `--ids=` limits the export to specific model IDs
- `--chunk=` processes records in batches (default `100`)
- `--missing` exports only the fields/locales that are currently missing translations (uses the source attribute value as a placeholder)

### Import translations

```bash
php artisan multi-lang:import "App\Models\Post" --path=storage/app/posts.json --strategy=merge
```

- `--strategy=merge` updates the provided translations without deleting others
- `--strategy=replace` syncs locales/fields and removes any translations not in the payload
- `--only-missing` applies translations only where a field is currently empty

---

## Testing

Any field you translate must be listed in `translatableFields`. Attempting to translate or store a value for a field outside this list throws an `InvalidArgumentException`, helping catch mistakes early.

The package includes factories and a comprehensive Pest suite. Run the tests with:

```bash
composer test
```

If you need factories in your own tests you can reference `TheJano\MultiLang\Database\Factories\TranslationFactory`.

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
