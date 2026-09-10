<?php

namespace App\Http\Requests;

use App\Models\Question;

class UpdateQuestionRequest extends QuestionRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        // shallow なネストルートなので {question} だけが来る。所属クイズの更新権限で判断する。
        return $question instanceof Question && $this->user()->can('update', $question->quiz);
    }
}
