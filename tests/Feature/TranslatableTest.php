<?php

use Illuminate\Support\Facades\App;
use TheJano\MultiLang\Models\Translation;
use TheJano\MultiLang\Tests\Feature\TestPost;

beforeEach(function () {
    $this->post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);
});

test('can set translation for a field', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');

    expect($this->post->translate('title', 'ckb'))->toBe('ناونیشان');
});

test('can set multiple translations at once', function () {
    $this->post->setTranslations([
        'title' => 'ناونیشان',
        'content' => 'ناوەڕۆک',
    ], 'ckb');

    expect($this->post->translate('title', 'ckb'))->toBe('ناونیشان');
    expect($this->post->translate('content', 'ckb'))->toBe('ناوەڕۆک');
});

test('can set translations for multiple locales', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->setTranslation('title', 'عنوان', 'ar');

    expect($this->post->translate('title', 'ckb'))->toBe('ناونیشان');
    expect($this->post->translate('title', 'ar'))->toBe('عنوان');
});

test('can get translation for a field', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');

    expect($this->post->translate('title', 'ckb'))->toBe('ناونیشان');
});

test('returns null when translation does not exist', function () {
    expect($this->post->translate('title', 'ckb'))->toBeNull();
});

test('can get all translations for a locale', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->setTranslation('content', 'ناوەڕۆک', 'ckb');

    $translations = $this->post->getTranslations('ckb');

    expect($translations)->toBeArray();
    expect($translations)->toHaveKeys(['title', 'content']);
    expect($translations['title'])->toBe('ناونیشان');
    expect($translations['content'])->toBe('ناوەڕۆک');
});

test('can get all translations for all locales', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->setTranslation('title', 'عنوان', 'ar');
    $this->post->setTranslation('content', 'ناوەڕۆک', 'ckb');

    $allTranslations = $this->post->getAllTranslations();

    expect($allTranslations)->toBeArray();
    expect($allTranslations)->toHaveKey('ckb');
    expect($allTranslations)->toHaveKey('ar');
    expect($allTranslations['ckb']['title'])->toBe('ناونیشان');
    expect($allTranslations['ar']['title'])->toBe('عنوان');
});

test('translateOrOriginal returns translation when exists', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');

    expect($this->post->translateOrOriginal('title', 'ckb'))->toBe('ناونیشان');
});

test('translateOrOriginal returns original when translation does not exist', function () {
    expect($this->post->translateOrOriginal('title', 'ckb'))->toBe('Original Title');
});

test('can check if translation exists', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');

    expect($this->post->hasTranslation('title', 'ckb'))->toBeTrue();
    expect($this->post->hasTranslation('title', 'ar'))->toBeFalse();
});

test('can delete a specific translation', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->deleteTranslation('title', 'ckb');

    expect($this->post->translate('title', 'ckb'))->toBeNull();
});

test('can delete all translations for a locale', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->setTranslation('content', 'ناوەڕۆک', 'ckb');
    $this->post->setTranslation('title', 'عنوان', 'ar');

    $this->post->deleteTranslations('ckb');

    expect($this->post->translate('title', 'ckb'))->toBeNull();
    expect($this->post->translate('content', 'ckb'))->toBeNull();
    expect($this->post->translate('title', 'ar'))->toBe('عنوان');
});

test('translations are deleted when model is deleted', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->setTranslation('content', 'ناوەڕۆک', 'ckb');

    $this->post->delete();

    expect(Translation::where('translatable_type', TestPost::class)->count())->toBe(0);
});

test('can access translation as attribute when translatableFields is defined', function () {
    App::setLocale('ckb');
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');

    expect($this->post->title)->toBe('ناونیشان');
});

test('returns original value when translation does not exist for attribute access', function () {
    App::setLocale('ckb');

    expect($this->post->title)->toBe('Original Title');
});
