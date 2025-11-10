<?php

namespace TheJano\MultiLang\Traits\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use TheJano\MultiLang\Support\TranslationScopeGroup;

trait HandlesTranslationScopes
{
    public function scopeWithTranslations($query, string|array|null $locales = null)
    {
        if ($locales === null) {
            return $query->with('translations');
        }

        if (! is_array($locales)) {
            $locales = [$locales];
        }

        $locales = array_values(array_unique(array_filter($locales, static function ($locale) {
            return $locale !== null && $locale !== '';
        })));

        if (empty($locales)) {
            $locales = [App::getLocale()];
        }

        return $query->with(['translations' => function ($query) use ($locales) {
            $query->whereIn('locale', $locales);
        }]);
    }

    public function scopeWithAllTranslations($query)
    {
        return $query->with('translations');
    }

    public function scopeWhereTranslate(
        Builder $query,
        string $field,
        mixed ...$parameters
    ) {
        [$operator, $value, $locale] = $this->prepareTranslateComparisonParameters($parameters);

        return $this->applyTranslateComparison($query, $field, $operator, $value, $locale);
    }

    public function scopeOrWhereTranslate(
        Builder $query,
        string $field,
        mixed ...$parameters
    ) {
        [$operator, $value, $locale] = $this->prepareTranslateComparisonParameters($parameters);

        return $this->applyTranslateComparison($query, $field, $operator, $value, $locale, true);
    }

    public function scopeWhereTranslateLike(
        Builder $query,
        string $field,
        string $value,
        ?string $locale = null
    ) {
        return $this->applyTranslateComparison($query, $field, 'like', $value, $locale);
    }

    public function scopeOrWhereTranslateLike(
        Builder $query,
        string $field,
        string $value,
        ?string $locale = null
    ) {
        return $this->applyTranslateComparison($query, $field, 'like', $value, $locale, true);
    }

    public function scopeWhereTranslateNot(
        Builder $query,
        string $field,
        mixed ...$parameters
    ) {
        [$operator, $value, $locale] = $this->prepareTranslateComparisonParameters($parameters);

        return $this->applyTranslateNegatedComparison($query, $field, $operator, $value, $locale);
    }

    public function scopeOrWhereTranslateNot(
        Builder $query,
        string $field,
        mixed ...$parameters
    ) {
        [$operator, $value, $locale] = $this->prepareTranslateComparisonParameters($parameters);

        return $this->applyTranslateNegatedComparison($query, $field, $operator, $value, $locale, true);
    }

    public function scopeWhereTranslateIn(
        Builder $query,
        string $field,
        array $values,
        ?string $locale = null
    ) {
        if (empty($values)) {
            throw new InvalidArgumentException('Values array for whereTranslateIn cannot be empty.');
        }

        return $this->applyTranslateRelationConstraint(
            $query,
            'whereHas',
            $field,
            $locale,
            fn (Builder $translationQuery) => $translationQuery->whereIn('translation', $values)
        );
    }

    public function scopeOrWhereTranslateIn(
        Builder $query,
        string $field,
        array $values,
        ?string $locale = null
    ) {
        if (empty($values)) {
            throw new InvalidArgumentException('Values array for orWhereTranslateIn cannot be empty.');
        }

        return $this->applyTranslateRelationConstraint(
            $query,
            'orWhereHas',
            $field,
            $locale,
            fn (Builder $translationQuery) => $translationQuery->whereIn('translation', $values)
        );
    }

    public function scopeWhereTranslateNotIn(
        Builder $query,
        string $field,
        array $values,
        ?string $locale = null
    ) {
        if (empty($values)) {
            throw new InvalidArgumentException('Values array for whereTranslateNotIn cannot be empty.');
        }

        return $this->applyTranslateRelationConstraint(
            $query,
            'whereHas',
            $field,
            $locale,
            fn (Builder $translationQuery) => $translationQuery->whereNotIn('translation', $values)
        );
    }

