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

                <div class="pt-2">
                    @if ($quiz->is_published)
                        <form method="POST" action="{{ route('quizzes.unpublish', $quiz) }}">
                            @csrf @method('PATCH')
                            <x-secondary-button>非公開に戻す</x-secondary-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('quizzes.publish', $quiz) }}">
                            @csrf @method('PATCH')
                            <x-primary-button>公開する</x-primary-button>
                        </form>
                        <p class="mt-1 text-xs text-gray-500">
                            公開するには問題が {{ \App\Models\Quiz::MIN_QUESTIONS }}〜{{ \App\Models\Quiz::MAX_QUESTIONS }} 問必要です。
                        </p>
                    @endif
                </div>
            </div>

            {{-- 問題一覧 + 管理 --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="flex items-center justify-between p-4 border-b">
                    <span class="text-sm font-medium text-gray-900">問題（{{ $quiz->question_count }} / {{ \App\Models\Quiz::MAX_QUESTIONS }}）</span>
                    @if ($quiz->question_count < \App\Models\Quiz::MAX_QUESTIONS)
                        <a href="{{ route('quizzes.questions.create', $quiz) }}" class="text-sm text-indigo-600 hover:underline">＋ 問題を追加</a>
                    @endif
                </div>

                @if ($quiz->questions->isEmpty())
                    <div class="p-4 text-gray-500 text-sm">まだ問題がありません。</div>
                @else
                    {{-- ↑↓ で並べ替え、変更があれば「順番を保存」を表示 --}}
                    <div x-data="{
                            items: @js($quiz->questions->map(fn ($q) => ['id' => $q->id, 'label' => $q->displayTitle()])->values()),
                            original: @js($quiz->questions->pluck('id')->values()),
                            get changed() { return JSON.stringify(this.items.map(i => i.id)) !== JSON.stringify(this.original); },
                            move(from, to) {
                                if (to < 0 || to >= this.items.length) return;
                                const moved = this.items.splice(from, 1)[0];
                                this.items.splice(to, 0, moved);
                            },
                         }">
                        <ul class="divide-y">
                            <template x-for="(item, i) in items" :key="item.id">
                                <li class="flex items-center justify-between p-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex flex-col">
                                            <button type="button" @click="move(i, i - 1)" class="text-gray-400 hover:text-gray-700 text-xs leading-none">▲</button>
                                            <button type="button" @click="move(i, i + 1)" class="text-gray-400 hover:text-gray-700 text-xs leading-none">▼</button>
                                        </div>
                                        <span class="text-sm text-gray-800" x-text="`${i + 1}. ${item.label}`"></span>
                                    </div>
                                    <a :href="`/questions/${item.id}/edit`" class="text-sm text-indigo-600 hover:underline">編集</a>
                                </li>
                            </template>
                        </ul>

                        <div class="p-4 border-t" x-show="changed" style="display: none;">
                            <form method="POST" action="{{ route('quizzes.questions.reorder', $quiz) }}">
                                @csrf @method('PATCH')
                                <template x-for="item in items" :key="item.id">
                                    <input type="hidden" name="ids[]" :value="item.id">
                                </template>
                                <x-primary-button>順番を保存</x-primary-button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
