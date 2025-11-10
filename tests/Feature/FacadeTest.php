<?php

use Illuminate\Support\Facades\App;
use TheJano\MultiLang\Facades\MultiLang;
use TheJano\MultiLang\Tests\Feature\TestPost;

test('facade can get current locale', function () {
    App::setLocale('ckb');

    expect(MultiLang::getLocale())->toBe('ckb');
});

test('facade can set locale', function () {
    MultiLang::setLocale('ar');

    expect(App::getLocale())->toBe('ar');
});

test('facade can get default locale', function () {
    $defaultLocale = MultiLang::getDefaultLocale();

    expect($defaultLocale)->toBeString();
    expect($defaultLocale)->toBe('en');
});

test('facade can get fallback locale', function () {
    $fallbackLocale = MultiLang::getFallbackLocale();

    expect($fallbackLocale)->toBeString();
    expect($fallbackLocale)->toBe('en');
});

test('facade can get available locales', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');
    $post->setTranslation('title', 'عنوان', 'ar');

    $locales = MultiLang::getAvailableLocales();

    expect($locales)->toBeArray();
    expect($locales)->toEqualCanonicalizing(['en', 'ckb', 'ar']);
});

test('facade can get locales with translations', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');
    $post->setTranslation('title', 'عنوان', 'ar');

    $locales = MultiLang::getLocalesWithTranslations();

    expect($locales)->toBeArray();
    expect($locales)->toEqualCanonicalizing(['ar', 'ckb']);
});

test('facade can get translations count for a locale', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');
    $post->setTranslation('content', 'ناوەڕۆک', 'ckb');

    $count = MultiLang::getTranslationsCount('ckb');

    expect($count)->toBeGreaterThanOrEqual(2);
});

test('facade can check if locale has translations', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    expect(MultiLang::hasTranslations('ckb'))->toBeTrue();
    expect(MultiLang::hasTranslations('de'))->toBeFalse();
});

test('facade can get model translations', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');
    $post->setTranslation('content', 'ناوەڕۆک', 'ckb');

    $translations = MultiLang::getModelTranslations($post, 'ckb');

    expect($translations)->toBeArray();
    expect($translations)->toHaveKey('title');
    expect($translations)->toHaveKey('content');
    expect($translations['title'])->toBe('ناونیشان');
    expect($translations['content'])->toBe('ناوەڕۆک');
});

test('facade can get model translation', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    expect(MultiLang::getModelTranslation($post, 'title', 'ckb'))->toBe('ناونیشان');
    expect(MultiLang::getModelTranslation($post, 'title', 'ar'))->toBeNull();
});

test('facade can get model translation or original', function () {
    $post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    expect(MultiLang::getModelTranslationOrOriginal($post, 'title', 'ckb'))->toBe('ناونیشان');
    expect(MultiLang::getModelTranslationOrOriginal($post, 'content', 'ckb'))->toBe('Original Content');
});

test('facade can check if model has translation', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    expect(MultiLang::modelHasTranslation($post, 'title', 'ckb'))->toBeTrue();
    expect(MultiLang::modelHasTranslation($post, 'title', 'ar'))->toBeFalse();
    expect(MultiLang::modelHasTranslation($post, 'content', 'ckb'))->toBeFalse();
});
