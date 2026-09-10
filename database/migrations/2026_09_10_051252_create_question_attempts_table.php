<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * question_attempts: 受験内の「各問の回答＋採点結果」。
 *
 * quiz_attempts 1件に対して、そのクイズの問題数ぶんの question_attempts が並ぶ。
 *
 * status の流れ:
 *   pending ──▶ grading ──▶ graded
 *                       └─▶ failed  （所属 grading_run のコールが失敗）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();

            // この問を「最後に」採点した run。再採点で付け替わる。
            // run が消えても回答自体は残す（null に落とす）。
            $table->foreignId('grading_run_id')->nullable()->constrained()->nullOnDelete();

            // 提出された記述回答。
            $table->text('answer_text');

            $table->string('status', 20)->default('pending');

            // 採点後に確定するこの問の得点（= criterion_scores.awarded_points の合計）。
            $table->unsignedSmallInteger('score')->nullable();

            // 採点時点の questions.max_score を複製（スナップショット）。
            $table->unsignedSmallInteger('max_score');

            // 採点時に AI へ渡した参考解答のバージョン。参考解答を使わなかった場合は null。
            $table->unsignedInteger('reference_version')->nullable();

            // その問の総評（AI が返す文章）。
            $table->text('overall_feedback')->nullable();

            // 採点失敗時の理由。
            $table->text('error_message')->nullable();

            $table->timestamp('graded_at')->nullable();

            $table->timestamps();

            // 1回の受験で、同じ問題への回答は1つだけ。
            $table->unique(['quiz_attempt_id', 'question_id']);

            $table->index(['question_id', 'status']);
            $table->index('grading_run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_attempts');
    }
};
