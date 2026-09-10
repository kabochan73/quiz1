<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">カテゴリ</h2>
            <a href="{{ route('categories.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                新規作成
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- 直前の操作結果メッセージ --}}
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg divide-y">
                @forelse ($categories as $category)
                    <div class="flex items-center justify-between p-4">
                        {{-- depth ぶん字下げして階層を表現する --}}
                        <span style="padding-left: {{ $category->depth * 1.5 }}rem">
                            @if ($category->depth > 0)
                                <span class="text-gray-400">└ </span>
                            @endif
                            {{ $category->name }}
                        </span>

                        <div class="flex items-center gap-3 text-sm">
                            <a href="{{ route('categories.edit', $category) }}" class="text-indigo-600 hover:underline">編集</a>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                  onsubmit="return confirm('「{{ $category->name }}」を削除しますか？ サブカテゴリがあれば一緒に削除されます。')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">削除</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-gray-500 text-sm">まだカテゴリがありません。</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
