<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * カテゴリ。クイズを分類する。親子構造（サブカテゴリ）を持てる。
 *
 * 方式は「隣接リスト」= 各行が親の id（parent_id）を持つだけのシンプルな構造。
 * v1 は最大3階層に制限するので、子孫の取得は素の Eloquent（再帰2段）で足りる。
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property int $depth 0 = トップレベル
 * @property int $sort_order
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'slug',
        'depth',
        'sort_order',
    ];

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
     * 親カテゴリ。トップレベルなら null。
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 直下の子カテゴリ（孫は含まない）。
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * このカテゴリに直接ぶら下がるクイズ（子孫カテゴリのクイズは含まない）。
     * 「親カテゴリで絞り込むと子孫のクイズも含める」処理はクエリ側で子孫 id を集めて行う。
     *
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /** トップレベル（親を持たない）カテゴリか。 */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
