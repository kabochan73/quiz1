<?php

namespace App\Http\Requests;

use App\Models\Quiz;

class StoreQuestionRequest extends QuestionRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        // 問題の追加はクイズの更新権限で判断する。
        return $quiz instanceof Quiz && $this->user()->can('update', $quiz);
    }
}
