<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * クイズ（問題セット）。このアプリの最上位エンティティ。
 *
 * 1クイズ = 1〜30 問。カテゴリ・タグ・公開フラグはここに付く。
 * question_count / total_max_score は子 questions から算出した非正規化キャッシュで、
 * 問題の追加・削除・編集のたびにアプリ側（サービス層）で更新する。
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $category_id
 * @property string $title
 * @property string|null $description
 * @property int $question_count
 * @property int $total_max_score
 * @property bool $is_published
 * @property Carbon|null $published_at
 */
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    /**
     * Eloquent は "Quiz" を "quizs" と複数形にしてしまうため、テーブル名を明示する。
     *
     * @var string
     */
    protected $table = 'quizzes';

    /** 1クイズに登録できる問題数の下限・上限。公開の条件にも使う。 */
    public const int MIN_QUESTIONS = 1;

    public const int MAX_QUESTIONS = 30;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'question_count',
        'total_max_score',
        'is_published',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * 所有ユーザー。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 分類カテゴリ。未分類なら null。
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 付いているタグ（多対多）。
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * このクイズの問題（出題順）。
     *
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    /**
     * このクイズの受験履歴。
     *
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * 公開できる問題数（MIN_QUESTIONS〜MAX_QUESTIONS）に収まっているか。
     * question_count は QuizCounters が保つ非正規化カラム。
     */
    public function hasPublishableQuestionCount(): bool
    {
        return $this->question_count >= self::MIN_QUESTIONS
            && $this->question_count <= self::MAX_QUESTIONS;
    }
}
