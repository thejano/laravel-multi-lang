<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use TheJano\MultiLang\Models\Translation;
use TheJano\MultiLang\Support\ArrayTranslationCacheStore;
use TheJano\MultiLang\Support\TranslationScopeGroup;
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

test('can set translations batch for multiple locales', function () {
    $this->post->setTranslationsBatch([
        'ckb' => [
            'title' => 'ناونیشانی نوێ',
            'content' => 'ناوەڕۆکی نوێ',
        ],
        'ar' => [
            'title' => 'عنوان جديد',
        ],
    ]);

    expect($this->post->translate('title', 'ckb'))->toBe('ناونیشانی نوێ');
    expect($this->post->translate('content', 'ckb'))->toBe('ناوەڕۆکی نوێ');
    expect($this->post->translate('title', 'ar'))->toBe('عنوان جديد');
});

test('set translations batch with detach removes missing entries', function () {
    $this->post->setTranslationsBatch([
        'ckb' => [
            'title' => 'سەرنجدانی پێشوو',
            'content' => 'ناوەڕۆکی پێشوو',
        ],
        'ar' => [
            'title' => 'عنوان قديم',
        ],
    ]);

    $this->post->setTranslationsBatch([
        'ckb' => [
            'title' => 'سەرنجدانی نوێ',
        ],
    ], detachMissing: true);

    expect($this->post->translate('title', 'ckb'))->toBe('سەرنجدانی نوێ');
    expect($this->post->translate('content', 'ckb'))->toBeNull();
    expect($this->post->translate('title', 'ar'))->toBeNull();
});

test('can get translation for a field', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');

    expect($this->post->translate('title', 'ckb'))->toBe('ناونیشان');
});

test('returns null when translation does not exist', function () {
    expect($this->post->translate('title', 'ckb'))->toBeNull();
});

test('throws when setting translation for non translatable fields', function () {
    expect(fn () => $this->post->setTranslation('description', 'باسکردن', 'ckb'))
        ->toThrow(\InvalidArgumentException::class, "Field 'description' is not defined in translatableFields.");
});

test('throws when accessing translation for non translatable fields', function () {
    expect(fn () => $this->post->translate('description', 'ckb'))
        ->toThrow(\InvalidArgumentException::class, "Field 'description' is not defined in translatableFields.");
});

