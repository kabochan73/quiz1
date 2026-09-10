<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * criterion_scores: 観点ごとの部分点（question_attempt の採点内訳）。
 *
 * AI は「ルーブリックの観点1つずつ」に点数とコメントを返す。それをこの1行1行に保存する。
 * question_attempts.score は、この awarded_points の合計。
 *
 * criterion_title / max_points は採点時点の値を複製して持つ（スナップショット）。
 * あとからルーブリックを編集しても、過去の採点内訳の意味が変わらないようにするため。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterion_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_attempt_id')->constrained()->cascadeOnDelete();

            // 元になったルーブリック観点。観点が削除されても採点内訳は残す（null に落とす）。
            $table->foreignId('rubric_criterion_id')->nullable()->constrained()->nullOnDelete();

            // 採点時点の観点名（スナップショット）。
            $table->string('criterion_title', 150);

            // 獲得した部分点と、その時点の配点。
            $table->unsignedSmallInteger('awarded_points');
            $table->unsignedSmallInteger('max_points');

            // この観点についての AI のコメント。
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index('question_attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_scores');
    }
};
