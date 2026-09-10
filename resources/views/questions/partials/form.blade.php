@php
    /**
     * 問題フォームの本体（作成・編集で共有）。
     * 呼び出し側で $formAction, $formMethod, $question(nullable), $submitLabel, $cancelUrl を渡す。
     */
    $question = $question ?? null;

    // Alpine に渡す観点の初期値: 直前の入力 → 既存データ → 空1行
    $initialCriteria = old('criteria', $question?->rubricCriteria
        ->map(fn ($c) => ['title' => $c->title, 'description' => $c->description, 'points' => $c->points])
        ->all()
        ?: [['title' => '', 'description' => '', 'points' => 1]]);
@endphp

<form method="POST" action="{{ $formAction }}" class="space-y-6">
    @csrf
    @if ($formMethod === 'PUT')
        @method('PUT')
    @endif

    <div>
        <x-input-label for="title" value="問題タイトル（任意）" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                      :value="old('title', $question?->title)" placeholder="未入力なら「問1」のように表示されます" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="body" value="問題文" />
        <textarea id="body" name="body" rows="4" required
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body', $question?->body) }}</textarea>
        <x-input-error :messages="$errors->get('body')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="difficulty" value="難易度" />
        <select id="difficulty" name="difficulty"
                class="mt-1 block w-32 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @for ($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}" @selected(old('difficulty', $question?->difficulty ?? 3) == $i)>{{ $i }}</option>
            @endfor
        </select>
        <x-input-error :messages="$errors->get('difficulty')" class="mt-2" />
    </div>

    {{-- ルーブリック（採点の観点）。ここが唯一の採点基準。Alpine で行を増減する。 --}}
    <div x-data="{
            criteria: @js($initialCriteria),
            max: {{ \App\Http\Requests\QuestionRequest::MAX_CRITERIA }},
            get total() { return this.criteria.reduce((s, c) => s + (parseInt(c.points) || 0), 0); },
            add() { if (this.criteria.length < this.max) this.criteria.push({ title: '', description: '', points: 1 }); },
            remove(i) { if (this.criteria.length > 1) this.criteria.splice(i, 1); },
         }">
        <div class="flex items-center justify-between">
            <x-input-label value="採点の観点（1〜10個）" />
            <span class="text-sm text-gray-600">配点合計 = 満点: <span class="font-semibold" x-text="total"></span></span>
        </div>
        <p class="mt-1 text-xs text-gray-500">「思い出してほしい要点」と、その配点を書きます。AIはこの観点だけで採点します。</p>
        <x-input-error :messages="$errors->get('criteria')" class="mt-2" />

        <div class="mt-3 space-y-3">
            <template x-for="(c, i) in criteria" :key="i">
                <div class="border border-gray-200 rounded-md p-3 space-y-2">
                    <div class="flex items-center gap-2">
                        <input type="text" :name="`criteria[${i}][title]`" x-model="c.title"
                               placeholder="観点名（例: 用語の正確さ）"
                               class="flex-1 border-gray-300 rounded-md shadow-sm text-sm" />
                        <input type="number" min="1" max="100" :name="`criteria[${i}][points]`" x-model.number="c.points"
                               class="w-20 border-gray-300 rounded-md shadow-sm text-sm" />
                        <button type="button" @click="remove(i)" x-show="criteria.length > 1"
                                class="text-red-600 text-sm">削除</button>
                    </div>
                    <textarea :name="`criteria[${i}][description]`" x-model="c.description" rows="2"
                              placeholder="何が書けていれば加点するか"
                              class="block w-full border-gray-300 rounded-md shadow-sm text-sm"></textarea>
                </div>
            </template>
        </div>

        <button type="button" @click="add()" x-show="criteria.length < max"
                class="mt-3 text-sm text-indigo-600 hover:underline">＋ 観点を追加</button>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <a href="{{ $cancelUrl }}" class="text-sm text-gray-600 hover:underline">キャンセル</a>
    </div>
</form>
