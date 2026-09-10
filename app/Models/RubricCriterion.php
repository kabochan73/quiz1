<?php

namespace App\Models;

use Database\Factories\RubricCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 採点基準（ルーブリック）の1観点。
 *
 * このアプリの採点の「唯一の絶対基準」。
 * 「思い出してほしい要点（description）」と「その配点（points）」を持ち、
 * AI はこの観点ごとに部分点をつける。
 *
 * @property int $id
 * @property int $question_id
 * @property string $title
 * @property string $description
 * @property int $points
 * @property int $sort_order
 */
class RubricCriterion extends Model
{
    /** @use HasFactory<RubricCriterionFactory> */
    use HasFactory;

    /**
     * "RubricCriterion" の自動命名は当てにならないため、テーブル名を明示する。
     *
     * @var string
     */
    protected $table = 'rubric_criteria';

    protected $fillable = [
        'question_id',
        'title',
        'description',
        'points',
        'sort_order',
    ];

    /**
     * 所属する問題。
     *
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * この観点に対して付いた採点結果（複数の受験にまたがる）。
     *
     * @return HasMany<CriterionScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(CriterionScore::class);
    }
}
