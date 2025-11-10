<?php

namespace TheJano\MultiLang\Traits\Concerns;

use Illuminate\Support\Facades\App;
use Illuminate\Translation\MessageSelector;

trait HandlesTranslationAccess
{
    protected ?MessageSelector $messageSelectorInstance = null;

    public function loadTranslations(string|array|null $locales = null): void
    {
        $this->ensureExternalCacheLoaded();

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
            $this->storeExternalCache();

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
        $this->storeExternalCache();
    }

    public function translate(string $field, ?string $locale = null): ?string
    {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        if ($this->relationLoaded('translations') && ! $this->translationsLoaded) {
            $this->loadTranslations($locale);
        }

        $this->ensureLocaleCached($locale);

        if ($this->cachedTranslations !== null) {
            return $this->cachedTranslations[$locale][$field] ?? null;
        }

        $translation = $this->translations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        return $translation?->translation;
    }

    public function translateOrOriginal(string $field, ?string $locale = null): ?string
    {
        if (! $this->translationsLoaded) {
            $this->loadTranslations($locale);
        }

        $translation = $this->translate($field, $locale);

        if ($translation !== null && $translation !== '') {
            return $translation;
        }

        $fallbackLocale = config('app.fallback_locale', config('app.locale', 'en'));
        if ($locale !== $fallbackLocale) {
            $fallbackTranslation = $this->translate($field, $fallbackLocale);
            if ($fallbackTranslation !== null && $fallbackTranslation !== '') {
                return $fallbackTranslation;
            }
        }

        return $this->getAttribute($field);
    }

    public function translatePlural(
        string $field,
        int|float $count,
        array $replace = [],
        ?string $locale = null
    ): ?string {
        $translation = $this->translate($field, $locale);

        if ($translation === null || $translation === '') {
            return null;
        }

        $locale = $locale ?? App::getLocale();

        $message = $this->messageSelector()->choose($translation, $count, $locale);

        return $this->makePluralReplacements($message, $count, $replace);
    }

    public function getTranslations(?string $locale = null): array
    {
        $locale = $locale ?? App::getLocale();

        $this->ensureLocaleCached($locale);

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

        return [];
    }

    public function getAllTranslations(): array
    {
        if (! $this->translationsLoaded) {
            $this->loadTranslations();
        }

        return $this->cachedTranslations ?? [];
    }

    protected function ensureLocaleCached(string $locale): void
    {
        if ($locale === '') {
            return;
        }

        $this->ensureExternalCacheLoaded();

        if (! $this->translationsLoaded || $this->cachedTranslations === null) {
            $this->loadTranslations($locale);

            return;
        }

        if (! array_key_exists($locale, $this->cachedTranslations)) {
            $this->loadTranslations($locale);
        }
    }

    protected function messageSelector(): MessageSelector
    {
        if ($this->messageSelectorInstance === null) {
            $this->messageSelectorInstance = new MessageSelector;
        }

        return $this->messageSelectorInstance;
    }

    protected function makePluralReplacements(string $message, int|float $count, array $replace): string
    {
        $replace = array_merge(['count' => $count], $replace);

        foreach ($replace as $key => $value) {
            $message = str_replace([':'.$key, '{'.$key.'}'], $value, $message);
        }

        return $message;
    }
}
