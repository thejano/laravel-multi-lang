<?php

namespace TheJano\MultiLang\Support;

use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Contracts\TranslationCacheStore;

class ArrayTranslationCacheStore implements TranslationCacheStore
{
    protected static array $store = [];

    public function get(Model $model): ?array
    {
        $key = $this->key($model);

        if ($key === null) {
            return null;
        }

        return self::$store[$key] ?? null;
    }

    public function put(Model $model, array $payload): void
    {
        $key = $this->key($model);

        if ($key === null) {
            return;
        }

        self::$store[$key] = $payload;
    }

    public function forget(Model $model): void
    {
        $key = $this->key($model);

        if ($key === null) {
            return;
        }

        unset(self::$store[$key]);
    }

    public static function flush(): void
    {
        self::$store = [];
    }

    protected function key(Model $model): ?string
    {
        if (! $model->getKey()) {
            return null;
        }

        return sprintf('%s:%s', $model::class, $model->getKey());
    }
}
