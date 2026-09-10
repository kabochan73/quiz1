<?php

namespace App\Models;

use App\Enums\GradingRunStatus;
use Database\Factories\GradingRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 採点 API の「1コール分」の記録。
 *
 * 最大10問（GRADING_CHUNK_SIZE）をまとめて1回のAPIコールで採点する。
 * モデル名・プロンプトバージョン・トークン・生レスポンスはここに集約する
 * （問単位に持つと重複するし、コスト集計は run を SUM すれば済む）。
 *
 * @property int $id
 * @property int $quiz_attempt_id
 * @property GradingRunStatus $status
 * @property int $question_count
 * @property string $ai_model
 * @property string $prompt_version
 * @property int|null $input_tokens
 * @property int|null $output_tokens
 * @property array<string, mixed>|null $ai_raw_response
 * @property string|null $error_message
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 */
class GradingRun extends Model
{
    /** @use HasFactory<GradingRunFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'status',
        'question_count',
        'ai_model',
        'prompt_version',
        'input_tokens',
        'output_tokens',
        'ai_raw_response',
        'error_message',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GradingRunStatus::class,
            'ai_raw_response' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * 対象の受験。
     *
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    /**
     * この run で採点された各問の回答。
     *
     * @return HasMany<QuestionAttempt, $this>
     */
    public function questionAttempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }
}
