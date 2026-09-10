<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tags: クイズに付ける自由なラベル。
 *
 * カテゴリと違い「階層なし・フラット」。役割を分ける:
 *   - カテゴリ = 分類の骨格（1クイズに1つ、絞り込みの軸）
 *   - タグ     = 横断的な目印（1クイズに複数、quiz_tag で多対多）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name', 50);
            $table->string('slug', 60);

            $table->timestamps();

            // 同じユーザー内でタグ名（slug）は一意。
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