    public function scopeOrWhereTranslateNotIn(
        Builder $query,
        string $field,
        array $values,
        ?string $locale = null
    ) {
        if (empty($values)) {
            throw new InvalidArgumentException('Values array for orWhereTranslateNotIn cannot be empty.');
        }

        return $this->applyTranslateRelationConstraint(
            $query,
            'orWhereHas',
            $field,
            $locale,
            fn (Builder $translationQuery) => $translationQuery->whereNotIn('translation', $values)
        );
    }

    public function scopeWhereTranslateNull(
        Builder $query,
        string $field,
        ?string $locale = null
    ) {
        return $this->applyTranslateNullConstraint($query, $field, $locale);
    }

    public function scopeOrWhereTranslateNull(
        Builder $query,
        string $field,
        ?string $locale = null
    ) {
        return $this->applyTranslateNullConstraint($query, $field, $locale, true);
    }

    public function scopeWhereTranslateNotNull(
        Builder $query,
        string $field,
        ?string $locale = null
    ) {
        return $this->applyTranslateRelationConstraint(
            $query,
            'whereHas',
            $field,
            $locale,
            fn (Builder $translationQuery) => $translationQuery->whereNotNull('translation')
        );
    }

    public function scopeOrWhereTranslateNotNull(
        Builder $query,
        string $field,
        ?string $locale = null
    ) {
        return $this->applyTranslateRelationConstraint(
            $query,
            'orWhereHas',
            $field,
            $locale,
            fn (Builder $translationQuery) => $translationQuery->whereNotNull('translation')
        );
    }

    public function scopeWhereTranslateWithFallback(
        Builder $query,
        string $field,
        mixed $value,
        ?string $locale = null,
        ?string $fallbackLocale = null,
        bool $includeOriginal = true
    ) {
        return $this->applyTranslateWithFallbackScope(
            $query,
            $field,
            $value,
            $locale,
            $fallbackLocale,
            $includeOriginal
        );
    }

    public function scopeOrWhereTranslateWithFallback(
        Builder $query,
        string $field,
        mixed $value,
        ?string $locale = null,
        ?string $fallbackLocale = null,
        bool $includeOriginal = true
    ) {
        return $this->applyTranslateWithFallbackScope(
            $query,
            $field,
            $value,
            $locale,
            $fallbackLocale,
            $includeOriginal,
            true
        );
    }

    public function scopeWhereTranslateGroup(Builder $query, callable $callback)
    {
        return $this->applyTranslateGroupScope($query, $callback);
    }

    public function scopeOrWhereTranslateGroup(Builder $query, callable $callback)
    {
        return $this->applyTranslateGroupScope($query, $callback, true);
    }

    protected function applyTranslateRelationConstraint(
        Builder $query,
        string $method,
        string $field,
        ?string $locale,
        Closure $callback
    ) {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();

        return $query->{$method}('translations', function (Builder $translationQuery) use (
            $field,
            $locale,
            $callback
        ) {
            $translationQuery->where('field', $field);

            if ($locale !== '*') {
                $translationQuery->where('locale', $locale);
            }

            $callback($translationQuery);
        });
    }

    protected function applyTranslateComparison(
        Builder $query,
        string $field,
        string $operator,
        mixed $value,
        ?string $locale,
        bool $isOr = false
    ) {
        return $this->applyTranslateRelationConstraint(
            $query,
            $isOr ? 'orWhereHas' : 'whereHas',
            $field,
            $locale,
            fn (Builder $translationQuery) => $translationQuery->where('translation', $operator, $value)
        );
    }

    protected function applyTranslateNegatedComparison(
        Builder $query,
        string $field,
        string $operator,
        mixed $value,
        ?string $locale,
        bool $isOr = false
    ) {
        return $this->applyTranslateRelationConstraint(
            $query,
            $isOr ? 'orWhereDoesntHave' : 'whereDoesntHave',
            $field,
            $locale,
            fn (Builder $translationQuery) => $translationQuery->where('translation', $operator, $value)
        );
    }

