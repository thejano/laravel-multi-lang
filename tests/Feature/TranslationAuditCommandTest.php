<?php

use Illuminate\Console\Command;
use TheJano\MultiLang\Tests\Feature\TestPost;

it('reports missing translations through audit command', function () {
    $post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);

    $post->setTranslation('title', 'ناونیشان', 'ckb');

    $this->artisan('multi-lang:audit', [
        'model' => TestPost::class,
        '--locales' => 'ckb,ar',
    ])
        ->expectsTable(
            ['Locale', 'Field', 'Missing Records'],
            [
                ['Locale' => 'ar', 'Field' => 'content', 'Missing Records' => 1],
                ['Locale' => 'ar', 'Field' => 'title', 'Missing Records' => 1],
                ['Locale' => 'ckb', 'Field' => 'content', 'Missing Records' => 1],
            ]
        )
        ->assertExitCode(Command::FAILURE);
});

it('passes when translations are complete', function () {
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
            'content' => 'محتوى',
        ],
    ]);

    $this->artisan('multi-lang:audit', [
        'model' => TestPost::class,
        '--locales' => 'ckb,ar',
    ])->assertExitCode(Command::SUCCESS);
});
