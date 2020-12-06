<?php

namespace Database\Factories;

use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'name' => $this->faker->regexify('[a-z\d_]{3,15}'),
            'twitter_id' => $this->faker->randomNumber(8),
            'api_token' => \App\Helpers\Helper::generateApiToken(),
        ];
    }
}
