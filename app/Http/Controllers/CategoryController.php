<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Category\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * カテゴリ管理（CRUD）。
 *
 * 認可は CategoryPolicy 経由。すべて「ログインユーザー自身のカテゴリ」だけを扱う。
 * 階層の保存ロジックは CategoryService に委譲し、コントローラは薄く保つ。
 */
class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    /** 一覧（階層表示は Blade 側で組み立てる）。 */
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        // 親→子→孫の順に並ぶよう depth, sort_order で整列して渡す。
        $categories = Auth::user()->categories()
            ->orderBy('depth')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('categories.index', ['categories' => $categories]);
    }

    /** 新規作成フォーム。 */
    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('categories.create', [
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categories->create(
            $request->user(),
            $request->validated('name'),
            $request->resolveParent(),
        );

        return redirect()
            ->route('categories.index')
            ->with('status', 'カテゴリを作成しました。');
    }

    /** 編集フォーム。 */
    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('categories.edit', [
            'category' => $category,
            // 自分自身・子孫・深すぎる親は選ばせない。
            'parentOptions' => $this->parentOptions($category),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update(
            $category,
            $request->validated('name'),
            $request->resolveParent(),
        );

        return redirect()
            ->route('categories.index')
            ->with('status', 'カテゴリを更新しました。');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $hadChildren = $category->children()->exists();
        $category->delete(); // 子孫は FK の cascade で一緒に消える

        return redirect()
            ->route('categories.index')
            ->with('status', $hadChildren
                ? 'カテゴリと、その中のサブカテゴリを削除しました。'
                : 'カテゴリを削除しました。');
    }

    /**
     * 親カテゴリの選択肢。
     * - 深さが MAX_DEPTH 未満のものだけ（子を持てる余地があるもの）
     * - 編集時は、対象カテゴリ自身とその子孫を除外（循環防止）
     *
     * @return Collection<int, Category>
     */
    private function parentOptions(?Category $editing = null): Collection
    {
        $excludeIds = [];

        if ($editing !== null) {
            $excludeIds = array_merge([$editing->id], $this->categories->descendantIds($editing));
        }

        return Auth::user()->categories()
            ->where('depth', '<', CategoryService::MAX_DEPTH)
            ->when($excludeIds !== [], fn ($q) => $q->whereKeyNot($excludeIds))
            ->orderBy('depth')
            ->orderBy('sort_order')
            ->get();
    }
}
