<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">クイズ</h2>
            <a href="{{ route('quizzes.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                新規作成
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg divide-y">
                @forelse ($quizzes as $quiz)
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('quizzes.show', $quiz) }}" class="font-medium text-indigo-600 hover:underline">
                                {{ $quiz->title }}
                            </a>
                            @if ($quiz->is_published)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800">公開中</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">下書き</span>
                            @endif
                        </div>
                        <div class="mt-1 text-sm text-gray-500 flex flex-wrap gap-x-3 gap-y-1">
                            <span>{{ $quiz->question_count }} 問</span>
                            <span>{{ $quiz->category?->name ?? '未分類' }}</span>
                            @if ($quiz->tags->isNotEmpty())
                                <span>#{{ $quiz->tags->pluck('name')->join(' #') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-gray-500 text-sm">まだクイズがありません。</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
