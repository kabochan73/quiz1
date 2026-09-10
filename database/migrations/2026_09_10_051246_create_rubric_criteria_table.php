<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * rubric_criteria: 採点基準（ルーブリック）の1観点。
 *
 * このアプリの採点の「唯一の絶対基準」。1問に複数の観点を持ち、
 * 各観点は「思い出してほしい要点 + その配点」を表す。
 * AI はこの観点ごとに部分点をつける。
 *
 * 制約: 1問あたり 1〜10 件程度。SUM(points) == questions.max_score をアプリで検証。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->id();

            // 所属する問題。問題削除で観点も削除。
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();

            // 観点名。例: 「用語の正確さ」「具体例の提示」。
            $table->string('title', 150);

            // 「何が書けていれば加点か」を具体的に。AI 採点のプロンプトにそのまま渡す。
            $table->text('description');

            // この観点の配点。
            $table->unsignedSmallInteger('points');

            // 表示順。
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_criteria');
    }
};
