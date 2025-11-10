---
title: Extensibility & Integrations
---

# Extensibility & Integrations

Laravel Multi-Lang is designed to bend without breaking. This guide shows you how to extend the package—custom cache stores, fallback chains, middleware integration, and observability hooks.

---

## Custom cache stores

Implement the `TranslationCacheStore` contract when you need more control than the built-in array/Redis stores:

```php
use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Contracts\TranslationCacheStore;

class DynamoTranslationCacheStore implements TranslationCacheStore
{
    public function get(Model $model): ?array
    {
        // Retrieve translation payload from DynamoDB (or other service)
    }

    public function put(Model $model, array $payload): void
    {
        // Persist payload
    }

    public function forget(Model $model): void
    {
        // Delete payload
    }
}
```

Register at runtime:

```php
Post::setTranslationCacheStore(new DynamoTranslationCacheStore());
```

Reset to the default behaviour with `Post::resetTranslationCacheStore()`.

---

## Fallback strategies

`translateOrOriginal()` uses Laravel’s fallback locale automatically, but you might need bespoke chains:

```php
$preferredLocales = ['fr-CA', 'fr', config('app.fallback_locale')];

$translation = collect($preferredLocales)
    ->map(fn ($locale) => $post->translate('title', $locale))
    ->first(fn ($value) => filled($value))
    ?? $post->getAttribute('title');
```

The same pattern works with `whereTranslateWithFallback()`—just pass the locales in the order you want them evaluated.

---

## Middleware integration

See the dedicated [Locale Middleware](/guide/middleware.html) guide for setup instructions, routing patterns, and examples.

---

## TranslationScopeGroup in custom builders

Need reusable translation filters across repositories or service layers? Wrap logic into dedicated scope groups:

```php
class PostFilters
{
    public static function headlineMatch(Builder $query, string $value, string $locale = null): Builder
    {
        return $query->whereTranslateGroup(function ($group) use ($value, $locale) {
            $group->where('title', $value, $locale)
                  ->orWhereWithFallback('title', $value, $locale);
        });
    }
}
```

Call `PostFilters::headlineMatch(Post::query(), 'ناونیشان', 'ckb')` wherever needed.

---

## Observability & events

Translations are standard Eloquent models, so you can tap into lifecycle events:

```php
use TheJano\MultiLang\Models\Translation;

Translation::saved(function ($translation) {
    Cache::tags(['translations'])->flush();
});

Translation::deleted(function ($translation) {
    activity()
        ->performedOn($translation->translatable)
        ->event('translation_deleted')
        ->withProperties([
            'locale' => $translation->locale,
            'field'  => $translation->field,
        ])->log('Translation removed.');
});
```

Consider broadcasting events or dispatching jobs to invalidate CDN caches, send Slack notifications, or trigger automatic re-translations.

---

## Integrating external translation services

1. **Export** untranslated strings with `multi-lang:export --missing`.
2. **Push** the JSON payload to your translation vendor (S3, Phrase, Lokalise, Smartling, etc.).
3. **Import** translated content with `multi-lang:import --strategy=merge` or `--strategy=replace`.
4. **Prime caches** (optional) by eager loading translations post-import.

Wrap export/import commands in queued jobs or scheduled tasks to automate round-trips.

---

## Building admin interfaces

- Use `setTranslationsBatch()` behind forms to write multiple locales in one request.
- Present translations using `getTranslations($locale)` to keep form inputs predictable.
- Provide bulk actions that call `deleteTranslations($locale)` before re-importing clean content.
- Combine translation scope groups with standard filters to power advanced search screens.

With extensibility covered, move on to [Testing & QA](/guide/testing.html) to keep localisation behaviour reliable in your CI pipeline.

