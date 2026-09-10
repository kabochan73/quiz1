<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * quiz_tag: quizzes と tags の多対多を仲介する中間テーブル。
 *
 * Laravel の命名規約に従い「単数形をアルファベット順に _ でつないだ名前」= quiz_tag。
 * タイムスタンプは持たない（付けた日時に意味がないため）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_tag', function (Blueprint $table) {
            // どちらか一方が消えたら、その組み合わせ行も消す。
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            // 同じクイズに同じタグを二重登録させない（複合主キー）。
            $table->primary(['quiz_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_tag');
    }
};
