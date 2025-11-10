---
title: Testing & QA
---

# Testing & QA

Translations are part of your domain, so they deserve first-class test coverage. This guide outlines how to seed translations in tests, leverage the package’s factories, and design QA workflows that keep localisation healthy.

---

## Factories & seeds

The package ships with `TheJano\MultiLang\Database\Factories\TranslationFactory`, making it easy to stub translations:

```php
use TheJano\MultiLang\Models\Translation;
use TheJano\MultiLang\Tests\Fixtures\TestPost;

Translation::factory()->create([
    'translatable_type' => TestPost::class,
    'translatable_id'   => $post->id,
    'locale'            => 'ckb',
    'field'             => 'title',
    'translation'       => 'ناونیشان',
]);
```

Alternatively, rely on the trait helpers inside your test setup:

```php
beforeEach(function () {
    $this->post = TestPost::factory()->create();
    $this->post->setTranslationsBatch([
        'ckb' => ['title' => 'ناونیشان'],
        'ar'  => ['title' => 'عنوان'],
    ]);
});
```

---

## Sample assertions

```php
it('returns translated title for current locale', function () {
    $post = TestPost::factory()->create();
    $post->setTranslation('title', 'ناونیشان', 'ckb');

    App::setLocale('ckb');

    expect($post->title)->toBe('ناونیشان');
    expect($post->translate('title', 'ar'))->toBeNull();
});

it('filters posts by translated title', function () {
    TestPost::factory()->create()->setTranslation('title', 'ناونیشان', 'ckb');
    TestPost::factory()->create()->setTranslation('title', 'عنوان', 'ar');

    $matches = TestPost::whereTranslate('title', 'ناونیشان', 'ckb')->get();

    expect($matches)->toHaveCount(1);
});
```

---

## Cache-aware testing

- During tests, the default per-model cache is enough. If you configure a shared cache store, call `Post::resetTranslationCacheStore()` in your `setUp()` method to keep the environment deterministic.
- After imports or bulk updates in tests, call `$post->refresh()` to repopulate relationships and caches.

---

## Testing CLI workflows

Use Laravel’s built-in command testing helpers:

```php
test('audit command reports missing translations', function () {
    TestPost::factory()->create(); // intentionally missing locale

    $this->artisan('multi-lang:audit', [
        'model' => TestPost::class,
        '--locales' => 'ckb,ar',
        '--detailed' => true,
    ])->expectsOutputToContain('Missing translations')
      ->assertExitCode(1);
});
```

For export/import commands, write fixtures to `storage/testing` and clean them up inside the test.

---

## QA workflows

- **CI checks**: run `php artisan multi-lang:audit` as part of your pipeline. Fail the build if the exit code is non-zero.
- **Smoke tests**: ensure critical views render translations for the locales you support (`trans_model()` assertions inside feature tests).
- **Percy / screenshot tests**: pair translations with visual diffs to catch layout regressions (especially for RTL languages).
- **Performance tests**: re-run the included Pest `PerformanceTest` suite after major refactors to ensure eager loading and caching still work as expected.

With testing covered, you’re ready to ship confident localisation changes alongside the rest of your application.

