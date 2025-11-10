---
title: Overview
---

# Overview

Laravel Multi-Lang centralises every moving part you need to internationalise Eloquent models—schema, traits, helpers, scopes, caching, CLI tooling, and observability—into a single, cohesive package. This page gives you the “map of the territory” before we dive into the detailed guides.

## Feature matrix

| Area | What you get | Where to learn more |
| ---- | ------------ | ------------------- |
| On-model translations | `Translatable` trait, attribute fallbacks, translation helpers, facade | [Core Concepts](/guide/core-concepts.html) |
| Querying content | Full suite of `whereTranslate*` scopes, grouped predicates, wildcard locales | [Querying Translations](/guide/querying.html) |
| Managing content | `setTranslation`, batch updates, soft deletes with auto-restore, pluralisation helpers | [Managing Translations](/guide/managing-translations.html) |
| Performance | Eager-loading scopes, per-request caching, shared cache stores, performance tips | [Caching & Performance](/guide/caching-performance.html) |
| Automation | Audit/export/import Artisan commands, JSON schema, scheduled snapshots | [Automation & CLI](/guide/automation.html) |
| Extensibility | Custom cache stores, fallback chains, middleware, observers, broadcasting | [Extensibility & Integrations](/guide/extensibility.html) |
| Quality & testing | Factories, Pest test coverage, usage patterns in tests, CI tips | [Testing & QA](/guide/testing.html) |

## Quick wins

1. **Add the trait**: mark your models as `Translatable` and list the attributes that should become translatable.
2. **Seed translations**: use `setTranslation()` or `setTranslationsBatch()` to write content per locale.
3. **Query smart**: filter translated content with `whereTranslate()`, `whereTranslateLike()`, and friends.
4. **Guard performance**: eager load via `withTranslations()` and optionally plug translations into Redis via `TranslationCacheStore`.
5. **Automate workflows**: run `multi-lang:audit`, `multi-lang:export`, and `multi-lang:import` in CI, scheduled jobs, or content pipelines.
6. **Scale safely**: rely on soft deletes, cached lookups, and observability hooks to manage large datasets.

## Standard translation flow

```mermaid
flowchart LR
    A[Model uses Translatable trait] --> B[Seed translations: setTranslation / batch]
    B --> C[Eager load locales with withTranslations]
    C --> D[Access attributes or translate()]
    D --> E[Query via whereTranslate scopes]
    C --> F[Cache store (array / Redis / custom)]
    B --> G[Audit & export via CLI]
    G --> H[Import translated JSON]
```

## Documentation map

- **New to the package?** Start with [Getting Started](/guide/getting-started.html) for installation and first translations.
- **See how the trait works internally?** Jump to [Core Concepts](/guide/core-concepts.html).
- **Filtering records in controllers or repositories?** [Querying Translations](/guide/querying.html) covers every scope.
- **Managing content at scale?** [Managing Translations](/guide/managing-translations.html) explains batch updates, soft deletes, pluralisation, and helpers.
- **Worried about performance?** [Caching & Performance](/guide/caching-performance.html) outlines eager loading, shared caches, and tuning advice.
- **Need automation pipelines?** [Automation & CLI](/guide/automation.html) details the Artisan commands with examples (CI, scheduled exports).
- **Extending behaviour?** [Extensibility & Integrations](/guide/extensibility.html) documents custom cache stores, fallback chains, middleware, and observers.
- **Write robust tests?** [Testing & QA](/guide/testing.html) shows how to integrate translations into your test suite.

With the lay of the land set, continue with [Getting Started](/guide/getting-started.html) or dive straight into the topic most relevant to your project. The remaining guides are intentionally modular—feel free to cherry-pick what you need.

