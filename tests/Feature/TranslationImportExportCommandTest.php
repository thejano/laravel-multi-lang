<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use TheJano\MultiLang\Tests\Feature\TestPost;

it('exports translations to json file', function () {
    $post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);

    $post->setTranslationsBatch([
        'ckb' => [
            'title' => 'ناونیشان',
            'content' => 'ناوەڕۆک',
        ],
        'ar' => [
            'title' => 'عنوان',
        ],
    ]);

    $path = storage_path('app/test_translations_export.json');

    if (File::exists($path)) {
        File::delete($path);
    }

    $this->artisan('multi-lang:export', [
        'model' => TestPost::class,
        '--path' => $path,
        '--locales' => 'ckb,ar',
    ])->assertExitCode(Command::SUCCESS);

    expect(File::exists($path))->toBeTrue();

    $payload = json_decode(File::get($path), true);

    expect($payload)->toBeArray()
        ->toHaveKey((string) $post->id);

    expect($payload[$post->id]['ckb']['title'])->toBe('ناونیشان');
    expect($payload[$post->id]['ar']['title'])->toBe('عنوان');

    File::delete($path);
});

it('imports translations from json file with strategies', function () {
    $post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);

    $post->setTranslation('title', 'Old Kurdish Title', 'ckb');

    $path = storage_path('app/test_translations_import.json');

    $data = [
        $post->id => [
            'ckb' => [
                'title' => 'ناونیشانی نوێ',
                'content' => 'ناوەڕۆکی نوێ',
            ],
            'ar' => [
                'title' => 'عنوان جديد',
            ],
        ],
    ];

    File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $this->artisan('multi-lang:import', [
        'model' => TestPost::class,
        '--path' => $path,
        '--strategy' => 'replace',
    ])->assertExitCode(Command::SUCCESS);

    $post->refresh();

    expect($post->translate('title', 'ckb'))->toBe('ناونیشانی نوێ');
    expect($post->translate('content', 'ckb'))->toBe('ناوەڕۆکی نوێ');
    expect($post->translate('title', 'ar'))->toBe('عنوان جديد');

    File::delete($path);
});

it('exports missing translations with source text', function () {
    config(['app.supported_locales' => ['en', 'ckb']]);

    $post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    $path = storage_path('app/test_missing_translations.json');

    if (File::exists($path)) {
        File::delete($path);
    }

    $this->artisan('multi-lang:export', [
        'model' => TestPost::class,
        '--path' => $path,
        '--locales' => 'ckb',
        '--missing' => true,
    ])->assertExitCode(Command::SUCCESS);

    $payload = json_decode(File::get($path), true);

    expect($payload)->toHaveKey((string) $post->id);
    expect($payload[$post->id])->toHaveKey('ckb');
    expect($payload[$post->id]['ckb'])->toHaveKey('content');
    expect($payload[$post->id]['ckb']['content'])->toBe('Original Content');
    expect($payload[$post->id]['ckb'])->not->toHaveKey('title');

    File::delete($path);
});

it('imports only missing translations when flag is used', function () {
    $post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    $path = storage_path('app/test_only_missing_import.json');

    $data = [
        $post->id => [
            'ckb' => [
                'title' => 'ناونیشانی نوێ',
                'content' => 'ناوەڕۆکی نوێ',
            ],
        ],
    ];

    File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $this->artisan('multi-lang:import', [
        'model' => TestPost::class,
        '--path' => $path,
        '--only-missing' => true,
    ])->assertExitCode(Command::SUCCESS);

    $post->refresh();

    expect($post->translate('title', 'ckb'))->toBe('ناونیشان'); // unchanged
    expect($post->translate('content', 'ckb'))->toBe('ناوەڕۆکی نوێ'); // newly filled

    File::delete($path);
});
