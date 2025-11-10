<?php

use TheJano\MultiLang\Tests\Feature\TestPost;

test('helper function multi_lang returns MultiLang instance', function () {
    $multiLang = multi_lang();

    expect($multiLang)->toBeInstanceOf(\TheJano\MultiLang\MultiLang::class);
});

test('helper function trans_model works', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    expect(trans_model($post, 'title', 'ckb'))->toBe('ناونیشان');
    expect(trans_model($post, 'title', 'ar'))->toBeNull();
});

test('helper function trans_model_or_original works', function () {
    $post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    expect(trans_model_or_original($post, 'title', 'ckb'))->toBe('ناونیشان');
    expect(trans_model_or_original($post, 'content', 'ckb'))->toBe('Original Content');
});

test('helper function get_available_locales works', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');
    $post->setTranslation('title', 'عنوان', 'ar');

    $locales = get_available_locales();

    expect($locales)->toBeArray();
    expect($locales)->toEqualCanonicalizing(['en', 'ckb', 'ar']);
});

test('helper function has_translations works', function () {
    $post = TestPost::create([
        'title' => 'Test',
        'content' => 'Test Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    expect(has_translations('ckb'))->toBeTrue();
    expect(has_translations('de'))->toBeFalse();
});
