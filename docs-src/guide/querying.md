---
title: Querying Translations
---

# Querying Translations

Laravel Multi-Lang mirrors Laravel’s query builder style so you can filter translated content with fluent, chainable scopes. This guide explains each scope, the arguments it accepts, and how to combine them.

---

## Scope parameters

Every scope follows the same signature:

| Argument | Description |
|----------|-------------|
| `field` | Required translatable column (must exist in `$translatableFields`). |
| `value` | Value to compare against. Use arrays for `In/NotIn`. Omit for `Null/NotNull`. |
| `operator` | Optional comparison operator (`=`, `!=`, `<`, `>`, `like`, `not like`, etc.). Defaults to `=`. |
| `locale` | Optional locale. Defaults to `App::getLocale()`. Pass `'*'` to target all locales at once. |
| `fallbackLocale` | Optional for fallback scopes—omit to use `config('app.fallback_locale')`. |
| `includeOriginal` | Determines whether the base attribute is evaluated in fallback scopes (default `true`). |

If you omit the operator, the scope assumes an equality comparison. Passing a locale last mirrors Laravel’s native `where` behaviour.

---

## Equality & comparison scopes

```php
// Simple equality (uses current locale if omitted)
Post::whereTranslate('title', 'ناونیشان', 'ckb')->get();
Post::orWhereTranslate('title', 'ناونیشان')->get();

// Comparison operators
Post::whereTranslate('published_at', '>=', now()->subWeek(), 'en')->get();
Post::whereTranslateNot('title', 'Draft', 'en')->get();

// Pattern matching
Post::whereTranslateLike('content', '%Laravel%', 'en')->get();
Post::whereTranslateLike('content', 'مقدمة%')->get(); // default locale
```

---

## Array scopes

```php
Post::whereTranslateIn('title', ['Titre', 'Titre secondaire'], 'fr')->get();
Post::whereTranslateNotIn('title', ['Archived', 'Draft'], 'en')->get();
Post::orWhereTranslateNotIn('title', ['پێش نووسین'], 'ckb')->get();
```

These scopes accept any value supported by `whereIn`/`whereNotIn`, including subqueries.

---

## Null scopes

```php
Post::whereTranslateNull('summary', 'ar')->get();      // Missing or NULL translation
Post::whereTranslateNotNull('summary')->get();         // Must exist in current locale
Post::orWhereTranslateNull('summary', 'ckb')->get();
```

---

## Fallback scopes

`whereTranslateWithFallback()` compares against multiple locales and (optionally) the original attribute in a single clause.

```php
// Check Kurdish → Arabic → original column (in that order)
Post::whereTranslateWithFallback('title', 'عنوان', 'ckb', 'ar')->get();

// Skip the original attribute
Post::whereTranslateWithFallback('title', 'Título', 'es', fallbackLocale: 'pt', includeOriginal: false)->get();

// OR variant
Post::orWhereTranslateWithFallback('excerpt', 'خلاصة', 'ar')->get();
```

---

## Grouped predicates

For complex combinations, wrap translation clauses with `whereTranslateGroup()`:

```php
Post::whereTranslateGroup(function ($group) {
    $group->where('title', 'ناونیشان', 'ckb')
          ->whereLike('content', '%Laravel%', 'ckb');
})->orWhere(function ($query) {
    $query->where('status', 'draft');
})->get();
```

Inside the group you can call any of:

- `where`, `orWhere`
- `whereNot`, `orWhereNot`
- `whereLike`, `orWhereLike`
- `whereIn`, `orWhereIn`
- `whereNotIn`, `orWhereNotIn`
- `whereNull`, `orWhereNull`
- `whereNotNull`, `orWhereNotNull`
- `whereWithFallback`, `orWhereWithFallback`

The group keeps your translation logic encapsulated, making the surrounding query easier to read.

---

## Wildcard locales

Pass `'*'` as the locale to evaluate the clause across all available translations:

```php
// Finds posts that have "Laravel" in any locale
Post::whereTranslateLike('content', '%Laravel%', '*')->get();

// Exclude posts where any translation equals "Draft"
Post::whereTranslateNot('title', 'Draft', '*')->get();
```

---

## Combining with standard conditions

Scopes return an `Illuminate\Database\Eloquent\Builder` instance, so you can chain them with native query builder calls:

```php
Post::where('status', 'published')
    ->whereTranslate('title', '!=', 'Archived', 'en')
    ->whereBetween('published_at', [now()->subMonth(), now()])
    ->orderBy('published_at', 'desc')
    ->paginate(15);
```

---

## Tips for repositories & services

- **Validate fields up front**: keep a constant array of translatable columns and assert membership before building dynamic queries.
- **Expose read models**: when building filters from HTTP query strings, map “friendly” parameters to the scopes above.
- **Fallback aware search**: combine fallback scopes with `'*'` locales to offer “search anywhere” experiences without hitting the database more than once.

Once you’re comfortable querying, continue to [Managing Translations](/guide/managing-translations.html) to learn about batch updates, soft deletes, and pluralisation helpers.

