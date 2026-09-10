<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\Question\QuestionWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 問題の CRUD。クイズ配下のネストしたリソース。
 *
 * - 一覧・詳細ページは持たない（問題はクイズ詳細ページ quizzes.show にまとめて表示する）
 * - create/store は quizzes/{quiz}/questions（クイズを親に取る）
 * - edit/update/destroy は shallow ルートで questions/{question}
 * - 認可はすべて「所属クイズの update 権限」で判断する
 * - 書き込みは QuestionWriter に委譲（観点の入れ替え・カウンタ再計算・公開解除をまとめて行う）
 */
class QuestionController extends Controller
{
    public function __construct(private readonly QuestionWriter $writer) {}

    public function create(Quiz $quiz): View|RedirectResponse
    {
        $this->authorize('update', $quiz);

        if ($quiz->question_count >= Quiz::MAX_QUESTIONS) {
            return redirect()
                ->route('quizzes.show', $quiz)
                ->with('error', '問題は1クイズ '.Quiz::MAX_QUESTIONS.' 問までです。');
        }

        return view('questions.create', ['quiz' => $quiz]);
    }

    public function store(StoreQuestionRequest $request, Quiz $quiz): RedirectResponse
    {
        // フォーム表示後に上限へ達したケースを弾く。
        if ($quiz->question_count >= Quiz::MAX_QUESTIONS) {
            return redirect()
                ->route('quizzes.show', $quiz)
                ->with('error', '問題数が上限に達しています。');
        }

        $this->writer->create($quiz, $this->payload($request));

        return redirect()
            ->route('quizzes.show', $quiz)
            ->with('status', '問題を追加しました。');
    }

    public function edit(Question $question): View
    {
        $this->authorize('update', $question->quiz);

        $question->load('rubricCriteria');

        return view('questions.edit', ['question' => $question]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->writer->update($question, $this->payload($request));

        return redirect()
            ->route('quizzes.show', $question->quiz)
            ->with('status', '問題を更新しました。');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('update', $question->quiz);

        $quiz = $question->quiz;
        $this->writer->delete($question);

        return redirect()
            ->route('quizzes.show', $quiz)
            ->with('status', '問題を削除しました。');
    }

    /**
     * クイズ内の問題を並べ替える。
     * リクエスト body の ids（問題 id の配列）が新しい順序。
     */
    public function reorder(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $ownIds = $quiz->questions()->pluck('id')->all();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'in:'.implode(',', $ownIds ?: [0])],
        ]);

        foreach (array_values($validated['ids']) as $index => $id) {
            Question::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return redirect()
            ->route('quizzes.show', $quiz)
            ->with('status', '問題の順番を変更しました。');
    }

    /**
     * FormRequest から QuestionWriter へ渡すデータを組み立てる。
     *
     * @return array{title: ?string, body: string, difficulty: int, max_score: int, criteria: list<array{title: string, description: string, points: int, sort_order: int}>}
     */
    private function payload(StoreQuestionRequest|UpdateQuestionRequest $request): array
    {
        return [
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'difficulty' => (int) $request->validated('difficulty'),
            'max_score' => $request->criteriaTotalPoints(),
            'criteria' => $request->criteria(),
        ];
    }
}
