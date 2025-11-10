<?php

namespace TheJano\MultiLang\Support;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use TheJano\MultiLang\Contracts\TranslationCacheStore;

class CacheTranslationCacheStore implements TranslationCacheStore
{
    public function __construct(
        protected Repository $cache,
        protected string $prefix = 'multi_lang:translations',
        protected ?int $ttl = null
    ) {}

    public function get(Model $model): ?array
    {
        $key = $this->key($model);

        if ($key === null) {
            return null;
        }

        return $this->cache->get($key);
    }

    public function put(Model $model, array $payload): void
    {
        $key = $this->key($model);

        if ($key === null) {
            return;
        }

        if ($this->ttl !== null) {
            $this->cache->put($key, $payload, $this->ttl);

            return;
        }

        $this->cache->forever($key, $payload);
    }

    public function forget(Model $model): void
    {
        $key = $this->key($model);

        if ($key === null) {
            return;
        }

        $this->cache->forget($key);
    }

    protected function key(Model $model): ?string
    {
        $id = $model->getKey();

        if ($id === null) {
            return null;
        }

        $base = Str::replace('\\', ':', $model::class);

        return "{$this->prefix}:{$base}:{$id}";
    }
}
