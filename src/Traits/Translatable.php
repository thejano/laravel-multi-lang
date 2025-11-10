<?php

namespace TheJano\MultiLang\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use TheJano\MultiLang\Contracts\TranslationCacheStore;
use TheJano\MultiLang\Models\Translation;
use TheJano\MultiLang\Traits\Concerns\HandlesTranslationAccess;
use TheJano\MultiLang\Traits\Concerns\HandlesTranslationCache;
use TheJano\MultiLang\Traits\Concerns\HandlesTranslationMutation;
use TheJano\MultiLang\Traits\Concerns\HandlesTranslationScopes;

trait Translatable
{
    use HandlesTranslationAccess;
    use HandlesTranslationCache;
    use HandlesTranslationMutation;
    use HandlesTranslationScopes;

    protected $cachedTranslations = null;

    protected $translationsLoaded = false;

    protected array $loadedTranslationLocales = [];

    protected bool $loadedAllTranslationLocales = false;

    protected bool $externalCacheLoaded = false;

    protected static bool $translationCacheStoreResolved = false;

    protected static ?TranslationCacheStore $translationCacheStore = null;

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

    public function hasTranslation(string $field, ?string $locale = null): bool
    {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        $this->ensureLocaleCached($locale);

        if ($this->cachedTranslations !== null && array_key_exists($locale, $this->cachedTranslations)) {
            return isset($this->cachedTranslations[$locale][$field]);
        }

        return false;
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        $translatableFields = $this->getTranslatableFields();

        if (! empty($translatableFields) && in_array($key, $translatableFields, true)) {
            if (! $this->translationsLoaded && ! $this->relationLoaded('translations')) {
                $this->loadTranslations();
            }

            $locale = App::getLocale();
            $translation = $this->translate($key, $locale);

            if ($translation !== null && $translation !== '') {
                return $translation;
            }

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
