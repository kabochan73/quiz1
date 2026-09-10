<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">クイズを編集</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('quizzes.update', $quiz) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="title" value="タイトル" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                      :value="old('title', $quiz->title)" required autofocus />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" value="説明（任意）" />
                        <textarea id="description" name="description" rows="3"
                                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $quiz->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="category_id" value="カテゴリ（任意）" />
                        <select id="category_id" name="category_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">（未分類）</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $quiz->category_id) == $category->id)>
                                    {{ str_repeat('　', $category->depth) }}{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tags" value="タグ（任意・カンマ区切り）" />
                        <x-text-input id="tags" name="tags" type="text" class="mt-1 block w-full"
                                      :value="old('tags', $quiz->tags->pluck('name')->join(', '))" placeholder="頻出, 苦手" />
                        <x-input-error :messages="$errors->get('tags')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>更新</x-primary-button>
                        <a href="{{ route('quizzes.show', $quiz) }}" class="text-sm text-gray-600 hover:underline">キャンセル</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900">このクイズを削除</h3>
                <p class="mt-1 text-xs text-gray-500">問題と受験履歴もすべて削除されます。元に戻せません。</p>
                <form method="POST" action="{{ route('quizzes.destroy', $quiz) }}" class="mt-3"
                      onsubmit="return confirm('「{{ $quiz->title }}」を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>削除する</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
