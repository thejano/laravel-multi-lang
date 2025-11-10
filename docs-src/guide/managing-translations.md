---
title: Managing Translations
---

# Managing Translations

Beyond querying, most applications need to seed, update, clean up, and display translations in a predictable way. This guide covers the write APIs, soft delete workflow, pluralisation, and best practices for everyday use.

---

## Writing translations

### `setTranslation()`

```php
$post->setTranslation('title', 'ناونیشان', 'ckb');
```

- Restores soft-deleted rows automatically.
- Invalidates per-model caches so subsequent reads pick up the new value.
- Resets the `translations` relationship if it was eager loaded (so you don’t see stale data).

### `setTranslations()`

```php
$post->setTranslations([
    'title' => 'ناونیشان',
    'content' => 'ناوەڕۆک',
], 'ckb');
```

- Updates multiple fields for a single locale.
- Useful for form submissions where you save one locale at a time.

### `setTranslationsBatch()`

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
```

- Accepts a map of locales to field/value pairs.
- Restores soft-deleted rows and clears caches per update.
- Pass `detachMissing: true` to remove translations not supplied for a given locale:

```php
$post->setTranslationsBatch([
    'ckb' => ['title' => 'ناونیشان'],
], detachMissing: true);
```

This is ideal for syncing translations from admin panels or import jobs.

---

## Reading translations

- `translate($field, $locale)` – returns the translation or `null`.
- `translateOrOriginal($field, $locale = null)` – returns the translation, fallback locale, or original column.
- `getTranslations($locale)` – array keyed by field (`['title' => '...', 'content' => '...']`).
- `getAllTranslations()` – all locales keyed by locale (`['ckb' => [...], 'ar' => [...]]`).
- Attribute access (`$post->title`) automatically follows the fallback chain (current locale → fallback locale → original).

Need to check if a translation exists? Use `hasTranslation($field, $locale)`.

---

## Soft delete lifecycle

- `deleteTranslation($field, $locale)` – soft deletes a single translation row.
- `deleteTranslations($locale)` – soft deletes every translation for the locale.
- `deleteTranslations()` – force deletes all translations for the model (used when the parent is deleted).

```php
$post->deleteTranslation('title', 'ckb');
$post->setTranslation('title', 'ناونیشان', 'ckb'); // Restores row silently
```

### Pruning soft-deleted rows

Schedule a job to hard delete “stale” entries if your table grows quickly:

```php
Translation::onlyTrashed()
    ->where('deleted_at', '<', now()->subDays(30))
    ->forceDelete();
```

---

## Pluralisation helpers

The package supports Laravel’s choice syntax (`{0}`, `{1}`, `[2,*]`, etc.):

```php
$post->setTranslation('visits', '{0}No visits|{1}One visit|[2,*]:count visits', 'en');

$post->translatePlural('visits', 0);                  // "No visits"
$post->translatePlural('visits', 5, ['count' => 5]);  // "5 visits"
trans_model_choice($post, 'visits', 12, ['count' => 12]);
```

When no plural text exists for the locale, helpers fall back to the original attribute.

---

## Attribute guards & validation

- The trait throws `InvalidArgumentException` if you try to read or write a field not listed in `translatableFields`. Use this to your advantage—validate user input against that array to prevent typos.
- When building forms, consider hydrating `getTranslations($locale)` into your view model so you can render all fields predictably.

---

## Admin UI & bulk editing tips

- Use `setTranslationsBatch()` behind bulk edit forms or APIs to minimise round-trips.
- Reset caches after background jobs by calling `Post::resetTranslationCacheStore()` if you use a shared cache store (array caches reset automatically per request).
- For table UIs, eager load the locales you intend to display via `withTranslations(['ckb', 'ar'])` to avoid per-row queries.

With day-to-day management nailed down, move to [Caching & Performance](/guide/caching-performance.html) to ensure your translations stay fast at scale.

