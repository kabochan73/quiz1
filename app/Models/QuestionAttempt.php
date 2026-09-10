<?php

namespace App\Models;

use App\Enums\QuestionAttemptStatus;
use Database\Factories\QuestionAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 受験内の「各問の回答＋採点結果」。
 *
 * score は criterion_scores.awarded_points の合計。
 * max_score / reference_version は採点時点のスナップショット。
 *
 * @property int $id
 * @property int $quiz_attempt_id
 * @property int $question_id
 * @property int|null $grading_run_id
 * @property string $answer_text
 * @property QuestionAttemptStatus $status
 * @property int|null $score
 * @property int $max_score
 * @property int|null $reference_version
 * @property string|null $overall_feedback
 * @property string|null $error_message
 * @property Carbon|null $graded_at
 */
class QuestionAttempt extends Model
{
    /** @use HasFactory<QuestionAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'grading_run_id',
        'answer_text',
        'status',
        'score',
        'max_score',
        'reference_version',
        'overall_feedback',
        'error_message',
        'graded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuestionAttemptStatus::class,
            'graded_at' => 'datetime',
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => QuestionAttemptStatus::Pending->value,
    ];

    /**
     * 所属する受験。
     *
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    /**
     * 回答した問題。
     *
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * この問を最後に採点した run。未採点なら null。
     *
     * @return BelongsTo<GradingRun, $this>
     */
    public function gradingRun(): BelongsTo
    {
        return $this->belongsTo(GradingRun::class);
    }

    /**
     * 観点ごとの採点内訳。
     *
     * @return HasMany<CriterionScore, $this>
     */
    public function criterionScores(): HasMany
    {
        return $this->hasMany(CriterionScore::class);
    }
}
