<?php

namespace App\Services\Quiz;

use App\Models\Quiz;

/**
 * クイズの非正規化カラム（question_count / total_max_score）を、
 * 子 questions の実データから再計算して保存するサービス。
 *
 * これらのカラムは「毎回 COUNT / SUM しなくて済むようにキャッシュした値」なので、
 * 中身がズレないよう、次のタイミングで必ず sync() を呼ぶ:
 *   - 問題を追加した / 削除した
 *   - 問題の max_score を変更した（＝ルーブリックの配点を編集した）
 *
 * v1 では「呼ぶ場所」をコントローラ／シーダーに明示する方針
 * （モデルイベントで暗黙に走らせると、シードやテストで挙動が読みにくくなるため）。
 */
class QuizCounters
{
    /**
     * 1件のクイズのカウンタを再計算して保存する。
     *
     * 集計は questions テーブルへの COUNT / SUM で行い、
     * 全問をメモリに読み込まない。
     */
    public function sync(Quiz $quiz): Quiz
    {
        $questions = $quiz->questions();

        $quiz->update([
            'question_count' => $questions->count(),
            // 問題が0件のときは sum() が null を返すので 0 に寄せる。
            'total_max_score' => (int) $questions->sum('max_score'),
        ]);

        return $quiz;
    }
}
