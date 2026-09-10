<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            問題を追加 <span class="text-gray-400 text-base">— {{ $quiz->title }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                @include('questions.partials.form', [
                    'formAction' => route('quizzes.questions.store', $quiz),
                    'formMethod' => 'POST',
                    'question' => null,
                    'submitLabel' => '追加',
                    'cancelUrl' => route('quizzes.show', $quiz),
                ])
            </div>
        </div>
    </div>
</x-app-layout>
