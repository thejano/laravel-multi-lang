---
title: Getting Started
---

# Getting Started

This guide walks through the minimal steps required to install, configure, and use Laravel Multi-Lang in a fresh application.

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- Database with JSON or text column support (MySQL, PostgreSQL, SQLite, etc.)

## 1. Install the package

```bash
composer require thejano/laravel-multi-lang
```

Optionally publish the configuration and migration if you need to customise them:

```bash
php artisan vendor:publish --tag=multi-lang-config
php artisan vendor:publish --tag=multi-lang-migrations
php artisan migrate
```

## 2. Prepare your model

Attach the `Translatable` trait and define the translatable columns via the `$translatableFields` property.

```php
use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Traits\Translatable;

class Post extends Model
{
    use Translatable;

    protected $fillable = ['title', 'content', 'description'];

    protected array $translatableFields = ['title', 'content', 'description'];
}
```

> **Tip:** If `$translatableFields` is left empty the package assumes every column is translatable. Explicitly defining fields prevents accidental writes to non-localised attributes.

## 3. Configure your locales

Laravel Multi-Lang respects Laravel's own localisation settings. Double-check `config/app.php` so the app locale, fallback locale, and supported locales reflect the languages you want to serve:

```php
'locale' => 'en',
'fallback_locale' => 'en',
'supported_locales' => ['en', 'ckb', 'ar'],
```

You can add as many locales as you need—`supported_locales` is optional, but defining it keeps helper outputs (such as `get_available_locales()`) predictable.

## 4. Seed translations

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

Translations are stored in the polymorphic `translations` table. Soft deletes are enabled by default so recovery is easy.

## 5. Read translations

```php
$post->translate('title', 'ckb');             // ناونیشان
$post->translateOrOriginal('title', 'fr');    // Falls back to original value
$post->translatePlural('visits', 5, ['count' => 5], 'en');
```

The package automatically handles caching, fallbacks, and pluralisation. You can also access translations via helpers:

```php
trans_model($post, 'title', 'ckb');
trans_model_or_original($post, 'title', 'ar');
trans_model_choice($post, 'visits', 12, ['count' => 12], 'en');
```

Want to sanity-check your setup quickly? Crack open Tinker and run the same calls:

```bash
php artisan tinker
>>> $post = App\Models\Post::first();
>>> $post->translate('title', 'ckb');
```

## 6. Explore further

Once you have the basics running, jump to:

- [Usage recipes](/guide/usage.html) – eager loading, scopes, pluralisation, and recovery.
- [CLI workflows](/guide/cli.html) – audit, export, and import commands for managing large datasets.
- [Advanced topics](/guide/advanced.html) – caching adapters, fallback strategies, and performance tips.

Happy localising! 🎉

