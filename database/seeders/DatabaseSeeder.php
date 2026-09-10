<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 単一ユーザー運用なので、固定のテストユーザーを1名だけ用意する。
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // サンプルのカテゴリ・タグ・クイズ・問題・ルーブリックを投入する。
        $this->call(QuizContentSeeder::class);
    }
}
