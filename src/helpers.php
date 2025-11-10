<?php

use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Facades\MultiLang;

if (! function_exists('multi_lang')) {
    /**
     * Get the MultiLang instance.
     */
    function multi_lang(): \TheJano\MultiLang\MultiLang
    {
        return app('multi-lang');
    }
}

if (! function_exists('trans_model')) {
    /**
     * Get translation for a model field.
     */
    function trans_model(Model $model, string $field, ?string $locale = null): ?string
    {
        return MultiLang::getModelTranslation($model, $field, $locale);
    }
}

if (! function_exists('trans_model_or_original')) {
    /**
     * Get translation or original for a model field.
     */
    function trans_model_or_original(Model $model, string $field, ?string $locale = null): ?string
    {
        return MultiLang::getModelTranslationOrOriginal($model, $field, $locale);
    }
}

if (! function_exists('get_available_locales')) {
    /**
     * Get all available locales.
     */
    function get_available_locales(): array
    {
        return MultiLang::getAvailableLocales();
    }
}

if (! function_exists('has_translations')) {
    /**
     * Check if a locale has translations.
     */
    function has_translations(string $locale): bool
    {
        return MultiLang::hasTranslations($locale);
    }
}
