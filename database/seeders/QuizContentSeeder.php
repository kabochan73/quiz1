<?php

namespace Database\Seeders;

use App\Enums\ReferenceAnswerSource;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Tag;
use App\Models\User;
use App\Services\Quiz\QuizCounters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * サンプルのカテゴリ・タグ・クイズ・問題・ルーブリックを投入する。
 *
 * 動作確認用に、境界値をわざと含める:
 *   - 1問だけのクイズ
 *   - 5問前後のクイズ
 *   - 11問のクイズ（採点が 10問チャンク × 2 に分かれる境界）
 *
 * 受験系（quiz_attempts など）はシードしない（手動で挙動を確認するため）。
 */
class QuizContentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrFail();

        $categories = $this->seedCategories($user);
        $tags = $this->seedTags($user);

        // --- クイズ1: 1問だけ（境界） ---
        $this->makeQuiz($user, $categories['アルゴリズム'], [$tags['頻出']], 'アルゴリズムの基礎', [
            [
                'body' => '「計算量オーダー（ビッグO記法）」とは何かを、目的にも触れて説明してください。',
                'criteria' => [
                    ['title' => '定義', 'description' => '入力サイズの増加に対する処理時間（または空間）の増え方を表す、という趣旨がある', 'points' => 5],
                    ['title' => '目的', 'description' => 'アルゴリズムの効率を実装に依らず比較するため、という趣旨がある', 'points' => 3],
                    ['title' => '具体例', 'description' => 'O(1) / O(n) / O(n^2) など具体的なオーダーに言及している', 'points' => 2],
                ],
            ],
        ]);

        // --- クイズ2: 4問 ---
        $this->makeQuiz($user, $categories['データベース'], [$tags['頻出'], $tags['要復習']], 'SQL 基本操作', [
            [
                'body' => 'SQL の INNER JOIN と LEFT OUTER JOIN の違いを説明してください。',
                'criteria' => [
                    ['title' => 'INNER JOIN', 'description' => '両テーブルで結合条件に一致する行だけが残る、という説明がある', 'points' => 4],
                    ['title' => 'LEFT OUTER JOIN', 'description' => '左テーブルの行は必ず残り、右に一致がなければ NULL で埋まる、という説明がある', 'points' => 4],
                    ['title' => '使い分け', 'description' => '「一致しない行も見たいかどうか」で選ぶ、という趣旨がある', 'points' => 2],
                ],
                'reference' => 'INNER JOIN は結合条件を満たす行の組み合わせだけを返す。LEFT OUTER JOIN は左テーブルの全行を返し、右テーブルに対応行がなければ右側のカラムは NULL になる。欠損も含めて確認したいときは LEFT を使う。',
            ],
            [
                'body' => 'トランザクションの ACID 特性を、それぞれ一言で説明してください。',
                'criteria' => [
                    ['title' => 'Atomicity', 'description' => '「全部成功か全部失敗か」の趣旨', 'points' => 3],
                    ['title' => 'Consistency', 'description' => '整合性制約を壊さない状態から状態へ移る趣旨', 'points' => 3],
                    ['title' => 'Isolation', 'description' => '並行実行しても直列実行と同じ結果になる趣旨', 'points' => 3],
                    ['title' => 'Durability', 'description' => 'コミット後は障害があっても結果が残る趣旨', 'points' => 3],
                ],
            ],
            [
                'body' => 'インデックスを貼ると速くなる理由と、貼りすぎのデメリットを説明してください。',
                'criteria' => [
                    ['title' => '高速化の理由', 'description' => '全件走査を避け、B木などで対象行に直接たどり着ける趣旨', 'points' => 5],
                    ['title' => 'デメリット', 'description' => '更新時にインデックスの維持コストがかかる／容量を食う趣旨', 'points' => 5],
                ],
            ],
            [
                'body' => '正規化（第1〜第3正規形）の目的を説明してください。',
                'criteria' => [
                    ['title' => '目的', 'description' => 'データの重複と更新時異常を減らす趣旨', 'points' => 6],
                    ['title' => '概要', 'description' => '繰り返し項目の排除→部分関数従属の排除→推移的関数従属の排除、と段階的に進む趣旨', 'points' => 4],
                ],
            ],
        ]);

        // --- クイズ3: 11問（採点チャンクが 10 + 1 に分かれる境界） ---
        $bigQuizQuestions = [];
        for ($i = 1; $i <= 11; $i++) {
            $bigQuizQuestions[] = [
                'body' => "英単語 #{$i} の意味・使いどころ・例文を説明してください。（サンプル問題）",
                'criteria' => [
                    ['title' => 'コアの意味', 'description' => '中心的な意味を自分の言葉で説明できている', 'points' => 4],
                    ['title' => 'ニュアンス', 'description' => '類義語との違いや使う場面に触れている', 'points' => 3],
                    ['title' => '例文', 'description' => '自然な例文を1つ挙げている', 'points' => 3],
                ],
            ];
        }
        $this->makeQuiz($user, $categories['語彙'], [$tags['苦手']], '英単語 説明チャレンジ', $bigQuizQuestions);
    }

    /**
     * カテゴリ階層を作る。キーはカテゴリ名、値は Category。
     *
     * @return array<string, Category>
     */
    private function seedCategories(User $user): array
    {
        $out = [];

        // [名前, 親名(またはnull)] を上から順に作る（親が先に存在するよう並べる）。
        $tree = [
            ['IT資格', null],
            ['基本情報', 'IT資格'],
            ['アルゴリズム', '基本情報'],
            ['データベース', '基本情報'],
            ['応用情報', 'IT資格'],
            ['英語', null],
            ['語彙', '英語'],
            ['英作文', '英語'],
            ['歴史', null],
        ];

        foreach ($tree as [$name, $parentName]) {
            $parent = $parentName !== null ? $out[$parentName] : null;

            $out[$name] = Category::create([
                'user_id' => $user->id,
                'parent_id' => $parent?->id,
                'name' => $name,
                'slug' => Str::slug($name) ?: 'cat-'.Str::random(6),
                'depth' => $parent ? $parent->depth + 1 : 0,
                'sort_order' => 0,
            ]);
        }

        return $out;
    }

    /**
     * フラットなタグを作る。キーはタグ名。
     *
     * @return array<string, Tag>
     */
    private function seedTags(User $user): array
    {
        $out = [];

        foreach (['頻出', '苦手', '要復習'] as $name) {
            $out[$name] = Tag::create([
                'user_id' => $user->id,
                'name' => $name,
                'slug' => Str::slug($name) ?: 'tag-'.Str::random(6),
            ]);
        }

        return $out;
    }

    /**
     * クイズ1件と、その問題・ルーブリックをまとめて作る。
     *
     * @param  list<Tag>  $tags
     * @param  list<array{body: string, criteria: list<array{title: string, description: string, points: int}>, reference?: string}>  $questions
     */
    private function makeQuiz(User $user, Category $category, array $tags, string $title, array $questions): void
    {
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => null,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $quiz->tags()->attach(collect($tags)->pluck('id'));

        foreach ($questions as $index => $q) {
            // 満点はルーブリックの配点合計に一致させる（このアプリの不変条件）。
            $maxScore = collect($q['criteria'])->sum('points');

            $hasReference = array_key_exists('reference', $q);

            $question = Question::create([
                'quiz_id' => $quiz->id,
                'title' => null,
                'body' => $q['body'],
                'reference_answer' => $q['reference'] ?? null,
                'reference_answer_source' => $hasReference
                    ? ReferenceAnswerSource::Ai
                    : ReferenceAnswerSource::None,
                'reference_version' => $hasReference ? 1 : 0,
                'difficulty' => 3,
                'max_score' => $maxScore,
                'sort_order' => $index + 1,
            ]);

            foreach ($q['criteria'] as $cIndex => $c) {
                $question->rubricCriteria()->create([
                    'title' => $c['title'],
                    'description' => $c['description'],
                    'points' => $c['points'],
                    'sort_order' => $cIndex + 1,
                ]);
            }
        }

        // 非正規化カラム（question_count / total_max_score）を再計算する。
        app(QuizCounters::class)->sync($quiz);
    }
}
