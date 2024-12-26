<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Post;
use App\Models\User;

class PostFactory extends Factory
{
    protected $model = Post::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */

        public function definition()
        {
            return [
                'user_id' => \App\Models\User::factory(), // ユーザーIDを別のファクトリで作成
                'content' => $this->faker->text(),
            ];
        }
}
