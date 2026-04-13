<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
        ];
    }
}
