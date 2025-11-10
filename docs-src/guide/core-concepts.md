---
title: Core Concepts
---

# Core Concepts

This guide introduces the foundational pieces that every Laravel Multi-Lang project relies on: configuration, the `Translatable` trait, attribute behaviour, and helper utilities.

---

## Configuration checkpoints

1. **Composer install**
   ```bash
   composer require thejano/laravel-multi-lang
   ```
2. **Publish assets (optional)**
   ```bash
   php artisan vendor:publish --tag=multi-lang-config
   php artisan vendor:publish --tag=multi-lang-migrations
   php artisan migrate
   ```
3. **App locales**
   ```php
   'locale' => 'en',
   'fallback_locale' => 'en',
   'supported_locales' => ['en', 'ckb', 'ar'],
   ```
   `supported_locales` is optional but recommended—it keeps helper outputs consistent and avoids typos.

---

## The `Translatable` trait

Attach the trait to any Eloquent model you want to localise:

```php
use TheJano\MultiLang\Traits\Translatable;

class Post extends Model
{
    use Translatable;

    protected $fillable = ['title', 'content', 'description'];
    protected array $translatableFields = ['title', 'content', 'description'];
}
```

- `translatableFields` is optional. If omitted, the package treats every column as translatable—but declaring the list protects you against accidental writes.
- Translations live in a polymorphic `translations` table; the trait handles relationships automatically.

---

## Reading and writing translations

```php
$post = Post::create(['title' => 'Original Title']);

$post->setTranslation('title', 'ناونیشان', 'ckb');   // Single locale
$post->setTranslations([
    'title' => 'ناونیشان',
    'content' => 'ناوەڕۆک',
], 'ckb');                                           // Multiple fields, one locale

$post->translate('title', 'ckb');                    // 'ناونیشان'
$post->translateOrOriginal('title', 'fr');           // Fallback to original column
$post->getTranslations('ckb');                      // ['title' => '...', 'content' => '...']
$post->getAllTranslations();                        // ['ckb' => [...], 'ar' => [...]]
```

### Attribute fallback order

When you access `$post->title`, Multi-Lang evaluates:

1. `App::getLocale()`
2. `config('app.fallback_locale')`
3. The original column value (`posts.title`)

Need the raw attribute without translation? Use `$post->getAttributes()['title']`.

---

## Helper functions & facade

Global helpers are always a function call away:

```php
trans_model($post, 'title', 'ckb');
trans_model_or_original($post, 'title');    // current locale with fallback
trans_model_choice($post, 'visits', 3, ['count' => 3]);
get_available_locales();                    // e.g. ['en', 'ckb', 'ar']
has_translations('ckb');                    // boolean
```

Prefer a facade? `TheJano\MultiLang\Facades\MultiLang` offers the same surface area plus convenience methods to read/set the active locale.

---

## Translation relationships

```php
$post->translations;                              // Collection of Translation models
$post->translations()->where('locale', 'ckb')->get();
```

The relationship is polymorphic (`translatable_type`, `translatable_id`) so you can reuse the same translations table across multiple models.

---

## Soft deletes by default

Every translation is soft deletable. Calling `setTranslation()` on a previously soft-deleted row automatically restores it. You can safely remove locales, re-import them, or prune old data without losing historical context.

```php
$post->deleteTranslation('title', 'ckb');  // marks row deleted
$post->setTranslation('title', 'ناونیشان', 'ckb'); // restores + updates
```

---

## Pluralisation helpers

Store Laravel-style choice strings directly in your translations:

```php
$post->setTranslation('visits', '{0}No visits|{1}One visit|[2,*]:count visits', 'en');

$post->translatePlural('visits', 0);                 // "No visits"
$post->translatePlural('visits', 5, ['count' => 5]); // "5 visits"
trans_model_choice($post, 'visits', 12, ['count' => 12]);
```

Helpers fall back to the original attribute if no pluralised translation exists.

---

## Quick debugging tips

- **Tinker-friendly**: `php artisan tinker` + `$post->translate('field', 'locale')` is the fastest way to verify your setup.
- **Guard your fields**: accessing or writing to a field not present in `translatableFields` throws an `InvalidArgumentException`.
- **Keep caches fresh**: after running imports or bulk updates, either call `Post::resetTranslationCacheStore()` or simply read translations—they automatically invalidate per-model caches.

With these fundamentals out of the way, continue to [Querying Translations](/guide/querying.html) to see how you can slice and dice translated content with fluent scopes.

