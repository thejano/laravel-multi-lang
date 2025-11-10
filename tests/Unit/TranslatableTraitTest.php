<?php

use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Traits\Translatable;

test('translatable trait provides translations relationship method', function () {
    $model = new class extends Model
    {
        use Translatable;
    };

    expect(method_exists($model, 'translations'))->toBeTrue();
});

test('translatable trait provides translate method', function () {
    $model = new class extends Model
    {
        use Translatable;
    };

    expect(method_exists($model, 'translate'))->toBeTrue();
});

test('translatable trait provides setTranslation method', function () {
    $model = new class extends Model
    {
        use Translatable;
    };

    expect(method_exists($model, 'setTranslation'))->toBeTrue();
});

test('translatable trait provides getTranslations method', function () {
    $model = new class extends Model
    {
        use Translatable;
    };

    expect(method_exists($model, 'getTranslations'))->toBeTrue();
});

test('translatable trait provides withTranslations scope', function () {
    $model = new class extends Model
    {
        use Translatable;
    };

    expect(method_exists($model, 'scopeWithTranslations'))->toBeTrue();
});

test('translatable trait provides withAllTranslations scope', function () {
    $model = new class extends Model
    {
        use Translatable;
    };

    expect(method_exists($model, 'scopeWithAllTranslations'))->toBeTrue();
});

test('translatable trait provides loadTranslations method', function () {
    $model = new class extends Model
    {
        use Translatable;
    };

    expect(method_exists($model, 'loadTranslations'))->toBeTrue();
});

test('translatable trait provides getTranslatableFields method', function () {
    $model = new class extends Model
    {
        use Translatable;

        protected $translatableFields = ['title', 'content'];
    };

    expect(method_exists($model, 'getTranslatableFields'))->toBeTrue();
    expect($model->getTranslatableFields())->toBe(['title', 'content']);
});

test('getTranslatableFields returns empty array when not defined', function () {
    $model = new class extends Model
    {
        use Translatable;
    };

    expect($model->getTranslatableFields())->toBe([]);
});
