<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Category;
use App\Models\Quiz;
use App\Services\Tag\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * クイズ（問題セット）の CRUD。
 *
 * - 問題そのものの編集は QuestionController（quizzes/{quiz}/questions/...）が担当
 * - 公開/非公開は publish() / unpublish() の専用アクション。
 *   公開は「問題が 1〜30 問あること」が条件（Quiz::hasPublishableQuestionCount）
 * - タグはフォームのカンマ区切り文字列。TagService で Tag に解決して sync する
 */
class QuizController extends Controller
{
    public function __construct(private readonly TagService $tags) {}

    public function index(): View
    {
        $this->authorize('viewAny', Quiz::class);

        $quizzes = Auth::user()->quizzes()
            ->with(['category', 'tags'])
            ->orderByDesc('updated_at')
            ->get();

        return view('quizzes.index', ['quizzes' => $quizzes]);
    }

    public function create(): View
    {
        $this->authorize('create', Quiz::class);

        return view('quizzes.create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreQuizRequest $request): RedirectResponse
    {
        $quiz = Auth::user()->quizzes()->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'category_id' => $request->validated('category_id'),
        ]);

        $quiz->tags()->sync($this->tags->resolveIds($request->user(), $request->tagNames()));

        // 作成直後は問題が0件。続けて問題を追加してもらうため詳細ページへ。
        return redirect()
            ->route('quizzes.show', $quiz)
            ->with('status', 'クイズを作成しました。続けて問題を追加してください。');
    }

    public function show(Quiz $quiz): View
    {
        $this->authorize('view', $quiz);

        $quiz->load(['category', 'tags', 'questions.rubricCriteria']);

        return view('quizzes.show', ['quiz' => $quiz]);
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        $quiz->load('tags');

        return view('quizzes.edit', [
            'quiz' => $quiz,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $quiz->update([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'category_id' => $request->validated('category_id'),
        ]);

        $quiz->tags()->sync($this->tags->resolveIds($request->user(), $request->tagNames()));

        return redirect()
            ->route('quizzes.show', $quiz)
            ->with('status', 'クイズを更新しました。');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        $quiz->delete(); // 問題・受験履歴も FK の cascade で消える

        return redirect()
            ->route('quizzes.index')
            ->with('status', 'クイズを削除しました。');
    }

    /** 公開する（問題が 1〜30 問あるときだけ）。 */
    public function publish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        if (! $quiz->hasPublishableQuestionCount()) {
            return back()->with('error', sprintf(
                '公開するには問題が %d〜%d 問必要です（現在 %d 問）。',
                Quiz::MIN_QUESTIONS,
                Quiz::MAX_QUESTIONS,
                $quiz->question_count,
            ));
        }

        $quiz->update(['is_published' => true, 'published_at' => now()]);

        return back()->with('status', 'クイズを公開しました。');
    }

    /** 非公開に戻す。 */
    public function unpublish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $quiz->update(['is_published' => false, 'published_at' => null]);

        return back()->with('status', 'クイズを非公開にしました。');
    }

    /**
     * カテゴリのセレクト用。全階層を depth 順に並べて返す。
     *
     * @return Collection<int, Category>
     */
    private function categoryOptions(): Collection
    {
        return Auth::user()->categories()
            ->orderBy('depth')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->get();
    }
}
