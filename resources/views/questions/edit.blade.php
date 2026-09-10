<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            問題を編集 <span class="text-gray-400 text-base">— {{ $question->quiz->title }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                @include('questions.partials.form', [
                    'formAction' => route('questions.update', $question),
                    'formMethod' => 'PUT',
                    'question' => $question,
                    'submitLabel' => '更新',
                    'cancelUrl' => route('quizzes.show', $question->quiz),
                ])
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900">この問題を削除</h3>
                <form method="POST" action="{{ route('questions.destroy', $question) }}" class="mt-3"
                      onsubmit="return confirm('この問題を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>削除する</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
