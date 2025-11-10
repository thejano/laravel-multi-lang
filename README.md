# Laravel Multi-Lang
[![Latest Version on Packagist](https://img.shields.io/packagist/v/thejano/laravel-multi-lang.svg?style=flat-square)](https://packagist.org/packages/thejano/laravel-multi-lang)
[![Tests](https://github.com/thejano/laravel-multi-lang/actions/workflows/tests.yml/badge.svg)](https://github.com/thejano/laravel-multi-lang/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/thejano/laravel-multi-lang.svg?style=flat-square)](https://packagist.org/packages/thejano/laravel-multi-lang)
[![License](https://img.shields.io/github/license/thejano/laravel-multi-lang.svg?style=flat-square)](LICENSE)

Polymorphic translations for Laravel models with a batteries-included developer experience—helper functions, eager-loading scopes, caching, CLI tooling, pluralisation helpers, import/export pipelines, and more.


## Documentation
Visit docs for the complete guide—including Overview, Core Concepts, Querying Translations, Automation & CLI, Extensibility, Middleware, and Testing.  

https://multi-lang.thejano.com

---

## Why you’ll love it

- **Laravel-native ergonomics** – fluent query scopes, helpers, Blade directives, and facades.
- **Performance ready** – eager loading, translation caching (including Redis-backed stores), and grouped predicates.
- **Robust workflows** – soft deletes with automatic restoration, batch setters, JSON import/export, audit command, pluralisation helpers.

---

## Install

```bash
composer require thejano/laravel-multi-lang

php artisan vendor:publish --tag=multi-lang-config
php artisan vendor:publish --tag=multi-lang-migrations
php artisan migrate
```

---

## Five-minute setup

```php
use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Traits\Translatable;

class Post extends Model
{
    use Translatable;

    protected $fillable = ['title', 'content'];
    protected array $translatableFields = ['title', 'content'];
}
```

```php
$post = Post::create(['title' => 'Original Title']);

$post->setTranslation('title', 'ناونیشان', 'ckb');
$post->translate('title', 'ckb');                      // ناونیشان
$post->translateOrOriginal('title', 'fr');             // Falls back to original value
trans_model_choice($post, 'visits', 5, ['count' => 5]);
```

Query like native Laravel:

```php
Post::whereTranslate('title', 'ناونیشان', 'ckb')->get();
Post::whereTranslateWithFallback('title', 'عنوان', 'ckb', 'ar')->get();
Post::withTranslations(['ckb', 'ar'])->get();
```

---

## CLI & automation

- `php artisan multi-lang:audit` – surface missing translations.
- `php artisan multi-lang:export` – JSON export (use `--missing`, `--ids`, `--locales`).
- `php artisan multi-lang:import` – merge/replace import with `--only-missing` support.

Pair exports/imports with your favourite translation service or CI workflow.

---

## Caching tips

Translations cache per model and can share stores:

```php
'cache_store' => [
    'driver' => 'redis',    // null uses default cache store
    'prefix' => 'multi_lang:translations',
    'ttl'    => 3600,
],
```

Reset across tests/jobs with `Post::resetTranslationCacheStore();`.

---

## Testing & contributing

- Run the Pest suite: `composer test`
- Issues or pull requests welcome—add tests and a short summary when contributing.
- Security reports: [pshtiwan@janocode.com](mailto:pshtiwan@janocode.com)

---

## License

Laravel Multi-Lang is open-source software licensed under the [MIT license](LICENSE).

