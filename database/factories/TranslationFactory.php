<?php

namespace TheJano\MultiLang\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TheJano\MultiLang\Models\Translation;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\TheJano\MultiLang\Models\Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        return [
            'translatable_type' => $this->faker->randomElement([
                'App\\Models\\Post',
                'App\\Models\\Page',
                'App\\Models\\Content',
            ]),
            'translatable_id' => $this->faker->numberBetween(1, 9999),
            'locale' => $this->faker->randomElement(['en', 'ckb', 'ar']),
            'field' => $this->faker->randomElement(['title', 'subtitle', 'body']),
            'translation' => $this->faker->sentence(),
        ];
    }
}
