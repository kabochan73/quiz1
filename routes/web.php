<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // カテゴリ管理（詳細ページは持たないので show は除外）
    Route::resource('categories', CategoryController::class)->except('show');

    // クイズ管理
    Route::resource('quizzes', QuizController::class);
    Route::patch('quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('quizzes.publish');
    Route::patch('quizzes/{quiz}/unpublish', [QuizController::class, 'unpublish'])->name('quizzes.unpublish');

    // 問題管理（クイズ配下。edit/update/destroy は shallow で questions/{question}）
    Route::patch('quizzes/{quiz}/questions/reorder', [QuestionController::class, 'reorder'])->name('quizzes.questions.reorder');
    Route::resource('quizzes.questions', QuestionController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->shallow();
});

require __DIR__.'/auth.php';
