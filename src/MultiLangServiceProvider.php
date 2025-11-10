<?php

namespace TheJano\MultiLang;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use TheJano\MultiLang\Console\Commands\TranslationAuditCommand;
use TheJano\MultiLang\Console\Commands\TranslationExportCommand;
use TheJano\MultiLang\Console\Commands\TranslationImportCommand;

class MultiLangServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/multi-lang.php', 'multi-lang');

        // Register MultiLang service as singleton
        $this->app->singleton('multi-lang', function ($app) {
            return new MultiLang;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->commands([
                TranslationAuditCommand::class,
                TranslationExportCommand::class,
                TranslationImportCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/multi-lang.php' => config_path('multi-lang.php'),
        ], 'multi-lang-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'multi-lang-migrations');
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // @transModel directive for model translations
        Blade::directive('transModel', function ($expression) {
            return "<?php echo trans_model({$expression}) ?? ''; ?>";
        });

        // @transModelOrOriginal directive for model translations with fallback
        Blade::directive('transModelOrOriginal', function ($expression) {
            return "<?php echo trans_model_or_original({$expression}) ?? ''; ?>";
        });

        // @currentLocale directive to get current locale
        Blade::directive('currentLocale', function () {
            return '<?php echo app()->getLocale(); ?>';
        });
    }
}
