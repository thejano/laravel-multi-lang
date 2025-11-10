<?php

namespace TheJano\MultiLang\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use TheJano\MultiLang\Traits\Translatable;

class TestPost extends Model
{
    use Translatable;

    protected $fillable = ['title', 'content'];

    protected $table = 'test_posts';

    protected $translatableFields = ['title', 'content'];
}
