<?php

namespace App\Enums;

/**
 * quiz_attempts.status の値。1回の受験全体の採点状況。
 *
 *   Grading ──▶ Graded            全問の採点成功
 *           ├▶ PartiallyFailed    一部の問だけ採点失敗（その問だけ再採点できる）
 *           └▶ Failed             全問失敗など
 */
enum QuizAttemptStatus: string
{
    case Grading = 'grading';
    case Graded = 'graded';
    case PartiallyFailed = 'partially_failed';
    case Failed = 'failed';

    /** 採点が完全に終わり、合計点が確定しているか。 */
    public function isComplete(): bool
    {
        return $this === self::Graded;
    }

    /** 再採点の余地があるか（失敗を含む状態）。 */
    public function hasFailures(): bool
    {
        return $this === self::PartiallyFailed || $this === self::Failed;
    }
}
