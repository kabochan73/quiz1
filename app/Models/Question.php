<?php

namespace App\Models;

use App\Enums\ReferenceAnswerSource;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * クイズ内の1問（記述式）。1問は必ず1クイズに属する。
 *
 * 模範解答カラムは持たない（記憶違いの登録で採点が汚染されるのを避けるため）。
 * 採点の基準は rubric_criteria（観点＋配点）のみ。
 * reference_answer は AI 生成の解答例で、採点では「表現の一例」としてしか使わない。
 *
 * @property int $id
 * @property int $quiz_id
 * @property string|null $title
 * @property string $body
 * @property string|null $reference_answer
 * @property ReferenceAnswerSource $reference_answer_source
 * @property int $reference_version
 * @property int $difficulty 1〜5
 * @property int $max_score
 * @property int $sort_order
 */
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'title',
        'body',
        'reference_answer',
        'reference_answer_source',
        'reference_version',
        'difficulty',
        'max_score',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_answer_source' => ReferenceAnswerSource::class,
        ];
    }

    /**
     * 属性のデフォルト値。参考解答は未生成状態から始める。
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'reference_answer_source' => ReferenceAnswerSource::None->value,
        'reference_version' => 0,
        'difficulty' => 3,
    ];

    /**
     * 所属クイズ。
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * 採点基準（ルーブリック）の観点一覧（表示順）。
     *
     * @return HasMany<RubricCriterion, $this>
     */
    public function rubricCriteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('sort_order');
    }

    /**
     * この問題への回答（複数の受験にまたがる）。
     *
     * @return HasMany<QuestionAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }

    /** 画面表示用のタイトル。未設定なら「問N」を返す。 */
    public function displayTitle(): string
    {
        return $this->title ?? "問{$this->sort_order}";
    }
}
