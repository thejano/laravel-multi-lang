<?php

namespace TheJano\MultiLang\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Traits\Translatable;

class TranslationAuditCommand extends Command
{
    protected $signature = 'multi-lang:audit
        {model : Fully-qualified model class using the Translatable trait}
        {--locales= : Comma-separated locales to audit}
        {--fields= : Comma-separated translatable fields to audit}
        {--chunk=100 : Number of models to process per chunk}
        {--detailed : Display individual model identifiers for missing translations}';

    protected $description = 'Audit translation coverage for a translatable model.';

    public function handle(): int
    {
        $modelClass = ltrim($this->argument('model'), '\\');

        if (! class_exists($modelClass)) {
            $this->error("Model [{$modelClass}] does not exist.");

            return self::FAILURE;
        }

        $model = app($modelClass);

        if (! $model instanceof Model) {
            $this->error("Class [{$modelClass}] is not an Eloquent model.");

            return self::FAILURE;
        }

        if (! in_array(Translatable::class, class_uses_recursive($modelClass), true)) {
            $this->error("Model [{$modelClass}] does not use the Translatable trait.");

            return self::FAILURE;
        }

        $fields = $this->resolveFields($model);

        if (empty($fields)) {
            $this->warn("Model [{$modelClass}] does not define any translatable fields.");

            return self::SUCCESS;
        }

        $locales = $this->resolveLocales();

        if (empty($locales)) {
            $this->warn('No locales provided or resolvable for the audit.');

            return self::SUCCESS;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $collectDetails = (bool) $this->option('detailed');

        $missingCounts = [];
        $missingDetails = [];
        $totalProcessed = 0;

        $modelClass::query()
            ->chunk($chunkSize, function ($models) use (
                $locales,
                $fields,
                $collectDetails,
                &$missingCounts,
                &$missingDetails,
                &$totalProcessed
            ) {
                $totalProcessed += $models->count();

                $models->load(['translations' => function ($query) use ($locales) {
                    $query->whereIn('locale', $locales);
                }]);

                foreach ($models as $instance) {
                    $instance->loadTranslations($locales);

                    foreach ($fields as $field) {
                        foreach ($locales as $locale) {
                            $value = $instance->translate($field, $locale);

                            if ($value === null || $value === '') {
                                $missingCounts[$locale][$field] = ($missingCounts[$locale][$field] ?? 0) + 1;

                                if ($collectDetails) {
                                    $missingDetails[] = [
                                        'Model ID' => $instance->getKey(),
                                        'Locale' => $locale,
                                        'Field' => $field,
                                    ];
                                }
                            }
                        }
                    }
                }
            });

        if (empty($missingCounts)) {
            $this->info(sprintf(
                'All translations present for %d field(s) across %d locale(s). %d record(s) audited.',
                count($fields),
                count($locales),
                $totalProcessed
            ));

            return self::SUCCESS;
        }

        $this->warn('Missing translations detected:');

        $summaryRows = [];

        ksort($missingCounts);

        foreach ($missingCounts as $locale => $fieldsCount) {
            ksort($fieldsCount);

            foreach ($fieldsCount as $field => $count) {
                $summaryRows[] = [
                    'Locale' => $locale,
                    'Field' => $field,
                    'Missing Records' => $count,
                ];
            }
        }

        $this->table(['Locale', 'Field', 'Missing Records'], $summaryRows);

        if ($collectDetails && ! empty($missingDetails)) {
            $this->newLine();
            $this->table(['Model ID', 'Locale', 'Field'], $missingDetails);
        }

        return self::FAILURE;
    }

    protected function resolveLocales(): array
    {
        $option = $this->option('locales');

        if (is_string($option) && $option !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $option))));
        }

        $supported = config('app.supported_locales', []);

        if (! empty($supported)) {
            return array_values(array_filter($supported));
        }

        $locale = config('app.locale');
        $fallback = config('app.fallback_locale');

        return array_values(array_filter(array_unique([$locale, $fallback])));
    }

    protected function resolveFields(object $model): array
    {
        $option = $this->option('fields');

        if (is_string($option) && $option !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $option))));
        }

        if (method_exists($model, 'getTranslatableFields')) {
            return array_values(array_filter($model->getTranslatableFields()));
        }

        return [];
    }
}
