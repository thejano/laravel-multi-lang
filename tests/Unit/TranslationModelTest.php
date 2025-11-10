<?php

use TheJano\MultiLang\Models\Translation;

test('translation model has correct fillable fields', function () {
    $fillable = (new Translation)->getFillable();

    expect($fillable)->toContain('translatable_type');
    expect($fillable)->toContain('translatable_id');
    expect($fillable)->toContain('locale');
    expect($fillable)->toContain('field');
    expect($fillable)->toContain('translation');
});

test('translation model has translatable_id cast', function () {
    $translation = new Translation;
    $casts = $translation->getCasts();

    expect($casts)->toHaveKey('translatable_id');
    expect($casts['translatable_id'])->toBe('integer');
});

test('translation model has translatable relationship method', function () {
    $translation = new Translation;

    expect(method_exists($translation, 'translatable'))->toBeTrue();
});

test('translation model uses correct table name from config', function () {
    config(['multi-lang.translations_table' => 'custom_translations']);

    $translation = new Translation;

    expect($translation->getTable())->toBe('custom_translations');
});