test('can load translations for multiple locales at once', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->setTranslation('title', 'عنوان', 'ar');

    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->post->loadTranslations(['ckb', 'ar']);

    DB::flushQueryLog();

    $ckbTitle = $this->post->translate('title', 'ckb');
    $arTitle = $this->post->translate('title', 'ar');

    expect(DB::getQueryLog())->toHaveCount(0);
    expect($ckbTitle)->toBe('ناونیشان');
    expect($arTitle)->toBe('عنوان');
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

test('whereTranslate scope filters by locale', function () {
    App::setLocale('ckb');
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');

    $found = TestPost::query()
        ->whereTranslate('title', 'ناونیشان')
        ->first();

    expect($found?->id)->toBe($this->post->id);
});

test('orWhereTranslate scope applies an or condition', function () {
    $otherPost = TestPost::create([
        'title' => 'Second Title',
        'content' => 'Second Content',
    ]);
    $otherPost->setTranslation('title', 'عنوان', 'ar');

    $results = TestPost::query()
        ->where('id', 0)
        ->orWhereTranslate('title', 'عنوان', 'ar')
        ->pluck('id');

    expect($results)->toContain($otherPost->id)
        ->not->toContain($this->post->id);
});

test('whereTranslateLike scope supports wildcard filtering', function () {
    $this->post->setTranslation('content', 'ناوەڕۆکی نوێ', 'ckb');

    $found = TestPost::query()
        ->whereTranslate('content', 'like', 'ناوەڕۆک%', 'ckb')
        ->first();

    expect($found?->id)->toBe($this->post->id);
});

test('whereTranslateIn scope filters by multiple values', function () {
    $this->post->setTranslation('title', 'عنوان', 'ar');

    $found = TestPost::query()
        ->whereTranslateIn('title', ['عنوان', 'ناونیشان'], 'ar')
        ->first();

    expect($found?->id)->toBe($this->post->id);
});

test('whereTranslateNotIn uses current locale when none is provided', function () {
    App::setLocale('ckb');

    $included = TestPost::create([
        'title' => 'Included Title',
        'content' => 'Included Content',
    ]);
    $included->setTranslation('title', 'ناونیشانی نووسراو ', 'ckb');

    $excluded = TestPost::create([
        'title' => 'Excluded Title',
        'content' => 'Excluded Content',
    ]);
    $excluded->setTranslation('title', 'ناونیشان', 'ckb');

    $results = TestPost::query()
        ->whereTranslateNotIn('title', ['ناونیشان'])
        ->pluck('id');

    expect($results)->toContain($included->id)
        ->not->toContain($excluded->id);
});

test('whereTranslateNotIn scope excludes provided values', function () {
    $first = TestPost::create([
        'title' => 'First Title',
        'content' => 'First Content',
    ]);
    $first->setTranslation('title', 'ناونیشان', 'ckb');

    $second = TestPost::create([
        'title' => 'Second Title',
        'content' => 'Second Content',
    ]);
    $second->setTranslation('title', 'ناونیشانی دووەم', 'ckb');

    $results = TestPost::query()
        ->whereTranslateNotIn('title', ['ناونیشان'], 'ckb')
        ->pluck('id');

    expect($results)->toContain($second->id)
        ->not->toContain($first->id)
        ->not->toContain($this->post->id);
});

test('orWhereTranslateNotIn scope applies an or condition', function () {
    $matching = TestPost::create([
        'title' => 'Matching Title',
        'content' => 'Matching Content',
    ]);
    $matching->setTranslation('title', 'ناونیشانی هاوتا', 'ckb');

    $results = TestPost::query()
        ->where('id', 0)
        ->orWhereTranslateNotIn('title', ['ناونیشان'], 'ckb')
        ->pluck('id');

    expect($results)->toContain($matching->id)
        ->not->toContain($this->post->id);
});

test('whereTranslate supports not equals operator', function () {
    $first = TestPost::create([
        'title' => 'First Title',
        'content' => 'First Content',
    ]);
    $first->setTranslation('title', 'ناونیشان', 'ckb');

    $second = TestPost::create([
        'title' => 'Second Title',
        'content' => 'Second Content',
    ]);
    $second->setTranslation('title', 'ناونیشانی دووەم', 'ckb');

    $results = TestPost::query()
        ->whereTranslate('title', '!=', 'ناونیشان', 'ckb')
        ->pluck('id');

    expect($results)->toContain($second->id)
        ->not->toContain($first->id)
        ->not->toContain($this->post->id);
});

test('whereTranslate supports operator syntax', function () {
    $match = TestPost::create([
        'title' => 'Hello Title',
        'content' => 'Hello Content',
    ]);
    $match->setTranslation('title', 'سڵاو جیهان', 'ckb');

    $miss = TestPost::create([
        'title' => 'Another Title',
        'content' => 'Another Content',
    ]);
    $miss->setTranslation('title', 'ناونیشانی تر', 'ckb');

    $results = TestPost::query()
        ->whereTranslate('title', 'like', 'سڵاو%', 'ckb')
        ->pluck('id');

    expect($results)->toContain($match->id)
        ->not->toContain($miss->id)
        ->not->toContain($this->post->id);
});

test('whereTranslateNull includes missing or null translations', function () {
    $withValue = TestPost::create([
        'title' => 'Value Title',
        'content' => 'Value Content',
    ]);
    $withValue->setTranslation('title', 'ناونیشان', 'ckb');

    $withNull = TestPost::create([
        'title' => 'Null Title',
        'content' => 'Null Content',
    ]);
    $withNull->translations()->create([
        'locale' => 'ckb',
        'field' => 'title',
        'translation' => null,
    ]);

    $without = TestPost::create([
        'title' => 'Without Title',
        'content' => 'Without Content',
    ]);

    $results = TestPost::query()
        ->whereTranslateNull('title', 'ckb')
        ->pluck('id');

    expect($results)->toContain($withNull->id)
        ->toContain($without->id)
        ->not->toContain($withValue->id);
});

test('orWhereTranslateNull applies an or condition', function () {
    $withNull = TestPost::create([
        'title' => 'Null Title',
        'content' => 'Null Content',
    ]);
    $withNull->translations()->create([
        'locale' => 'ckb',
        'field' => 'title',
        'translation' => null,
    ]);

    $results = TestPost::query()
        ->where('id', 0)
        ->orWhereTranslateNull('title', 'ckb')
        ->pluck('id');

    expect($results)->toContain($withNull->id);
});

test('whereTranslateNotNull requires a non null translation value', function () {
    $withValue = TestPost::create([
        'title' => 'Value Title',
        'content' => 'Value Content',
    ]);
    $withValue->setTranslation('title', 'ناونیشان', 'ckb');

    $withNull = TestPost::create([
        'title' => 'Null Title',
        'content' => 'Null Content',
    ]);
    $withNull->translations()->create([
        'locale' => 'ckb',
        'field' => 'title',
        'translation' => null,
    ]);

    $without = TestPost::create([
        'title' => 'Without Title',
        'content' => 'Without Content',
    ]);

    $results = TestPost::query()
        ->whereTranslateNotNull('title', 'ckb')
        ->pluck('id');

    expect($results)->toContain($withValue->id)
        ->not->toContain($withNull->id)
        ->not->toContain($without->id);
});

test('orWhereTranslateNotNull applies an or condition', function () {
    $withValue = TestPost::create([
        'title' => 'Value Title',
        'content' => 'Value Content',
    ]);
    $withValue->setTranslation('title', 'ناونیشان', 'ckb');

    $results = TestPost::query()
        ->where('id', 0)
        ->orWhereTranslateNotNull('title', 'ckb')
        ->pluck('id');

    expect($results)->toContain($withValue->id);
});

test('whereTranslateNull uses current locale by default', function () {
    App::setLocale('ckb');

    $withNull = TestPost::create([
        'title' => 'Null Title',
        'content' => 'Null Content',
    ]);
    $withNull->translations()->create([
        'locale' => 'ckb',
        'field' => 'title',
        'translation' => null,
    ]);

    $withValue = TestPost::create([
        'title' => 'Value Title',
        'content' => 'Value Content',
    ]);
    $withValue->setTranslation('title', 'ناونیشان', 'ckb');

    $results = TestPost::query()
        ->whereTranslateNull('title')
        ->pluck('id');

    expect($results)->toContain($withNull->id)
        ->not->toContain($withValue->id);
});

test('whereTranslateWithFallback matches fallback locale translations', function () {
    $this->post->setTranslation('title', 'عنوان', 'ar');

    $results = TestPost::query()
        ->whereTranslateWithFallback('title', 'عنوان', 'ckb', 'ar')
        ->pluck('id');

    expect($results)->toContain($this->post->id);
});

test('whereTranslateWithFallback matches original attribute', function () {
    $results = TestPost::query()
        ->whereTranslateWithFallback('title', 'Original Title', 'ckb', 'ar')
        ->pluck('id');

    expect($results)->toContain($this->post->id);
});

test('orWhereTranslateWithFallback adds fallback clause with or condition', function () {
    $anotherPost = TestPost::create([
        'title' => 'Second Original Title',
        'content' => 'Second Content',
    ]);
    $anotherPost->setTranslation('title', 'عنوان', 'ar');

    $results = TestPost::query()
        ->where('id', 0)
        ->orWhereTranslateWithFallback('title', 'عنوان', 'ckb', 'ar')
        ->pluck('id');

    expect($results)->toContain($anotherPost->id)
        ->not->toContain($this->post->id);
});

test('external translation cache store prevents additional queries', function () {
    ArrayTranslationCacheStore::flush();
    TestPost::setTranslationCacheStore(new ArrayTranslationCacheStore);

    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->loadTranslations('ckb');

    DB::enableQueryLog();
    DB::flushQueryLog();

    $fresh = TestPost::find($this->post->id);
    DB::flushQueryLog();

    $fresh->translate('title', 'ckb');

    expect(DB::getQueryLog())->toHaveCount(0);

    TestPost::resetTranslationCacheStore();
    ArrayTranslationCacheStore::flush();
});

test('laravel cache store integration caches translations across requests', function () {
    config(['cache.default' => 'array']);
    config([
        'multi-lang.cache_store' => [
            'driver' => null,
            'prefix' => 'pest:translations',
            'ttl' => null,
        ],
    ]);

    TestPost::resetTranslationCacheStore();

    $post = TestPost::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
    ]);
    $post->setTranslation('title', 'ناونیشان', 'ckb');
    $post->loadTranslations('ckb');

    DB::enableQueryLog();
    DB::flushQueryLog();

    $fresh = TestPost::find($post->id);
    DB::flushQueryLog();

    $fresh->translate('title', 'ckb');

    expect(DB::getQueryLog())->toHaveCount(0);

    TestPost::resetTranslationCacheStore();
    config(['multi-lang.cache_store' => null]);
});

