<?php

namespace App\Models;

use App\Enums\QuizAttemptStatus;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 1回の受験。
 *
 * v1 は「1回の提出で全問まとめて送る」。提出時にこのレコードと、
 * そのクイズの問題数ぶんの question_attempts（pending）を同時に作る。
 *
 * max_score / question_count は受験時点のスナップショット
 * （後からクイズを編集しても過去の結果の意味を保つため）。
 *
 * @property int $id
 * @property int $user_id
 * @property int $quiz_id
 * @property QuizAttemptStatus $status
 * @property int|null $total_score
 * @property int $max_score
 * @property int $question_count
 * @property Carbon $submitted_at
 * @property Carbon|null $graded_at
 */
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'status',
        'total_score',
        'max_score',
        'question_count',
        'submitted_at',
        'graded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuizAttemptStatus::class,
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    /**
     * 受験したユーザー。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 受験対象のクイズ。
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * 各問の回答＋採点結果。
     *
     * @return HasMany<QuestionAttempt, $this>
     */
    public function questionAttempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }

    /**
     * この受験の採点コール（チャンク）の記録。
     *
     * @return HasMany<GradingRun, $this>
     */
    public function gradingRuns(): HasMany
    {
        return $this->hasMany(GradingRun::class);
    }
}
