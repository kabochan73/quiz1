<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * quiz_attempts: 1回の受験。
 *
 * v1 は「1回の提出で全問まとめて送る」（途中保存なし）。
 * 提出時にこのレコードを作り、同時に全問の question_attempts を pending で作る。
 *
 * status の流れ:
 *   grading ──▶ graded            （全問の採点成功）
 *           ├▶ partially_failed   （一部の問だけ採点失敗。その問だけ再採点できる）
 *           └▶ failed             （全問失敗など）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();

            $table->string('status', 20)->default('grading');

            // 全問採点完了後に確定する合計点（= 各 question_attempts.score の合計）。
            $table->unsignedSmallInteger('total_score')->nullable();

            // --- 受験時点のスナップショット ---
            // 後からクイズや問題を編集しても、この受験結果の意味が変わらないように値を複製して保持する。
            $table->unsignedSmallInteger('max_score');       // 受験時の quizzes.total_max_score
            $table->unsignedSmallInteger('question_count');  // 受験時の問題数

            $table->timestamp('submitted_at');            // 提出時刻（実質レコード作成時刻）
            $table->timestamp('graded_at')->nullable();   // 全問の採点が終わった時刻

            $table->timestamps();

            // 履歴一覧（自分の受験を新しい順）用。
            $table->index(['user_id', 'submitted_at']);
            // クイズごとの受験状況の集計用。
            $table->index(['quiz_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
