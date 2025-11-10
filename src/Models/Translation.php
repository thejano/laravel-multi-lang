<?php

namespace TheJano\MultiLang\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Translation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'translation',
    ];

    protected $casts = [
        'translatable_id' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('multi-lang.translations_table', 'translations'));
    }

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \TheJano\MultiLang\Database\Factories\TranslationFactory::new();
    }
}
