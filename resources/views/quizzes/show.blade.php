<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $quiz->title }}</h2>
            <a href="{{ route('quizzes.edit', $quiz) }}" class="text-sm text-indigo-600 hover:underline">編集</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 bg-red-50 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            {{-- メタ情報 --}}
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-3">
                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                    @if ($quiz->is_published)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800">公開中</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">下書き</span>
                    @endif
                    <span>{{ $quiz->category?->name ?? '未分類' }}</span>
                    @if ($quiz->tags->isNotEmpty())
                        <span>#{{ $quiz->tags->pluck('name')->join(' #') }}</span>
                    @endif
                    <span>{{ $quiz->question_count }} 問 / 満点 {{ $quiz->total_max_score }}</span>
                </div>

                @if ($quiz->description)
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $quiz->description }}</p>
                @endif

                {{-- 公開切り替え --}}
                <div class="pt-2">
                    @if ($quiz->is_published)
                        <form method="POST" action="{{ route('quizzes.unpublish', $quiz) }}">
                            @csrf
                            @method('PATCH')
                            <x-secondary-button>非公開に戻す</x-secondary-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('quizzes.publish', $quiz) }}">
                            @csrf
                            @method('PATCH')
                            <x-primary-button>公開する</x-primary-button>
                        </form>
                        <p class="mt-1 text-xs text-gray-500">
                            公開するには問題が {{ \App\Models\Quiz::MIN_QUESTIONS }}〜{{ \App\Models\Quiz::MAX_QUESTIONS }} 問必要です。
                        </p>
                    @endif
                </div>
            </div>

            {{-- 問題一覧（追加・並べ替えは次のステップで実装） --}}
            <div class="bg-white shadow sm:rounded-lg divide-y">
                <div class="p-4 text-sm font-medium text-gray-900">問題</div>
                @forelse ($quiz->questions as $question)
                    <div class="p-4">
                        <div class="text-sm text-gray-800">{{ $question->displayTitle() }}</div>
                        <div class="text-sm text-gray-500 truncate">{{ $question->body }}</div>
                        <div class="mt-1 text-xs text-gray-400">
                            観点 {{ $question->rubricCriteria->count() }} / 配点 {{ $question->max_score }}
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-gray-500 text-sm">まだ問題がありません。</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
