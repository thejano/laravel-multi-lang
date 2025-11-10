<?php

namespace TheJano\MultiLang\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class TranslationImportCommand extends Command
{
    protected $signature = 'multi-lang:import
        {model : Fully-qualified model class using the Translatable trait}
        {--path= : Source JSON file path}
        {--strategy=merge : Import strategy (merge or replace)}
        {--only-missing : Only apply translations for fields that are currently empty}';

    protected $description = 'Import model translations from a JSON file.';

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

        if (! method_exists($model, 'setTranslationsBatch')) {
            $this->error("Model [{$modelClass}] does not use the Translatable trait.");

            return self::FAILURE;
        }

        $path = $this->option('path')
            ?? storage_path('app/'.class_basename($modelClass).'_translations.json');

        if (! File::exists($path)) {
            $this->error("File [{$path}] does not exist.");

            return self::FAILURE;
        }

        $strategy = strtolower($this->option('strategy') ?? 'merge');

        if (! in_array($strategy, ['merge', 'replace'], true)) {
            $this->error("Invalid strategy [{$strategy}]. Use merge or replace.");

            return self::FAILURE;
        }

        $payload = json_decode(File::get($path), true);

        if (! is_array($payload)) {
            $this->error("File [{$path}] does not contain valid JSON.");

            return self::FAILURE;
        }

        $total = 0;
        $updated = 0;

        $onlyMissing = (bool) $this->option('only-missing');

        foreach ($payload as $id => $locales) {
            $total++;

            $instance = $modelClass::find($id);

            if (! $instance) {
                $this->warn("Skipping missing model [{$modelClass}#{$id}].");

                continue;
            }

            if (! is_array($locales)) {
                $this->warn("Skipping invalid locale payload for [{$modelClass}#{$id}].");

                continue;
            }

            $normalized = [];

            foreach ($locales as $locale => $fields) {
                if (! is_string($locale) || $locale === '' || ! is_array($fields)) {
                    continue;
                }

                $normalized[$locale] = array_filter($fields, static function ($value, $field) {
                    return is_string($field) && $field !== '' && $value !== null;
                }, ARRAY_FILTER_USE_BOTH);
            }

            if (empty($normalized)) {
                continue;
            }

            if ($onlyMissing) {
                $filtered = [];

                foreach ($normalized as $locale => $fields) {
                    foreach ($fields as $field => $value) {
                        $current = $instance->translate($field, $locale);

                        if ($current === null || $current === '') {
                            $filtered[$locale][$field] = $value;
                        }
                    }
                }

                if (! empty($filtered)) {
                    $instance->setTranslationsBatch(
                        $filtered,
                        detachMissing: false
                    );
                    $updated++;
                }

                continue;
            }

            $instance->setTranslationsBatch(
                $normalized,
                detachMissing: $strategy === 'replace'
            );

            $updated++;
        }

        $this->info(sprintf(
            'Processed %d record(s); updated %d model(s) from %s.',
            $total,
            $updated,
            $path
        ));

        return self::SUCCESS;
    }
}
