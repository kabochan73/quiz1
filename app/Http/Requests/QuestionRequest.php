<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 問題の作成・更新で共通するバリデーション。
 *
 * ポイント:
 * - 模範解答の入力欄はない（採点の基準はルーブリックだけ）
 * - ルーブリックの観点は 1〜10 個。各観点に配点（1以上）が必要
 * - max_score は入力させない。「配点の合計」を保存時に自動計算する（criteriaTotalPoints()）
 */
abstract class QuestionRequest extends FormRequest
{
    /** 1問に付けられるルーブリック観点の下限・上限。 */
    public const int MIN_CRITERIA = 1;

    public const int MAX_CRITERIA = 10;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'difficulty' => ['required', 'integer', 'between:1,5'],

            'criteria' => ['required', 'array', 'min:'.self::MIN_CRITERIA, 'max:'.self::MAX_CRITERIA],
            'criteria.*.title' => ['required', 'string', 'max:150'],
            'criteria.*.description' => ['required', 'string', 'max:2000'],
            'criteria.*.points' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'body' => '問題文',
            'difficulty' => '難易度',
            'criteria' => '採点の観点',
            'criteria.*.title' => '観点名',
            'criteria.*.description' => '加点の基準',
            'criteria.*.points' => '配点',
        ];
    }

    /**
     * バリデーション済みの観点データ（sort_order を振り直して返す）。
     *
     * @return list<array{title: string, description: string, points: int, sort_order: int}>
     */
    public function criteria(): array
    {
        $criteria = [];

        foreach (array_values($this->validated('criteria')) as $index => $row) {
            $criteria[] = [
                'title' => $row['title'],
                'description' => $row['description'],
                'points' => (int) $row['points'],
                'sort_order' => $index + 1,
            ];
        }

        return $criteria;
    }

    /**
     * 問題の満点 = 観点の配点合計。
     */
    public function criteriaTotalPoints(): int
    {
        return array_sum(array_column($this->criteria(), 'points'));
    }
}
