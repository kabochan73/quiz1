<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">カテゴリを作成</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('categories.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="カテゴリ名" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="parent_id" value="親カテゴリ（任意）" />
                        <select id="parent_id" name="parent_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">（トップレベル）</option>
                            @foreach ($parentOptions as $option)
                                <option value="{{ $option->id }}" @selected(old('parent_id') == $option->id)>
                                    {{ str_repeat('　', $option->depth) }}{{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">カテゴリは3階層まで作れます。</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>作成</x-primary-button>
                        <a href="{{ route('categories.index') }}" class="text-sm text-gray-600 hover:underline">キャンセル</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
