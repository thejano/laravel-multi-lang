<?php

namespace TheJano\MultiLang\Traits\Concerns;

use Illuminate\Support\Facades\App;
use Illuminate\Translation\MessageSelector;
use TheJano\MultiLang\Models\Translation;

trait HandlesTranslationAccess
{
    protected ?MessageSelector $messageSelectorInstance = null;

    public function loadTranslations(string|array|null $locales = null): void
    {
        $this->ensureExternalCacheLoaded();

        $normalizedLocales = $this->normalizeLocalesInput($locales);

        if ($this->attemptBatchTranslationLoad($normalizedLocales)) {
            return;
        }

        if ($this->cachedTranslations !== null) {
            if ($normalizedLocales === null && $this->loadedAllTranslationLocales) {
                $this->clearFromTranslationLazyLoadQueue();

                return;
            }

            if ($normalizedLocales !== null) {
                $missingLocales = array_diff($normalizedLocales, $this->loadedTranslationLocales);

                if (empty($missingLocales)) {
                    $this->clearFromTranslationLazyLoadQueue();

                    return;
                }

                $normalizedLocales = array_values($missingLocales);
            }
        }

        if ($normalizedLocales === null) {
            if (! $this->relationLoaded('translations')) {
                $this->load('translations');
            }

            $translations = $this->relationLoaded('translations')
                ? $this->getRelation('translations')
                : collect();

            $this->cacheTranslationsFromCollection($translations);
            $this->loadedAllTranslationLocales = true;
            $this->loadedTranslationLocales = array_keys($this->cachedTranslations ?? []);
            $this->storeExternalCache();
            $this->clearFromTranslationLazyLoadQueue();

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
            $primaryLocales = [];

            if ($this->relationLoaded('translations')) {
                [$remainingLocales, $primaryLocales] = $this->filterPrimaryLocales($remainingLocales);

                if (! empty($primaryLocales)) {
                    $this->initializeEmptyLocales($primaryLocales);

                    $this->loadedTranslationLocales = array_values(array_unique(array_merge(
                        $this->loadedTranslationLocales,
                        $primaryLocales
                    )));
                }

                if (empty($remainingLocales)) {
                    $this->translationsLoaded = true;
                    $this->storeExternalCache();
                    $this->clearFromTranslationLazyLoadQueue();

                    return;
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

            if (empty($remainingLocales) && ! empty($primaryLocales)) {
                $this->translationsLoaded = true;
                $this->storeExternalCache();
                $this->clearFromTranslationLazyLoadQueue();

                return;
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
        $this->clearFromTranslationLazyLoadQueue();
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

    protected function filterPrimaryLocales(array $locales): array
    {
        $primary = $this->primaryLocales();

        if (empty($primary)) {
            return [$locales, []];
        }

        $skipped = array_values(array_intersect($locales, $primary));
        $remaining = array_values(array_diff($locales, $skipped));

        return [$remaining, $skipped];
    }

    protected function primaryLocales(): array
    {
        $locales = [
            config('app.locale'),
            config('app.fallback_locale'),
            App::getLocale(),
        ];

        return array_values(array_unique(array_filter($locales, static function ($locale) {
            return $locale !== null && $locale !== '';
        })));
    }

    protected function attemptBatchTranslationLoad(?array $normalizedLocales): bool
    {
        $class = static::class;

        if (! isset(static::$translationLazyLoadQueue[$class]) || empty(static::$translationLazyLoadQueue[$class])) {
            return false;
        }

        $queue = static::$translationLazyLoadQueue[$class];
        $modelsToLoad = [];

        foreach ($queue as $queuedModel) {
            if ($queuedModel->getKey() === null) {
                continue;
            }

            if ($normalizedLocales === null) {
                if ($queuedModel->loadedAllTranslationLocales) {
                    continue;
                }

                if ($queuedModel->relationLoaded('translations')) {
                    $queuedModel->cacheTranslationsFromCollection($queuedModel->getRelation('translations'));
                    $queuedModel->loadedAllTranslationLocales = true;
                    $queuedModel->loadedTranslationLocales = array_keys($queuedModel->cachedTranslations ?? []);
                    $queuedModel->storeExternalCache();
                    $queuedModel->clearFromTranslationLazyLoadQueue();

                    continue;
                }
            } else {
                $availableLocales = $queuedModel->loadedTranslationLocales;

                if ($queuedModel->relationLoaded('translations')) {
                    $relationLocales = $queuedModel->getRelation('translations')
                        ->pluck('locale')
                        ->unique()
                        ->all();

                    $availableLocales = array_values(array_unique(array_merge($availableLocales, $relationLocales)));
                }

                $missingLocales = array_diff($normalizedLocales, $availableLocales);

                if (! empty($missingLocales) && $queuedModel->relationLoaded('translations')) {
                    [$missingLocales, $skippedLocales] = $queuedModel->filterPrimaryLocales($missingLocales);

                    if (! empty($skippedLocales)) {
                        $queuedModel->initializeEmptyLocales($skippedLocales);

                        $queuedModel->loadedTranslationLocales = array_values(array_unique(array_merge(
                            $queuedModel->loadedTranslationLocales,
                            $skippedLocales
                        )));

                        $queuedModel->storeExternalCache();
                        $queuedModel->clearFromTranslationLazyLoadQueue();
                    }

                    if (empty($missingLocales)) {
                        continue;
                    }
                }

                if (empty($missingLocales)) {
                    if ($queuedModel->relationLoaded('translations')) {
                        $queuedModel->cacheTranslationsFromCollection(
                            $queuedModel->getRelation('translations')->whereIn('locale', $normalizedLocales)
                        );

                        $queuedModel->loadedTranslationLocales = array_values(array_unique(array_merge(
                            $queuedModel->loadedTranslationLocales,
                            $normalizedLocales
                        )));

                        $queuedModel->storeExternalCache();
                        $queuedModel->clearFromTranslationLazyLoadQueue();
                    }

                    continue;
                }
            }

            $modelsToLoad[$queuedModel->getKey()] = $queuedModel;
        }

        $currentKey = $this->getKey();

        if ($currentKey !== null && isset(static::$translationLazyLoadQueue[$class][$currentKey])) {
            $modelsToLoad[$currentKey] = $this;
        }

        if (empty($modelsToLoad)) {
            return false;
        }

        $ids = array_keys($modelsToLoad);

        $translationQuery = Translation::query()
            ->where('translatable_type', $this->getMorphClass())
            ->whereIn('translatable_id', $ids);

        if ($normalizedLocales !== null) {
            $translationQuery->whereIn('locale', $normalizedLocales);
        }

        $translations = $translationQuery->get()->groupBy('translatable_id');

        foreach ($modelsToLoad as $id => $model) {
            $records = $translations[$id] ?? collect();

            if ($model->relationLoaded('translations')) {
                $model->setRelation(
                    'translations',
                    $model->getRelation('translations')->merge($records)
                );
            } else {
                $model->setRelation('translations', $records);
            }

            if ($records->isEmpty()) {
                if ($normalizedLocales !== null) {
                    [$remainingLocales, $skippedLocales] = $model->filterPrimaryLocales($normalizedLocales);

                    if (! empty($skippedLocales)) {
                        $model->initializeEmptyLocales($skippedLocales);
                        $model->loadedTranslationLocales = array_values(array_unique(array_merge(
                            $model->loadedTranslationLocales,
                            $skippedLocales
                        )));
                    }

                    if (! empty($remainingLocales)) {
                        $model->initializeEmptyLocales($remainingLocales);
                    }
                } else {
                    $model->initializeEmptyLocales([]);
                    $model->loadedAllTranslationLocales = true;
                }
            } else {
                $model->cacheTranslationsFromCollection($records);

                $loadedLocales = $normalizedLocales ?? $records->pluck('locale')->unique()->values()->all();

                $model->loadedTranslationLocales = array_values(array_unique(array_merge(
                    $model->loadedTranslationLocales,
                    $loadedLocales
                )));

                if ($normalizedLocales === null) {
                    $model->loadedAllTranslationLocales = true;
                }
            }

            $model->storeExternalCache();
            $model->clearFromTranslationLazyLoadQueue();
        }

        foreach ($ids as $id) {
            unset(static::$translationLazyLoadQueue[$class][$id]);
        }

        if (empty(static::$translationLazyLoadQueue[$class])) {
            unset(static::$translationLazyLoadQueue[$class]);
        }

        return true;
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
