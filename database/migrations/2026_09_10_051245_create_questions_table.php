<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * questions: クイズ内の1問（記述式）。
 *
 * 1問は必ず1クイズに属する（単体では存在しない）。
 *
 * 重要な設計判断: 「模範解答」カラムは持たない。
 *   このアプリは記憶の定着度チェックが目的で、ユーザーが記憶違いの模範解答を登録すると
 *   それを基準に採点され、間違いを強化してしまう。
 *   → 採点の基準は rubric_criteria（観点＋配点）だけ。
 *   → reference_answer は「AI が生成した解答例」で、採点では表現の一例として渡すのみ（点数の根拠にしない）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            // 所属クイズ。クイズ削除で問題も削除。
            // user_id は持たない（question->quiz->user_id で辿る。Policy もクイズ経由でスコープ）。
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();

            // 問題のタイトル。任意。未設定なら画面で「問{sort_order}」と表示する。
            $table->string('title', 200)->nullable();

            // 問題文本体。Markdown 可。
            $table->text('body');

            // --- AI 生成の参考解答（任意）---
            $table->text('reference_answer')->nullable();
            // 参考解答の出所: none=未生成 / ai=AI生成のまま / ai_edited=ユーザーが手直し。
            $table->string('reference_answer_source', 10)->default('none');
            // 生成・再生成・編集のたびに +1。どのバージョンで採点したかを受験側に記録し、点数比較を可能にする。
            $table->unsignedInteger('reference_version')->default(0);

            // 難易度 1〜5。問題単位（1クイズ内に難易度が混在してよい）。
            $table->unsignedTinyInteger('difficulty')->default(3);

            // この問題の満点。ルーブリックの配点合計と一致させる（アプリで検証）。
            $table->unsignedSmallInteger('max_score');

            // クイズ内の出題順。
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // クイズの問題を出題順で引く用。
            $table->index(['quiz_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
