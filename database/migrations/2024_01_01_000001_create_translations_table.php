<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 10)->index();
            $table->string('field')->index();
            $table->text('translation')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'translatable_unique');
            $table->index(['translatable_type', 'translatable_id'], 'translations_translatable_index');
            $table->index(['locale', 'field'], 'translations_locale_field_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
