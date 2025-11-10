<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use TheJano\MultiLang\Tests\Feature\TestPost;

beforeEach(function () {
    // Create multiple posts with translations
    for ($i = 1; $i <= 10; $i++) {
        $post = TestPost::create([
            'title' => "Post {$i}",
            'content' => "Content {$i}",
        ]);

        $post->setTranslation('title', "ناونیشان {$i}", 'ckb');
        $post->setTranslation('title', "عنوان {$i}", 'ar');
        $post->setTranslation('content', "ناوەڕۆک {$i}", 'ckb');
    }
});

test('detects N+1 query problem when accessing translations without eager loading', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    $posts = TestPost::all();

    // Access translations for each post (should cause N+1)
    foreach ($posts as $post) {
        $post->translate('title', 'ckb');
        $post->translate('content', 'ckb');
    }

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should have 1 query for posts + (10 posts * 2 fields) = 21+ queries (N+1 problem)
    expect($queryCount)->toBeGreaterThan(20);
});

test('prevents N+1 queries when using withTranslations scope', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    App::setLocale('ckb');
    $posts = TestPost::withTranslations('ckb')->get();

    // Access translations for each post (should use eager loaded data)
    foreach ($posts as $post) {
        $post->translate('title', 'ckb');
        $post->translate('content', 'ckb');
    }

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should have 1 query for posts + 1 query for translations = 2 queries
    expect($queryCount)->toBeLessThanOrEqual(5);
});

test('prevents N+1 queries when using withAllTranslations scope', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    $posts = TestPost::withAllTranslations()->get();

    // Access translations for multiple locales
    foreach ($posts as $post) {
        $post->translate('title', 'ckb');
        $post->translate('title', 'ar');
        $post->translate('content', 'ckb');
    }

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should have 1 query for posts + 1 query for all translations = 2 queries
    expect($queryCount)->toBeLessThanOrEqual(5);
});

test('prevents N+1 queries when accessing attributes with eager loading', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    App::setLocale('ckb');
    $posts = TestPost::withTranslations('ckb')->get();

    // Access translatable attributes (should use eager loaded data)
    foreach ($posts as $post) {
        $title = $post->title;
        $content = $post->content;
    }

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should have 1 query for posts + 1 query for translations = 2 queries
    expect($queryCount)->toBeLessThanOrEqual(5);
});

test('caches translations after first access to prevent multiple queries', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    $post = TestPost::withTranslations('ckb')->first();

    // First access should use eager loaded data
    $title1 = $post->translate('title', 'ckb');

    // Clear query log
    DB::flushQueryLog();

    // Second access should use cache (no new queries)
    $title2 = $post->translate('title', 'ckb');
    $content = $post->translate('content', 'ckb');
    $title3 = $post->translate('title', 'ckb');

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should have 0 queries (using cached data)
    expect($queryCount)->toBe(0);
    expect($title1)->toBe($title2);
    expect($title2)->toBe($title3);
});

test('getTranslations uses cached data after eager loading', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    $post = TestPost::withTranslations('ckb')->first();

    // First call
    $translations1 = $post->getTranslations('ckb');

    // Clear query log
    DB::flushQueryLog();

    // Second call should use cache
    $translations2 = $post->getTranslations('ckb');

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should have 0 queries (using cached data)
    expect($queryCount)->toBe(0);
    expect($translations1)->toBe($translations2);
});

test('hasTranslation uses cached data after eager loading', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    $post = TestPost::withTranslations('ckb')->first();

    // First call
    $hasTitle1 = $post->hasTranslation('title', 'ckb');

    // Clear query log
    DB::flushQueryLog();

    // Second call should use cache
    $hasTitle2 = $post->hasTranslation('title', 'ckb');
    $hasContent = $post->hasTranslation('content', 'ckb');
    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should have 0 queries (using cached data)
    expect($queryCount)->toBe(0);
    expect($hasTitle1)->toBeTrue();
    expect($hasTitle2)->toBeTrue();
    expect($hasContent)->toBeTrue();

    expect(fn () => $post->hasTranslation('missing', 'ckb'))
        ->toThrow(\InvalidArgumentException::class, "Field 'missing' is not defined in translatableFields.");
});

test('setTranslation invalidates cache', function () {
    // Create a fresh post for this test
    $post = TestPost::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشانی سەرەتایی', 'ckb');

    // Load and cache translations
    $post->loadTranslations('ckb');

    // Get initial translation (from cache)
    $originalTitle = $post->translate('title', 'ckb');
    expect($originalTitle)->toBe('ناونیشانی سەرەتایی');

    // Update translation (should invalidate cache)
    $post->setTranslation('title', 'ناونیشانی نوێ', 'ckb');

    // Should get new value (cache was invalidated, so it queries fresh)
    // Note: After cache invalidation, translate() will query the database
    $newTitle = $post->translate('title', 'ckb');

    expect($newTitle)->toBe('ناونیشانی نوێ');
    expect($newTitle)->not->toBe($originalTitle);
});

test('loadTranslations method loads and caches translations efficiently', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    $post = TestPost::first();

    // Manually load translations
    $post->loadTranslations('ckb');

    // Clear query log
    DB::flushQueryLog();

    // Access multiple translations (should use cache)
    $title = $post->translate('title', 'ckb');
    $content = $post->translate('content', 'ckb');
    $title2 = $post->translate('title', 'ckb');

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should have 0 queries after caching
    expect($queryCount)->toBe(0);
});

test('performance comparison: with vs without eager loading', function () {
    // Test without eager loading
    DB::enableQueryLog();
    DB::flushQueryLog();

    $postsWithout = TestPost::all();
    foreach ($postsWithout as $post) {
        $post->translate('title', 'ckb');
    }

    $queriesWithout = count(DB::getQueryLog());

    // Test with eager loading
    DB::enableQueryLog();
    DB::flushQueryLog();

    $postsWith = TestPost::withTranslations('ckb')->get();
    foreach ($postsWith as $post) {
        $post->translate('title', 'ckb');
    }

    $queriesWith = count(DB::getQueryLog());

    // With eager loading should use significantly fewer queries
    expect($queriesWith)->toBeLessThan($queriesWithout);
    expect($queriesWith)->toBeLessThanOrEqual(5);
});

test('multiple locale access with withAllTranslations is efficient', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    $posts = TestPost::withAllTranslations()->get();

    // Access translations for multiple locales
    foreach ($posts as $post) {
        $post->translate('title', 'ckb');
        $post->translate('title', 'ar');
        $post->translate('content', 'ckb');
        $post->translate('title', 'en'); // May not exist, but shouldn't cause extra queries
    }

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Should still be efficient (1 for posts, 1 for translations)
    expect($queryCount)->toBeLessThanOrEqual(5);
});
