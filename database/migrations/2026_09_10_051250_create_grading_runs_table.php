<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * grading_runs: 採点 API の「1コール分」の記録。
 *
 * 採点は最大10問（GRADING_CHUNK_SIZE、既定10）をまとめて1回のAPIコールで行う。
 * 30問のクイズなら 10問 × 3 run に分割してキューで並列実行する。
 *
 * モデル名・プロンプトバージョン・トークン使用量・生レスポンスは
 * 「問単位」ではなく「run 単位」で持つ（問単位だと10問ぶん重複するため。コスト集計も run を SUM すれば済む）。
 *
 * 再採点時は、失敗した問だけを集めて新しい run を作る（1問だけの run になることもある）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();

            // running ──▶ succeeded / failed
            $table->string('status', 20)->default('running');

            // この run でまとめて採点した問数（1〜GRADING_CHUNK_SIZE）。
            $table->unsignedTinyInteger('question_count');

            // 採点に使ったモデルとプロンプトのバージョン（再現性のため必ず記録）。
            $table->string('ai_model', 50);
            $table->string('prompt_version', 20);

            // トークン使用量。コスト集計はこのテーブルを合計する。
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();

            // 構造化レスポンス全体。デバッグや、v2 でのプロンプト改善（同じ回答を新プロンプトで採点し直す）に使う。
            $table->json('ai_raw_response')->nullable();

            // コール失敗時の理由。
            $table->text('error_message')->nullable();

            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index('quiz_attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_runs');
    }
};
