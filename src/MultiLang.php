<?php

namespace TheJano\MultiLang;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use TheJano\MultiLang\Models\Translation;

class MultiLang
{
    /**
     * Get the current locale.
     */
    public function getLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Set the current locale.
     */
    public function setLocale(string $locale): void
    {
        App::setLocale($locale);
    }

    /**
     * Get the default application locale.
     */
    public function getDefaultLocale(): string
    {
        return config('app.locale', 'en');
    }

    /**
     * Get the fallback application locale.
     */
    public function getFallbackLocale(): string
    {
        return config('app.fallback_locale', $this->getDefaultLocale());
    }

    /**
     * Get the supported application locales.
     */
    public function getSupportedLocales(): array
    {
        $locales = config('app.supported_locales');

        if (is_string($locales)) {
            $locales = preg_split('/[\s,]+/', $locales, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_array($locales) && ! empty($locales)) {
            return array_values(array_unique($locales));
        }

        $configuredLocales = config('app.locales');

        if (is_string($configuredLocales)) {
            $configuredLocales = preg_split('/[\s,]+/', $configuredLocales, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_array($configuredLocales) && ! empty($configuredLocales)) {
            return array_values(array_unique($configuredLocales));
        }

        return [$this->getDefaultLocale()];
    }

    /**
     * Get the locales that the application should use.
     */
    public function getAvailableLocales(): array
    {
        return $this->getSupportedLocales();
    }

    /**
     * Get locales that currently have translations stored.
     */
    public function getLocalesWithTranslations(): array
    {
        return Translation::distinct()
            ->pluck('locale')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get translations count for a specific locale.
     */
    public function getTranslationsCount(?string $locale = null): int
    {
        $locale = $locale ?? $this->getLocale();

        return Translation::where('locale', $locale)->count();
    }

    /**
     * Check if a locale has translations.
     */
    public function hasTranslations(string $locale): bool
    {
        return Translation::where('locale', $locale)->exists();
    }

    /**
     * Get all translations for a model instance.
     */
    public function getModelTranslations(Model $model, ?string $locale = null): array
    {
        if (! method_exists($model, 'getTranslations')) {
            return [];
        }

        return $model->getTranslations($locale);
    }

    /**
     * Check if a model has translations for a locale.
     */
    public function modelHasTranslation(Model $model, string $field, ?string $locale = null): bool
    {
        if (! method_exists($model, 'hasTranslation')) {
            return false;
        }

        return $model->hasTranslation($field, $locale);
    }

    /**
     * Get translation for a model field.
     */
    public function getModelTranslation(Model $model, string $field, ?string $locale = null): ?string
    {
        if (! method_exists($model, 'translate')) {
            return null;
        }

        return $model->translate($field, $locale);
    }

    /**
     * Get translation or original for a model field.
     */
    public function getModelTranslationOrOriginal(Model $model, string $field, ?string $locale = null): ?string
    {
        if (! method_exists($model, 'translateOrOriginal')) {
            return $model->getAttribute($field);
        }

        return $model->translateOrOriginal($field, $locale);
    }

    /**
     * Get pluralized translation for a model field.
     */
    public function getModelTranslationPlural(
        Model $model,
        string $field,
        int|float $count,
        array $replace = [],
        ?string $locale = null
    ): ?string {
        if (! method_exists($model, 'translatePlural')) {
            return null;
        }

        return $model->translatePlural($field, $count, $replace, $locale);
    }
}
