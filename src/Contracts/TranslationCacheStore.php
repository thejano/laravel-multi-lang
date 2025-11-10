<?php

namespace TheJano\MultiLang\Contracts;

use Illuminate\Database\Eloquent\Model;

interface TranslationCacheStore
{
    public function get(Model $model): ?array;

    public function put(Model $model, array $payload): void;

    public function forget(Model $model): void;
}
