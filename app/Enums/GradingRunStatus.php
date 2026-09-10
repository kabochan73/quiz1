<?php

namespace App\Enums;

/**
 * grading_runs.status の値。採点 API 1コールの成否。
 *
 *   Running ──▶ Succeeded / Failed
 */
enum GradingRunStatus: string
{
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
