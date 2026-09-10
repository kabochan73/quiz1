<?php

namespace App\Models;

use Database\Factories\CriterionScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 観点ごとの部分点（question_attempt の採点内訳）。
 *
 * AI が「ルーブリックの観点1つずつ」に返す点数とコメントを1行ずつ保存する。
 * criterion_title / max_points は採点時点の値のスナップショット
 * （あとからルーブリックを編集しても過去の内訳の意味を保つため）。
 *
 * @property int $id
 * @property int $question_attempt_id
 * @property int|null $rubric_criterion_id
 * @property string $criterion_title
 * @property int $awarded_points
 * @property int $max_points
 * @property string|null $comment
 */
class CriterionScore extends Model
{
    /** @use HasFactory<CriterionScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'question_attempt_id',
        'rubric_criterion_id',
        'criterion_title',
        'awarded_points',
        'max_points',
        'comment',
    ];

    /**
     * 所属する問の回答。
     *
     * @return BelongsTo<QuestionAttempt, $this>
     */
    public function questionAttempt(): BelongsTo
    {
        return $this->belongsTo(QuestionAttempt::class);
    }

    /**
     * 元になったルーブリック観点。観点が削除済みなら null。
     *
     * @return BelongsTo<RubricCriterion, $this>
     */
    public function rubricCriterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class);
    }
}