test('whereTranslateGroup groups multiple translation clauses', function () {
    $fallbackPost = TestPost::create([
        'title' => 'Fallback Title',
        'content' => 'Fallback Content',
    ]);
    $fallbackPost->setTranslation('title', 'عنوان', 'ar');

    $kurdishPost = TestPost::create([
        'title' => 'Kurdish Title',
        'content' => 'Kurdish Content',
    ]);
    $kurdishPost->setTranslation('title', 'ناونیشان', 'ckb');

    $results = TestPost::query()
        ->whereTranslateGroup(function (TranslationScopeGroup $group) {
            $group->where('title', 'ناونیشان', 'ckb')
                ->orWhere('title', 'عنوان', 'ar');
        })
        ->pluck('id');

    expect($results)->toContain($fallbackPost->id)
        ->toContain($kurdishPost->id)
        ->not->toContain($this->post->id);
});

test('orWhereTranslateGroup allows combining grouped clauses with other constraints', function () {
    $kurdishPost = TestPost::create([
        'title' => 'Kurdish Title',
        'content' => 'Kurdish Content',
    ]);
    $kurdishPost->setTranslation('title', 'ناونیشانی تایبەتی', 'ckb');

    $results = TestPost::query()
        ->where('title', 'Original Title')
        ->orWhereTranslateGroup(function (TranslationScopeGroup $group) {
            $group->whereLike('title', 'ناونیشانی%', 'ckb')
                ->orWhereWithFallback('title', 'Original Title', 'ckb', 'ar');
        })
        ->pluck('id');

    expect($results)->toContain($this->post->id)
        ->toContain($kurdishPost->id);
});

