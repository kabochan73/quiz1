<?php

namespace App\Http\Requests;

class UpdateQuizRequest extends QuizRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('quiz'));
    }
}
