<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * quizzes: 問題セット。このアプリの最上位エンティティ。
 *
 * カテゴリ・タグ・公開フラグはすべてクイズに付く。受験・採点・履歴・成績もクイズ単位。
 * 1クイズは 1〜30 問（questions テーブル）で構成する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 分類カテゴリ。任意。どの階層のカテゴリでも指定してよい（末端に限定しない）。
            // カテゴリが消えても、そのクイズ自体は残す（category_id を null に落とすだけ）。
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 200);

            // 出題範囲や狙いのメモ。Markdown 可。
            $table->text('description')->nullable();

            // --- 非正規化カラム（子 questions から算出してキャッシュ）---
            // 毎回 COUNT / SUM しなくて済むように、問題の追加・削除・編集のたびにアプリで更新する。

            // 問題数。1〜30 の範囲チェックに使う。
            $table->unsignedSmallInteger('question_count')->default(0);
            // 配点合計（= 各 question.max_score の合計）。クイズ満点。
            $table->unsignedSmallInteger('total_max_score')->default(0);

            // 公開状態。公開クイズだけが受験対象。公開には「問題 1〜30 問」が必要（アプリで検証）。
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // 一覧（自分の公開クイズ）用。
            $table->index(['user_id', 'is_published']);
            // カテゴリ絞り込み用。
            $table->index(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
