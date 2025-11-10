<?php

namespace TheJano\MultiLang\Traits\Concerns;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use TheJano\MultiLang\Contracts\TranslationCacheStore;
use TheJano\MultiLang\Support\CacheTranslationCacheStore;

trait HandlesTranslationCache
{
    public static function setTranslationCacheStore(?TranslationCacheStore $store): void
    {
        static::$translationCacheStore = $store;
        static::$translationCacheStoreResolved = $store !== null;
    }

    public static function resetTranslationCacheStore(): void
    {
        static::$translationCacheStore = null;
        static::$translationCacheStoreResolved = false;
    }

    protected function ensureExternalCacheLoaded(): void
    {
        if ($this->externalCacheLoaded || $this->getKey() === null) {
            return;
        }

        $store = $this->translationCacheStore();

        if ($store === null) {
            $this->externalCacheLoaded = true;

            return;
        }

        $payload = $store->get($this);

        if ($payload !== null) {
            $this->cachedTranslations = $payload['translations'] ?? null;
            $this->loadedTranslationLocales = $payload['loaded_locales'] ?? array_keys($this->cachedTranslations ?? []);
            $this->loadedAllTranslationLocales = (bool) ($payload['loaded_all'] ?? false);
            $this->translationsLoaded = (bool) ($payload['translations_loaded'] ?? false);
        }

        $this->externalCacheLoaded = true;
    }

    protected function storeExternalCache(): void
    {
        $store = $this->translationCacheStore();

        if ($store === null || $this->getKey() === null) {
            return;
        }

        if ($this->cachedTranslations === null) {
            $store->forget($this);

            return;
        }

        $store->put($this, [
            'translations' => $this->cachedTranslations,
            'loaded_locales' => $this->loadedTranslationLocales,
            'loaded_all' => $this->loadedAllTranslationLocales,
            'translations_loaded' => $this->translationsLoaded,
        ]);
    }

    protected function forgetExternalCache(): void
    {
        $store = $this->translationCacheStore();

        if ($store !== null && $this->getKey() !== null) {
            $store->forget($this);
        }

        $this->externalCacheLoaded = false;
    }

    protected function translationCacheStore(): ?TranslationCacheStore
    {
        if (! static::$translationCacheStoreResolved) {
            $configuredStore = config('multi-lang.cache_store');

            if ($configuredStore instanceof TranslationCacheStore) {
                static::$translationCacheStore = $configuredStore;
                static::$translationCacheStoreResolved = true;
            } elseif (is_string($configuredStore) && $configuredStore !== '') {
                static::$translationCacheStore = app($configuredStore);
                static::$translationCacheStoreResolved = true;
            } elseif (is_array($configuredStore)) {
                $driver = $configuredStore['driver'] ?? null;
                $prefix = $configuredStore['prefix'] ?? 'multi_lang:translations';
                $ttl = $configuredStore['ttl'] ?? null;

                $cache = $driver !== null
                    ? Cache::store($driver)
                    : Cache::store(config('cache.default'));

                static::$translationCacheStore = new CacheTranslationCacheStore(
                    $cache,
                    $prefix,
                    $ttl !== null ? (int) $ttl : null
                );

                static::$translationCacheStoreResolved = true;
            } elseif ($configuredStore === null) {
                static::$translationCacheStore = null;
                static::$translationCacheStoreResolved = true;
            } else {
                throw new InvalidArgumentException(
                    'The configured translation cache store must resolve to '.TranslationCacheStore::class
                );
            }
        }

        return static::$translationCacheStore;
    }
}
