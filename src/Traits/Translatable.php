<?php

namespace TheJano\MultiLang\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use TheJano\MultiLang\Models\Translation;

trait Translatable
{
    protected $cachedTranslations = null;

    protected $translationsLoaded = false;

    protected array $loadedTranslationLocales = [];

    protected bool $loadedAllTranslationLocales = false;

    protected static function bootTranslatable(): void
    {
        static::deleting(function ($model) {
            $model->deleteTranslations();
        });
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function scopeWithTranslations($query, string|array|null $locales = null)
    {
        if ($locales === null) {
            return $query->with('translations');
        }

        if (! is_array($locales)) {
            $locales = [$locales];
        }

        $locales = array_values(array_unique(array_filter($locales, static function ($locale) {
            return $locale !== null && $locale !== '';
        })));

        if (empty($locales)) {
            $locales = [App::getLocale()];
        }

        return $query->with(['translations' => function ($query) use ($locales) {
            $query->whereIn('locale', $locales);
        }]);
    }

    public function scopeWithAllTranslations($query)
    {
        return $query->with('translations');
    }

    public function loadTranslations(string|array|null $locales = null): void
    {
        $normalizedLocales = $this->normalizeLocalesInput($locales);

        if ($this->cachedTranslations !== null) {
            if ($normalizedLocales === null && $this->loadedAllTranslationLocales) {
                return;
            }

            if ($normalizedLocales !== null) {
                $missingLocales = array_diff($normalizedLocales, $this->loadedTranslationLocales);

                if (empty($missingLocales)) {
                    return;
                }

                $normalizedLocales = array_values($missingLocales);
            }
        }

        if ($normalizedLocales === null) {
            if (! $this->relationLoaded('translations') || ! $this->loadedAllTranslationLocales) {
                $this->load('translations');
            }

            $this->cacheTranslationsFromCollection($this->getRelation('translations'));
            $this->loadedAllTranslationLocales = true;
            $this->loadedTranslationLocales = array_keys($this->cachedTranslations ?? []);

            return;
        }

        $translationsCollection = collect();
        $remainingLocales = $normalizedLocales;

        if ($this->relationLoaded('translations')) {
            $relationTranslations = $this->getRelation('translations')->whereIn('locale', $normalizedLocales);

            if ($relationTranslations->isNotEmpty()) {
                $translationsCollection = $translationsCollection->merge($relationTranslations);
                $foundLocales = $relationTranslations->pluck('locale')->unique()->all();
                $remainingLocales = array_values(array_diff($remainingLocales, $foundLocales));
            }
        }

        if (! empty($remainingLocales)) {
            $fetchedTranslations = $this->translations()
                ->whereIn('locale', $remainingLocales)
                ->get();

            if ($fetchedTranslations->isNotEmpty()) {
                if ($this->relationLoaded('translations')) {
                    $this->setRelation(
                        'translations',
                        $this->getRelation('translations')->merge($fetchedTranslations)
                    );
                } else {
                    $this->setRelation('translations', $fetchedTranslations);
                }

                $translationsCollection = $translationsCollection->merge($fetchedTranslations);

                $remainingLocales = array_values(array_diff(
                    $remainingLocales,
                    $fetchedTranslations->pluck('locale')->unique()->all()
                ));
            }
        }

        if ($translationsCollection->isEmpty() && empty($remainingLocales)) {
            $this->initializeEmptyLocales($normalizedLocales);

            return;
        }

        $this->cacheTranslationsFromCollection($translationsCollection);

        if (! empty($remainingLocales)) {
            $this->initializeEmptyLocales($remainingLocales);
        }

        $this->loadedTranslationLocales = array_values(array_unique(array_merge(
            $this->loadedTranslationLocales,
            $normalizedLocales
        )));

        $this->translationsLoaded = true;
    }

    public function translate(string $field, ?string $locale = null): ?string
    {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        // If translations are eager loaded but not cached, cache them first
        if ($this->relationLoaded('translations') && ! $this->translationsLoaded) {
            $this->loadTranslations($locale);
        }

        $this->ensureLocaleCached($locale);

        // Try to use cached translations if available
        if ($this->cachedTranslations !== null) {
            return $this->cachedTranslations[$locale][$field] ?? null;
        }

        // Fallback to query (this will cause N+1 if called in a loop)
        $translation = $this->translations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        return $translation?->translation;
    }

    public function translateOrOriginal(string $field, ?string $locale = null): ?string
    {
        // Load translations if not already loaded
        if (! $this->translationsLoaded) {
            $this->loadTranslations($locale);
        }

        $translation = $this->translate($field, $locale);

        if ($translation !== null && $translation !== '') {
            return $translation;
        }

        // Try fallback locale if translation is not found
        $fallbackLocale = config('app.fallback_locale', config('app.locale', 'en'));
        if ($locale !== $fallbackLocale) {
            $fallbackTranslation = $this->translate($field, $fallbackLocale);
            if ($fallbackTranslation !== null && $fallbackTranslation !== '') {
                return $fallbackTranslation;
            }
        }

        return $this->getAttribute($field);
    }

    public function setTranslation(string $field, string $value, ?string $locale = null): void
    {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        $this->translations()->updateOrCreate(
            [
                'locale' => $locale,
                'field' => $field,
            ],
            [
                'translation' => $value,
            ]
        );

        // Invalidate cache and relation
        $this->cachedTranslations = null;
        $this->translationsLoaded = false;
        $this->loadedTranslationLocales = [];
        $this->loadedAllTranslationLocales = false;

        // If translations relation is loaded, update it or unset it
        if ($this->relationLoaded('translations')) {
            $this->unsetRelation('translations');
        }
    }

    public function setTranslations(array $translations, ?string $locale = null): void
    {
        $locale = $locale ?? App::getLocale();

        foreach ($translations as $field => $value) {
            $this->ensureFieldIsTranslatable($field);
            $this->setTranslation($field, $value, $locale);
        }
    }

    public function getTranslations(?string $locale = null): array
    {
        $locale = $locale ?? App::getLocale();

        $this->ensureLocaleCached($locale);

        // Return cached translations for the locale
        if ($this->cachedTranslations !== null && array_key_exists($locale, $this->cachedTranslations)) {
            $translations = $this->cachedTranslations[$locale];

            if (! empty($translatableFields = $this->getTranslatableFields())) {
                return array_intersect_key(
                    $translations,
                    array_flip($translatableFields)
                );
            }

            return $translations;
        }

        // Return empty array if no translations found
        return [];
    }

    public function getAllTranslations(): array
    {
        // Load translations if not already loaded
        if (! $this->translationsLoaded) {
            $this->loadTranslations();
        }

        // Return cached translations (all locales)
        return $this->cachedTranslations ?? [];
    }

    public function deleteTranslation(string $field, ?string $locale = null): bool
    {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        $deleted = $this->translations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->delete();

        if ($deleted) {
            $this->cachedTranslations = null;
            $this->translationsLoaded = false;
            $this->loadedTranslationLocales = [];
            $this->loadedAllTranslationLocales = false;
        }

        return $deleted;
    }

    public function deleteTranslations(?string $locale = null): bool
    {
        if ($locale !== null) {
            $deleted = $this->translations()
                ->where('locale', $locale)
                ->delete();
        } else {
            $deleted = $this->translations()->delete();
        }

        if ($deleted) {
            $this->cachedTranslations = null;
            $this->translationsLoaded = false;
            $this->loadedTranslationLocales = [];
            $this->loadedAllTranslationLocales = false;
        }

        return (bool) $deleted;
    }

    public function hasTranslation(string $field, ?string $locale = null): bool
    {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        $this->ensureLocaleCached($locale);

        // Check cached translations
        if ($this->cachedTranslations !== null && array_key_exists($locale, $this->cachedTranslations)) {
            return isset($this->cachedTranslations[$locale][$field]);
        }

        return false;
    }

    public function getTranslatableFields(): array
    {
        return $this->translatableFields ?? [];
    }

    protected function normalizeLocalesInput(string|array|null $locales): ?array
    {
        if ($locales === null) {
            return null;
        }

        if (! is_array($locales)) {
            $locales = [$locales];
        }

        $normalized = array_values(array_unique(array_filter($locales, static function ($locale) {
            return $locale !== null && $locale !== '';
        })));

        if (empty($normalized)) {
            return [App::getLocale()];
        }

        return $normalized;
    }

    protected function cacheTranslationsFromCollection($translations): void
    {
        $this->cachedTranslations = $this->cachedTranslations ?? [];

        if ($translations->isEmpty()) {
            $this->translationsLoaded = true;

            return;
        }

        $translatableFields = $this->getTranslatableFields();

        $mapped = $translations
            ->when(! empty($translatableFields), function ($collection) use ($translatableFields) {
                return $collection->filter(function ($translation) use ($translatableFields) {
                    return in_array($translation->field, $translatableFields, true);
                });
            })
            ->groupBy('locale')
            ->map(function ($translations) {
                return $translations->pluck('translation', 'field')->toArray();
            })
            ->toArray();

        if ($this->loadedAllTranslationLocales) {
            $this->cachedTranslations = $mapped;
        } else {
            $this->cachedTranslations = array_replace($this->cachedTranslations, $mapped);
        }

        $this->translationsLoaded = true;
    }

    protected function initializeEmptyLocales(array $locales): void
    {
        $this->cachedTranslations = $this->cachedTranslations ?? [];

        foreach ($locales as $locale) {
            if (! array_key_exists($locale, $this->cachedTranslations)) {
                $this->cachedTranslations[$locale] = [];
            }
        }

        $this->translationsLoaded = true;
    }

    protected function ensureLocaleCached(string $locale): void
    {
        if ($locale === '') {
            return;
        }

        if (! $this->translationsLoaded || $this->cachedTranslations === null) {
            $this->loadTranslations($locale);

            return;
        }

        if (! array_key_exists($locale, $this->cachedTranslations)) {
            $this->loadTranslations($locale);
        }
    }

    protected function ensureFieldIsTranslatable(string $field): void
    {
        $translatableFields = $this->getTranslatableFields();

        if (empty($translatableFields)) {
            return;
        }

        if (! in_array($field, $translatableFields, true)) {
            throw new InvalidArgumentException("Field '{$field}' is not defined in translatableFields.");
        }
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // Check if this is a translatable field and we should return translation
        $translatableFields = $this->getTranslatableFields();

        if (! empty($translatableFields) && in_array($key, $translatableFields)) {
            // Auto-load translations for attribute access if not loaded
            if (! $this->translationsLoaded && ! $this->relationLoaded('translations')) {
                $this->loadTranslations();
            }

            $locale = App::getLocale();
            $translation = $this->translate($key, $locale);

            if ($translation !== null && $translation !== '') {
                return $translation;
            }

            // Try fallback locale if translation is not found
            $fallbackLocale = config('app.fallback_locale', config('app.locale', 'en'));
            if ($locale !== $fallbackLocale) {
                $fallbackTranslation = $this->translate($key, $fallbackLocale);
                if ($fallbackTranslation !== null && $fallbackTranslation !== '') {
                    return $fallbackTranslation;
                }
            }
        }

        return $value;
    }
}