test('whereTranslate accepts explicit locale parameter', function () {
    $this->post->setTranslation('title', 'عنوان', 'ar');

    $found = TestPost::query()
        ->whereTranslate('title', 'عنوان', 'ar')
        ->first();

    expect($found?->id)->toBe($this->post->id);
});

test('whereTranslate normalizes equality aliases', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ar');

    $found = TestPost::query()
        ->whereTranslate('title', '==', 'ناونیشان', 'ar')
        ->first();

    expect($found?->id)->toBe($this->post->id);
});

test('whereTranslateNot supports not equals operator', function () {
    $first = TestPost::create([
        'title' => 'First Title',
        'content' => 'First Content',
    ]);
    $first->setTranslation('title', 'ناونیشان', 'ckb');

    $second = TestPost::create([
        'title' => 'Second Title',
        'content' => 'Second Content',
    ]);
    $second->setTranslation('title', 'ناونیشانی دووەم', 'ckb');

    $results = TestPost::query()
        ->whereTranslateNot('title', '!=', 'ناونیشان', 'ckb')
        ->pluck('id');

    expect($results)->toContain($first->id)
        ->toContain($this->post->id)
        ->not->toContain($second->id);
});

test('whereTranslateNot excludes matching translations', function () {
    $matched = TestPost::create([
        'title' => 'Bonjour Title',
        'content' => 'Bonjour Content',
    ]);
    $matched->setTranslation('title', 'ناونیشان', 'ckb');

    $different = TestPost::create([
        'title' => 'Salut Title',
        'content' => 'Salut Content',
    ]);
    $different->setTranslation('title', 'ناونیشانی دووەم', 'ckb');

    $noTranslation = TestPost::create([
        'title' => 'Plain Title',
        'content' => 'Plain Content',
    ]);

    $results = TestPost::query()
        ->whereTranslateNot('title', 'ناونیشان', 'ckb')
        ->pluck('id');

    expect($results)->toContain($different->id)
        ->toContain($noTranslation->id)
        ->not->toContain($matched->id);
});

test('orWhereTranslateNot combines with other conditions', function () {
    $bonjour = TestPost::create([
        'title' => 'Bonjour Only',
        'content' => 'Bonjour Only Content',
    ]);
    $bonjour->setTranslation('title', 'ناونیشان', 'ckb');

    $salut = TestPost::create([
        'title' => 'Salut Only',
        'content' => 'Salut Only Content',
    ]);
    $salut->setTranslation('title', 'ناونیشانی دووەم', 'ckb');

    $results = TestPost::query()
        ->whereTranslate('title', 'ناونیشانی دووەم', 'ckb')
        ->orWhereTranslateNot('title', 'ناونیشان', 'ckb')
        ->pluck('id');

    expect($results)->toContain($salut->id)
        ->toContain($this->post->id)
        ->not->toContain($bonjour->id);
});

test('setting translation restores soft deleted entry', function () {
    $this->post->setTranslation('title', 'ناونیشان', 'ckb');
    $this->post->deleteTranslation('title', 'ckb');

    expect(Translation::withTrashed()
        ->where('translatable_type', TestPost::class)
        ->where('translatable_id', $this->post->id)
        ->where('locale', 'ckb')
        ->where('field', 'title')
        ->whereNotNull('deleted_at')
        ->exists())->toBeTrue();

    $this->post->setTranslation('title', 'ناونیشانی نوێ', 'ckb');

    expect($this->post->translate('title', 'ckb'))->toBe('ناونیشانی نوێ');
    expect(Translation::where('translatable_type', TestPost::class)
        ->where('translatable_id', $this->post->id)
        ->where('locale', 'ckb')
        ->where('field', 'title')
        ->count())->toBe(1);
});

test('translatePlural selects correct message', function () {
    $this->post->setTranslation('title', '{0}No apples|{1}One apple|[2,*]:count apples', 'en');

    expect($this->post->translatePlural('title', 0, [], 'en'))->toBe('No apples');
    expect($this->post->translatePlural('title', 1, [], 'en'))->toBe('One apple');
    expect($this->post->translatePlural('title', 5, ['count' => 5], 'en'))->toBe('5 apples');
    expect(trans_model_choice($this->post, 'title', 3, ['count' => 3], 'en'))->toBe('3 apples');
});
