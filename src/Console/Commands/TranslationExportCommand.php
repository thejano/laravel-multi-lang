<?php

namespace TheJano\MultiLang\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslationExportCommand extends Command
{
    protected $signature = 'multi-lang:export
        {model : Fully-qualified model class using the Translatable trait}
        {--path= : Destination file path (defaults to storage/app/{model}_translations.json)}
        {--locales= : Comma-separated list of locales to include}
        {--ids= : Comma-separated list of model IDs to include}
        {--chunk=100 : Number of models to process per chunk}
        {--missing : Export only fields missing translations (uses source text)}';

    protected $description = 'Export model translations to a JSON file.';

    public function handle(): int
    {
        $modelClass = ltrim($this->argument('model'), '\\');

        if (! class_exists($modelClass)) {
            $this->error("Model [{$modelClass}] does not exist.");

            return self::FAILURE;
        }

        $model = new $modelClass;

        if (! $model instanceof Model) {
            $this->error("Class [{$modelClass}] is not an Eloquent model.");

            return self::FAILURE;
        }

        if (! method_exists($model, 'setTranslation')) {
            $this->error("Model [{$modelClass}] does not use the Translatable trait.");

            return self::FAILURE;
        }

        $path = $this->option('path')
            ?? storage_path('app/'.Str::snake(class_basename($modelClass)).'_translations.json');

        $locales = $this->parseCsvOption($this->option('locales'));
        $ids = $this->parseCsvOption($this->option('ids'));
        $chunk = max(1, (int) $this->option('chunk'));

        $missingOnly = (bool) $this->option('missing');

        $data = [];

        $modelClass::query()
            ->when(! empty($ids), function ($query) use ($model, $ids) {
                $query->whereIn($model->getQualifiedKeyName(), $ids);
            })
            ->chunk($chunk, function ($models) use (&$data, $locales, $missingOnly, &$fieldsList) {
                foreach ($models as $instance) {
                    if ($missingOnly) {
                        $availableLocales = $this->determineLocales($locales);
                        $translatableFields = method_exists($instance, 'getTranslatableFields')
                            ? $instance->getTranslatableFields()
                            : [];

                        if (empty($translatableFields)) {
                            continue;
                        }

                        $missingTranslations = [];

                        $instance->loadTranslations($availableLocales);

                        foreach ($availableLocales as $locale) {
                            foreach ($translatableFields as $field) {
                                $value = $instance->translate($field, $locale);

                                if ($value === null || $value === '') {
                                    $source = $instance->getAttribute($field);

                                    if ($source !== null && $source !== '') {
                                        $missingTranslations[$locale][$field] = $source;
                                    }
                                }
                            }
                        }

                        if (! empty($missingTranslations)) {
                            $data[$instance->getKey()] = $missingTranslations;
                        }

                        continue;
                    }

                    if ($locales === null) {
                        $instance->loadTranslations();
                        $translations = $instance->getAllTranslations();
                    } else {
                        $instance->loadTranslations($locales);
                        $translations = [];

                        foreach ($locales as $locale) {
                            $fields = $instance->getTranslations($locale);

                            if (! empty($fields)) {
                                $translations[$locale] = $fields;
                            }
                        }
                    }

                    if (! empty($translations)) {
                        $data[$instance->getKey()] = $translations;
                    }
                }
            });

        File::ensureDirectoryExists(dirname($path));

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info(sprintf(
            'Exported %d record(s) %s%s.',
            count($data),
            $missingOnly ? 'with missing translations ' : '',
            $path
        ));

        return self::SUCCESS;
    }

    protected function parseCsvOption(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $items = array_values(array_filter(array_map('trim', explode(',', $value)), function ($item) {
            return $item !== '';
        }));

        return $items ?: null;
    }

    protected function determineLocales(?array $locales): array
    {
        if ($locales !== null && ! empty($locales)) {
            return $locales;
        }

        $supported = config('app.supported_locales');

        if (is_string($supported)) {
            $supported = preg_split('/[\s,]+/', $supported, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_array($supported) && ! empty($supported)) {
            return array_values(array_unique($supported));
        }

        return [config('app.locale')];
    }
}
