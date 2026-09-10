<?php

namespace App\Http\Requests;

use App\Models\Quiz;

class StoreQuizRequest extends QuizRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Quiz::class);
    }
}
