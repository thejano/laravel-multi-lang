<?php

namespace TheJano\MultiLang\Support;

use Illuminate\Database\Eloquent\Builder;

class TranslationScopeGroup
{
    protected array $clauses = [];

    public function where(string $field, mixed ...$parameters): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $parameters) {
            $builder->whereTranslate($field, ...$parameters);
        };

        return $this;
    }

    public function orWhere(string $field, mixed ...$parameters): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $parameters) {
            $builder->orWhereTranslate($field, ...$parameters);
        };

        return $this;
    }

    public function whereNot(string $field, mixed ...$parameters): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $parameters) {
            $builder->whereTranslateNot($field, ...$parameters);
        };

        return $this;
    }

    public function orWhereNot(string $field, mixed ...$parameters): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $parameters) {
            $builder->orWhereTranslateNot($field, ...$parameters);
        };

        return $this;
    }

    public function whereLike(string $field, mixed ...$parameters): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $parameters) {
            $builder->whereTranslateLike($field, ...$parameters);
        };

        return $this;
    }

    public function orWhereLike(string $field, mixed ...$parameters): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $parameters) {
            $builder->orWhereTranslateLike($field, ...$parameters);
        };

        return $this;
    }

    public function whereIn(string $field, array $values, ?string $locale = null): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $values, $locale) {
            $builder->whereTranslateIn($field, $values, $locale);
        };

        return $this;
    }

    public function orWhereIn(string $field, array $values, ?string $locale = null): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $values, $locale) {
            $builder->orWhereTranslateIn($field, $values, $locale);
        };

        return $this;
    }

    public function whereNotIn(string $field, array $values, ?string $locale = null): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $values, $locale) {
            $builder->whereTranslateNotIn($field, $values, $locale);
        };

        return $this;
    }

    public function orWhereNotIn(string $field, array $values, ?string $locale = null): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $values, $locale) {
            $builder->orWhereTranslateNotIn($field, $values, $locale);
        };

        return $this;
    }

    public function whereNull(string $field, ?string $locale = null): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $locale) {
            $builder->whereTranslateNull($field, $locale);
        };

        return $this;
    }

    public function orWhereNull(string $field, ?string $locale = null): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $locale) {
            $builder->orWhereTranslateNull($field, $locale);
        };

        return $this;
    }

    public function whereNotNull(string $field, ?string $locale = null): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $locale) {
            $builder->whereTranslateNotNull($field, $locale);
        };

        return $this;
    }

    public function orWhereNotNull(string $field, ?string $locale = null): self
    {
        $this->clauses[] = function (Builder $builder) use ($field, $locale) {
            $builder->orWhereTranslateNotNull($field, $locale);
        };

        return $this;
    }

    public function whereWithFallback(
        string $field,
        mixed $value,
        ?string $locale = null,
        ?string $fallbackLocale = null,
        bool $includeOriginal = true
    ): self {
        $this->clauses[] = function (Builder $builder) use (
            $field,
            $value,
            $locale,
            $fallbackLocale,
            $includeOriginal
        ) {
            $builder->whereTranslateWithFallback($field, $value, $locale, $fallbackLocale, $includeOriginal);
        };

        return $this;
    }

    public function orWhereWithFallback(
        string $field,
        mixed $value,
        ?string $locale = null,
        ?string $fallbackLocale = null,
        bool $includeOriginal = true
    ): self {
        $this->clauses[] = function (Builder $builder) use (
            $field,
            $value,
            $locale,
            $fallbackLocale,
            $includeOriginal
        ) {
            $builder->orWhereTranslateWithFallback($field, $value, $locale, $fallbackLocale, $includeOriginal);
        };

        return $this;
    }

    public function hasClauses(): bool
    {
        return ! empty($this->clauses);
    }

    public function apply(Builder $builder): void
    {
        foreach ($this->clauses as $clause) {
            $clause($builder);
        }
    }
}
