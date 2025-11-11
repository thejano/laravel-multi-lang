---
title: Caching & Performance
---

# Caching & Performance

Translation-heavy apps can grind to a halt if each lookup triggers a database query. This guide shows you how to keep translations fast—from eager loading to shared caches and performance tuning.

---

## Eager loading translations

Avoid N+1 queries by pre-fetching the locales you need:

```php
// Single locale
$posts = Post::withTranslations('ckb')->get();

// Multiple locales
$posts = Post::withTranslations(['ckb', 'ar'])->get();

// Every locale available
$posts = Post::withAllTranslations()->get();

// Lazy eager load on an existing model
$post = Post::first();
$post->loadTranslations(['ckb', 'ar']);
```

Each method hydrates the `translations` relationship and primes the in-memory cache so future calls to `translate()` or attribute access do not hit the database.

> **Heads up:** When you eager load translations and later access a primary locale (your `config('app.locale')`, fallback, or current `App::getLocale()`), the package now reuses that eager-loaded payload. If the locale is missing in the collection, the original model attribute is returned without issuing another query. This keeps base-language reads as cheap as possible while still fetching real translations on demand.

---

## Per-model caching

The trait maintains an in-memory cache for translations on each model instance:

- Accessing a translation populates the cache.
- Subsequent reads during the same request are free.
- Calling any mutation method (`setTranslation`, `setTranslations`, `setTranslationsBatch`, `deleteTranslation`, etc.) clears the cache automatically.

---

## Shared cache stores

To persist translations across requests, configure a `TranslationCacheStore`. The package ships with two implementations:

1. `ArrayTranslationCacheStore` – in-memory, primarily for testing.
2. `CacheTranslationCacheStore` – wraps any Laravel cache driver (Redis, Memcached, Dynamo, etc.).

### Configure via `config/multi-lang.php`

```php
'cache_store' => [
    'driver' => 'redis',                 // null uses the default cache store
    'prefix' => 'multi_lang:translations',
    'ttl'    => 3600,                    // seconds; null = forever
],
```

### Configure at runtime

```php
Post::setTranslationCacheStore(new CacheTranslationCacheStore(
    cache()->store('redis'),
    prefix: 'multi_lang:translations',
    ttl: 3600
));

// Reset to default (per-model cache only)
Post::resetTranslationCacheStore();
```

You can implement your own store by satisfying the `TranslationCacheStore` interface (see [Extensibility & Integrations](/guide/extensibility.html)).

---

## Priming and invalidation

- **Priming**: call `$post->getTranslations('ckb')` after eager loading to ensure the payload is cached (helpful before queueing a job).
- **Invalidation**: all write operations clear per-model caches and call `forget()` on the external store if configured. You rarely need to flush caches manually, but `Post::resetTranslationCacheStore()` is handy in tests.

---

## Performance checklist

1. **Use eager loading** whenever you render lists or tables of translated models.
2. **Index wisely**: keep the built-in composite indexes (`translatable_type/translatable_id/locale/field` and `locale/field`) to maintain query speed.
3. **Batch updates**: prefer `setTranslationsBatch()` to reduce database chatter during imports or admin workflows.
4. **Prune soft deletes**: schedule a job to remove old soft-deleted rows if your table grows into the millions.
5. **Audit regularly**: run `multi-lang:audit` in CI to catch missing translations before they reach production.
6. **Monitor the cache**: use cache tags or metrics to confirm translations are hitting the shared store as expected.

---

## Troubleshooting

| Symptom | Likely cause | Quick fix |
| ------- | ------------ | --------- |
| Too many queries | Missing eager loading | Call `withTranslations()` or `loadTranslations()` for required locales. |
| Stale translations | Cache not invalidated | Ensure writes go through package helpers; call `resetTranslationCacheStore()` after custom import scripts. |
| Cache never hit | Shared store misconfigured | Confirm `config('multi-lang.cache_store')` is set correctly and the driver has permission. |
| Slow audit/export | Chunk size too large | Pass `--chunk=` to Artisan commands to balance memory and speed. |

With performance tuned, continue to [Automation & CLI](/guide/automation.html) to automate audits, exports, and imports.

