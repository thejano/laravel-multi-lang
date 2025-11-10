<?php

namespace TheJano\MultiLang\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use TheJano\MultiLang\Models\Translation;
use InvalidArgumentException;

trait Translatable
{
    protected $cachedTranslations = null;

    protected $translationsLoaded = false;

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

    public function scopeWithTranslations($query, ?string $locale = null)
    {
        $locale = $locale ?? App::getLocale();

        return $query->with(['translations' => function ($query) use ($locale) {
            $query->where('locale', $locale);
        }]);
    }

    public function scopeWithAllTranslations($query)
    {
        return $query->with('translations');
    }

    public function loadTranslations(?string $locale = null): void
    {
        if ($this->translationsLoaded && $this->cachedTranslations !== null) {
            return;
        }

        if (! $this->relationLoaded('translations')) {
            $this->load('translations');
        }

        $translations = $this->getRelation('translations');
        $translatableFields = $this->getTranslatableFields();

        if ($translations->isEmpty()) {
            $this->cachedTranslations = [];
            $this->translationsLoaded = true;

            return;
        }

        $this->cachedTranslations = $translations
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

        // Try to use cached translations if available
        if ($this->translationsLoaded && $this->cachedTranslations !== null) {
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

        // Load translations if not already loaded
        if (! $this->translationsLoaded) {
            $this->loadTranslations($locale);
        }

        // Return cached translations for the locale
        if ($this->cachedTranslations !== null && isset($this->cachedTranslations[$locale])) {
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

        return $this->translations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->delete();
    }

    public function deleteTranslations(?string $locale = null): bool
    {
        if ($locale !== null) {
            return $this->translations()
                ->where('locale', $locale)
                ->delete();
        }

        return $this->translations()->delete();
    }

    public function hasTranslation(string $field, ?string $locale = null): bool
    {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        // Load translations if not already loaded
        if (! $this->translationsLoaded) {
            $this->loadTranslations($locale);
        }

        // Check cached translations
        if ($this->cachedTranslations !== null && isset($this->cachedTranslations[$locale])) {
            return isset($this->cachedTranslations[$locale][$field]);
        }

        return false;
    }

    public function getTranslatableFields(): array
    {
        return $this->translatableFields ?? [];
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
