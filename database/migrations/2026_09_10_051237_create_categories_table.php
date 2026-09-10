<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * categories: クイズを分類するカテゴリ。
 *
 * 親子構造（サブカテゴリ）を持てる。方式は「隣接リスト」＝ 自分の親の id を parent_id に持つだけ。
 * 例: 「IT資格」(親なし) > 「基本情報」(親=IT資格) > 「アルゴリズム」(親=基本情報)
 *
 * 階層の深さ制限（最大3階層）や循環禁止、兄弟間の名前重複チェックは
 * アプリ側（FormRequest / サービス層）で担保する。DB では最低限の制約だけ張る。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // 所有者。単一ユーザー運用だが、最初からユーザーでスコープしておく（将来の共有機能で作り直さずに済む）。
            // ユーザー削除時はそのユーザーのカテゴリも全て削除。
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 親カテゴリ。null ならトップレベル。
            // 親が消えたら子孫もまとめて削除（cascade）。※自己参照 FK。
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();

            $table->string('name', 100);

            // URL やクエリで使う識別子。兄弟（同じ parent_id）の中で一意にしたい。
            // ただし PostgreSQL では NULL 同士は「別物」と扱われるため、
            // トップレベル（parent_id = null）同士の重複はこの unique では防げない。
            // → トップレベルの重複チェックはアプリ側バリデーションで行う。
            $table->string('slug', 120);

            // 階層の深さ。0 = トップレベル。保存時に「親の depth + 1」をアプリで計算して入れる。
            // 非正規化。深さ制限の判定や、一覧のインデント表示を JOIN なしで行うために持つ。
            $table->unsignedTinyInteger('depth')->default(0);

            // 同じ親の中での並び順。
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // 「あるユーザーの、ある親の下の、この slug」で一意（主に非トップレベル向け）。
            $table->unique(['user_id', 'parent_id', 'slug']);

            // 一覧取得（ユーザーの、ある親の子を並び順で）用のインデックス。
            $table->index(['user_id', 'parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
