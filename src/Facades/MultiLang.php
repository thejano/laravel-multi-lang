<?php

namespace TheJano\MultiLang\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getLocale()
 * @method static void setLocale(string $locale)
 * @method static string getDefaultLocale()
 * @method static string getFallbackLocale()
 * @method static array getSupportedLocales()
 * @method static array getAvailableLocales()
 * @method static array getLocalesWithTranslations()
 * @method static int getTranslationsCount(?string $locale = null)
 * @method static bool hasTranslations(string $locale)
 * @method static array getModelTranslations(\Illuminate\Database\Eloquent\Model $model, ?string $locale = null)
 * @method static bool modelHasTranslation(\Illuminate\Database\Eloquent\Model $model, string $field, ?string $locale = null)
 * @method static string|null getModelTranslation(\Illuminate\Database\Eloquent\Model $model, string $field, ?string $locale = null)
 * @method static string|null getModelTranslationOrOriginal(\Illuminate\Database\Eloquent\Model $model, string $field, ?string $locale = null)
 *
 * @see \TheJano\MultiLang\MultiLang
 */
class MultiLang extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'multi-lang';
    }
}
