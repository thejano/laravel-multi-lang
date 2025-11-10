<?php

namespace TheJano\MultiLang\Traits\Concerns;

use Illuminate\Support\Facades\App;
use InvalidArgumentException;

trait HandlesTranslationMutation
{
    public function setTranslation(string $field, string $value, ?string $locale = null): void
    {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        $translation = $this->translations()
            ->withTrashed()
            ->firstOrNew([
                'locale' => $locale,
                'field' => $field,
            ]);

        $translation->translation = $value;
        $translation->deleted_at = null;
        $translation->save();

        $this->invalidateCachedTranslations();

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

    public function setTranslationsBatch(array $translations, bool $detachMissing = false): void
    {
        if (empty($translations)) {
            if ($detachMissing) {
                $this->deleteTranslations();
            }

            return;
        }

        $localesToFields = [];

        foreach ($translations as $locale => $fields) {
            if (! is_string($locale) || $locale === '') {
                throw new InvalidArgumentException('Locale keys must be non-empty strings.');
            }

            if ($fields === null) {
                $fields = [];
            }

            if (! is_array($fields)) {
                throw new InvalidArgumentException('Each locale must map to an array of field => translation pairs.');
            }

            $localesToFields[$locale] = [];

            foreach ($fields as $field => $value) {
                $this->ensureFieldIsTranslatable($field);

                $translation = $this->translations()
                    ->withTrashed()
                    ->firstOrNew([
                        'locale' => $locale,
                        'field' => $field,
                    ]);

                $translation->translation = $value;
                $translation->deleted_at = null;
                $translation->save();

                $localesToFields[$locale][] = $field;
            }
        }

        $this->invalidateCachedTranslations();

        if ($this->relationLoaded('translations')) {
            $this->unsetRelation('translations');
        }

        if (! $detachMissing) {
            return;
        }

        $locales = array_keys($localesToFields);

        if (empty($locales)) {
            $this->deleteTranslations();

            return;
        }

        $this->translations()
            ->whereNotIn('locale', $locales)
            ->delete();

        foreach ($localesToFields as $locale => $fields) {
            if (empty($fields)) {
                $this->translations()
                    ->where('locale', $locale)
                    ->delete();

                continue;
            }

            $this->translations()
                ->where('locale', $locale)
                ->whereNotIn('field', array_unique($fields))
                ->delete();
        }
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
            $this->invalidateCachedTranslations();
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
            $this->invalidateCachedTranslations();
        }

        return (bool) $deleted;
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
        $this->storeExternalCache();
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

    protected function invalidateCachedTranslations(): void
    {
        $this->cachedTranslations = null;
        $this->translationsLoaded = false;
        $this->loadedTranslationLocales = [];
        $this->loadedAllTranslationLocales = false;
        $this->externalCacheLoaded = false;
        $this->forgetExternalCache();
    }
}
