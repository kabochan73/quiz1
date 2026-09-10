<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'name' => $name,
            // 日本語名でもぶつからないよう、slug はランダム寄りに生成する。
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'depth' => 0,
            'sort_order' => 0,
        ];
    }

    /**
     * 指定した親カテゴリの子として作る。depth は親 + 1。
     */
    public function childOf(Category $parent): static
    {
        return $this->state(fn () => [
            'user_id' => $parent->user_id,
            'parent_id' => $parent->id,
            'depth' => $parent->depth + 1,
        ]);
    }
}