    protected function applyTranslateNullConstraint(
        Builder $query,
        string $field,
        ?string $locale,
        bool $isOr = false
    ) {
        $method = $isOr ? 'orWhere' : 'where';

        return $query->{$method}(function (Builder $subQuery) use ($field, $locale) {
            $subQuery->whereDoesntHave('translations', function (Builder $translationQuery) use ($field, $locale) {
                $translationQuery->where('field', $field);

                if ($locale !== '*' && $locale !== null) {
                    $translationQuery->where('locale', $locale);
                }
            });

            $subQuery->orWhereHas('translations', function (Builder $translationQuery) use ($field, $locale) {
                $translationQuery->where('field', $field);

                if ($locale !== '*' && $locale !== null) {
                    $translationQuery->where('locale', $locale);
                }

                $translationQuery->whereNull('translation');
            });
        });
    }

    protected function applyTranslateWithFallbackScope(
        Builder $query,
        string $field,
        mixed $value,
        ?string $locale,
        ?string $fallbackLocale,
        bool $includeOriginal,
        bool $isOr = false
    ) {
        $this->ensureFieldIsTranslatable($field);

        $locale = $locale ?? App::getLocale();
        $fallbackLocale = $fallbackLocale ?? config('app.fallback_locale', config('app.locale', 'en'));
        $column = $query->getModel()->qualifyColumn($field);

        $method = $isOr ? 'orWhere' : 'where';

        return $query->{$method}(function (Builder $builder) use (
            $field,
            $value,
            $locale,
            $fallbackLocale,
            $includeOriginal,
            $column
        ) {
            $builder->whereTranslate($field, $value, $locale);

            if ($fallbackLocale !== null && $fallbackLocale !== '' && $fallbackLocale !== $locale) {
                $builder->orWhereTranslate($field, $value, $fallbackLocale);
            }

            if ($includeOriginal) {
                $builder->orWhere($column, '=', $value);
            }
        });
    }

    protected function applyTranslateGroupScope(
        Builder $query,
        callable $callback,
        bool $isOr = false
    ) {
        $group = new TranslationScopeGroup;
        $callback($group);

        if (! $group->hasClauses()) {
            return $query;
        }

        $method = $isOr ? 'orWhere' : 'where';

        return $query->{$method}(function (Builder $builder) use ($group) {
            $group->apply($builder);
        });
    }

    protected function prepareTranslateComparisonParameters(array $parameters): array
    {
        $count = count($parameters);

        if ($count === 0) {
            throw new InvalidArgumentException('whereTranslate requires at least a value.');
        }

        if ($count > 3) {
            throw new InvalidArgumentException('whereTranslate accepts at most three arguments after the field.');
        }

        $operator = '=';
        $value = null;
        $locale = null;

        if ($count === 1) {
            $value = $parameters[0];
        } elseif ($count === 2) {
            if ($this->isTranslateOperator($parameters[0])) {
                $operator = $this->normalizeTranslateOperator($parameters[0]);
                $value = $parameters[1];
            } else {
                $value = $parameters[0];
                $locale = $parameters[1];
            }
        } elseif ($count === 3) {
            if (! $this->isTranslateOperator($parameters[0])) {
                throw new InvalidArgumentException('Invalid operator provided to whereTranslate.');
            }

            $operator = $this->normalizeTranslateOperator($parameters[0]);
            $value = $parameters[1];
            $locale = $parameters[2];
        }

        if ($value === null) {
            throw new InvalidArgumentException('whereTranslate requires a non-null value.');
        }

        return [$operator, $value, $locale];
    }

    protected function normalizeTranslateOperator(string $operator): string
    {
        $trimmed = trim($operator);
        $lower = strtolower($trimmed);

        return match ($lower) {
            '==', '===' => '=',
            '!==', '!=' => '!=',
            'like', 'not like', 'ilike', 'not ilike', 'rlike', 'not rlike', 'regexp', 'not regexp', 'like binary' => $lower,
            default => $trimmed,
        };
    }

    protected function isTranslateOperator(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);
        $lower = strtolower($trimmed);

        $basicOperators = ['=', '==', '===', '!=', '!==', '<>', '>', '>=', '<', '<='];
        $stringOperators = [
            'like',
            'not like',
            'ilike',
            'not ilike',
            'rlike',
            'not rlike',
            'regexp',
            'not regexp',
            'like binary',
        ];

        return in_array($trimmed, $basicOperators, true) || in_array($lower, $stringOperators, true);
    }
}
