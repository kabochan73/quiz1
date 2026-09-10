<?php

namespace App\Enums;

/**
 * question_attempts.status の値。各問ごとの採点状況。
 *
 *   Pending ──▶ Grading ──▶ Graded
 *                       └─▶ Failed   （所属 grading_run のコールが失敗）
 */
enum QuestionAttemptStatus: string
{
    case Pending = 'pending';
    case Grading = 'grading';
    case Graded = 'graded';
    case Failed = 'failed';

    /** これ以上状態が変わらない（採点済み or 失敗）か。 */
    public function isFinished(): bool
    {
        return $this === self::Graded || $this === self::Failed;
    }
}
