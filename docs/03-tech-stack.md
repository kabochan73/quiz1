# 03. 技術スタック（v1）

## 0. 確定バージョン（2026-09 時点）

| 項目 | 確定 | メモ |
|---|---|---|
| 実行環境 | **Docker / Laravel Sail** | PHP・PostgreSQL をコンテナで。ホストには Docker のみ必要 |
| PHP | **8.4** | Sail ランタイム `runtimes/8.4`（`compose.yaml` で固定）|
| Laravel | **12.x** | 現行最新は 13（2026-03 リリース）だが、Breeze を使うため **v1 は 12 に固定**。12 のバグ修正は 2026-08、セキュリティ修正は 2027-02 まで。v2 で「Laravel 13 + Livewire スターターキット」へ移行を予定 |
| Composer | 2.x | |
| Node | 20 LTS 以上（Sail コンテナ同梱） | Vite 用 |

## 1. 全体像

| レイヤー | 採用 | 理由 |
|---|---|---|
| 実行環境 | **Laravel Sail**（Docker Compose） | `compose.yaml`: `laravel.test`(PHP 8.4) + `pgsql`(postgres:18-alpine) |
| フレームワーク | **Laravel 12**（PHP 8.4） | 王道。Breeze が使える最後の世代 |
| 画面 | **Blade + Alpine.js + Tailwind CSS v4** | SPA を挟まず Laravel の基礎に集中。採点中表示は Alpine のポーリング |
| ビルド | **Vite**（Laravel 標準） | |
| 認証 | **Laravel Breeze（Blade スタック）** | 最小構成の認証。Fortify を薄くラップ。今後更新なしだが v1 には十分 |
| DB | **PostgreSQL 18**（開発・本番とも） | Sail の `pgsql` コンテナ。開発と本番を揃える |
| キュー | **database ドライバ（v1）** | Redis なしで非同期採点を実現。v2 で `sail add redis` |
| AI | **Claude API / 公式 `anthropic-ai/sdk`**（+ 自作 `GraderInterface` ラッパー） | 構造化出力で採点結果を受け取る。中身が見えて学習向き。v2 のマルチプロバイダ化はラッパーで対応 |
| 採点モデル | **`claude-sonnet-5`**（`.env` で切替可） | コスト/速度/品質のバランス。一貫性が足りなければ `claude-opus-5` |
| テスト | **Pest 3** | Laravel 12 デフォルト |
| 静的解析 / 整形 | **Larastan（PHPStan）level 6+ / Laravel Pint** | ポートフォリオの品質担保 |
| CI | v1 は任意（v3 で GitHub Actions） | |

## 2. ディレクトリ / レイヤー構成（v1）

```
app/
├── Models/            Quiz, Question, RubricCriterion, Category, Tag,
│                      QuizAttempt, QuestionAttempt, GradingRun, CriterionScore
├── Http/
│   ├── Controllers/   Quiz, Question(quiz配下), QuizAttempt, Dashboard
│   └── Requests/      FormRequest でバリデーション（問題数1〜30、配点合計 = 満点 など）
├── Policies/          QuizPolicy, QuizAttemptPolicy（quiz 経由で user_id スコープ）
├── Jobs/
│   ├── GradeChunk            最大10問を1コールで採点（キュー、1 GradingRun に対応）
│   └── FinalizeQuizAttempt   全 GradeChunk 完了を検知して合計点を確定
├── Services/
│   ├── Quiz/
│   │   └── QuizCounters       question_count / total_max_score の再計算
│   ├── Reference/
│   │   ├── ReferenceAnswerGenerator   問題文+ルーブリック → AI が参考解答を生成
│   │   └── GenerateReferenceAnswer(Job) 生成をキューで実行、reference_version++
│   └── Grading/
│       ├── GraderInterface           採点の抽象（テストでフェイク可能）
│       ├── ClaudeGrader               Claude API 実装
│       ├── GradingPromptBuilder       複数問（問題文+ルーブリック+回答+任意の参考解答）→ プロンプト
│       └── GradingResult (DTO)        問ごとの観点別スコア + 総評 のリスト
└── Support/
```

**ポイント**: コントローラや Job は `GraderInterface` にだけ依存する。
`ClaudeGrader` を差し替えれば、テストでは API を叩かないフェイクを注入できる。v2 のマルチプロバイダ化もここだけで済む。

## 3. AI 採点の設計

### 3.1 フロー

```
クイズ提出（全問まとめて POST）
  → QuizAttemptController@store
      - QuizAttempt を status=grading で作成（max_score / question_count をスナップショット）
      - 各問の QuestionAttempt を status=pending で作成
      - 問を GRADING_CHUNK_SIZE（既定10）ずつに分割 → チャンクごとに GradeChunk::dispatch()
        （30問なら 3 ジョブ。Bus::batch でまとめる）
      - 即リダイレクト（結果ページ、"採点中 0/N" 表示）
  → GradeChunk（キューワーカー、最大10問を1コール）
      - GradingRun を status=running で作成、対象 QuestionAttempt を status=grading
      - GradingPromptBuilder で「複数問 + それぞれのルーブリック/回答（+ あれば参考解答）」を1メッセージに整形
      - ClaudeGrader->gradeChunk() で Claude API を1回呼ぶ（構造化出力＝問ごとの結果配列）
      - 各問: criterion_scores を保存、score = 部分点の合計、status=graded、grading_run_id / reference_version をセット
      - GradingRun: status=succeeded, input/output_tokens, ai_raw_response, finished_at
      - コール失敗時: GradingRun.status=failed、対象 QuestionAttempt を status=failed（batch は止めない）
  → FinalizeQuizAttempt（batch の finally）
      - 全 QuestionAttempt が graded → QuizAttempt.total_score 集計, status=graded
      - 一部 failed → status=partially_failed（失敗問だけ再採点＝新しい GradeChunk）
  → 結果ページ（Alpine が数秒間隔でポーリング、"採点中 k/N" → 完了で全内訳表示）
```

> **相対評価を避ける**: 1コールに複数問を入れても、プロンプトで「各回答は自身のルーブリックだけで独立採点。
> 同じコール内の他の回答と比較・順位付けしない」と明示する。`prompt_version` で管理。

### 3.2 モデル選定

| 用途 | モデル | 備考 |
|---|---|---|
| 既定 | `claude-sonnet-5` | 採点の品質/コスト/速度のバランスが良い |
| 高一貫性が必要なとき | `claude-opus-5` | ルーブリック解釈のブレが少ない。`.env` で切替 |

`config/grading.php` にモデル名・プロンプトバージョン・タイムアウトを集約し、`.env` で上書きする。

### 3.3 構造化出力（採点結果のスキーマ）

Messages API の `output_config.format`（JSON スキーマ）で、パース不要の結果を受け取る。
1コールで複数問を採点するので、**問ごとの結果を配列**で返させる。

```jsonc
{
  "results": [
    {
      "question_index": 0,                 // このコールで送った問の配列添字
      "criteria": [
        { "criterion_index": 0, "awarded_points": 8, "comment": "用語は正確だが例示が不足" }
      ],
      "overall_feedback": "理解の骨格はできている。次は…",
      "confidence": "high"                  // low は UI で「要確認」マーク
    }
    // ... 送った問の数だけ
  ]
}
```

- `question_index` / `criterion_index` は送信した配列の添字に対応させる（DB の ID を露出しない）
- レスポンスの `results` 件数・`question_index` の網羅性をアプリ側で検証（欠けた問は failed 扱い）
- `awarded_points` は `0 <= x <= その観点の配点` をアプリ側でも再検証（AI の逸脱に備える）
- 各問の `score` と クイズの `total_score` は AI に計算させず、アプリで合算する

### 3.4 プロンプト方針（`prompt_version = v1`）

- **System**: 「あなたは厳格だが公平な採点者。**ルーブリックの各観点のみに基づいて**部分点をつける。
  参考解答が与えられても、それは表現の一例であって唯一の正解ではない。受験者の表現が参考解答と違っても、
  ルーブリックの観点を満たしていれば加点する。参考解答に誤り・疑問があれば `overall_feedback` で指摘する。
  **複数の回答をまとめて渡されても、各回答はそのルーブリックだけで独立に採点し、他の回答と比較・順位付けしない**」
- **User**: `[問0] 問題文 / ルーブリック / （あれば）参考解答 / 受験者の回答` … `[問1] …` を問ごとに明確に区切って提示
- 出力は構造化出力スキーマ（`results` 配列）に従わせる
- プロンプト本文はコード内に定数化し、変更時は `prompt_version` を上げる（履歴と対応付け）
- 1コールの問数は `GRADING_CHUNK_SIZE`（既定10）。増やすと出力が長くなり品質・打ち切りリスクが上がる

### 3.5 参考解答の生成（`ReferenceAnswerGenerator`）

- 模範解答はユーザーに書かせない。問題編集画面の「参考解答を生成」ボタンで AI が
  **問題文 + ルーブリック** から解答例を作り、`questions.reference_answer`（`source = ai`）に保存する
- 生成・再生成・手直しのたびに `reference_version++`。採点時は使ったバージョンを `question_attempts.reference_version` に記録
- **一度きり生成して保存**（毎回の採点では作り直さない）。同じクイズの再挑戦で点数を比較できるようにするため
- 参考解答は任意。`none` のままでもクイズは公開・受験でき、その場合の採点はルーブリックのみで行う
- 生成もキュー（`GenerateReferenceAnswer` Job）。1問1コール。トークンは記録するが `grading_runs` とは別（生成専用の軽いログ or 問題の updated ログで十分）

### 3.6 テスト戦略

- `GraderInterface` のフェイク実装（渡された問数ぶんの `GradingResult` を返す）で Job / Controller / 集計 / チャンク分割をテスト
- `ClaudeGrader` 自体は「リクエスト整形」と「レスポンス→DTO 変換」を純粋関数的に分け、HTTP をモックした単体テストを1本
  - `results` の件数不足・`question_index` 欠番・配点超過など**異常系レスポンス**のハンドリングを重点的に
- 30問（3チャンク）で「1チャンクだけ失敗 → partially_failed → 再採点で graded」の結合テスト
- 実 API を叩く疎通テストは `@group external` などで通常は除外

## 4. 依存パッケージ（v1）

| パッケージ | 用途 | 備考 |
|---|---|---|
| `laravel/framework` `^12.0` | 本体 | |
| `laravel/breeze` `^2` (require-dev) | 認証スキャフォールド | `--stack=blade` |
| `anthropic-ai/sdk` `^0.4x` | Claude API 公式 SDK | + `guzzlehttp/guzzle:^7`。PHP ^8.1 |
| `pestphp/pest` `^3` + `pest-plugin-laravel` | テスト | |
| `larastan/larastan` `^3` (require-dev) | 静的解析 | |
| `laravel/pint` (require-dev) | コード整形 | |

> **SDK 注意**: `anthropic-ai/sdk` は 0.x でマイナー更新が速い。`composer.lock` を必ずコミットし、
> API 呼び出しは `ClaudeGrader` の中だけに閉じ込める（破壊的変更の影響範囲を1ファイルに限定）。
> 名前空間・メソッド名は導入時に公式ドキュメント（platform.claude.com/docs/en/api/sdks/php）で確認。

### 採点ラッパーの構造（再掲）

```
GraderInterface
  gradeChunk(array $items): array   // $items: [{question_body, criteria[], answer_text, reference_answer?}]
                                    // 返り値: 問ごとの GradingResult

ClaudeGrader implements GraderInterface
  - GradingPromptBuilder でメッセージ生成
  - anthropic-ai/sdk で messages 呼び出し（output_config.format = results スキーマ、stream）
  - レスポンス → GradingResult[] に変換、usage をそのまま返す

FakeGrader implements GraderInterface   // テスト用。決め打ちの結果を返す
```

App サービスプロバイダで `GraderInterface` を `ClaudeGrader` にバインド。テストで `FakeGrader` に差し替え。

## 5. 環境変数（`.env`）

Sail が設定する DB / ポート系（`DB_CONNECTION=pgsql`, `DB_HOST=pgsql`, `APP_PORT=8000` ...）に加えて:

```
QUEUE_CONNECTION=database

ANTHROPIC_API_KEY=sk-ant-...
GRADING_MODEL=claude-sonnet-5      # 一貫性が足りなければ claude-opus-5
GRADING_PROMPT_VERSION=v1
GRADING_CHUNK_SIZE=10              # 1コールで採点する最大問数（1〜30）
GRADING_TIMEOUT=120                # チャンク採点は出力が長いので長めに。SDK は stream 推奨
REFERENCE_MODEL=claude-sonnet-5    # 参考解答の生成モデル（採点と分けておく）
```

## 6. セットアップ / 起動フロー

### 初回のみ（構築済み）

```
composer create-project laravel/laravel quiz-1 "12.*"
composer require anthropic-ai/sdk guzzlehttp/guzzle
composer require --dev laravel/breeze larastan/larastan
php artisan breeze:install blade --pest        # Blade + Alpine + Tailwind + Pest
php artisan sail:install --with=pgsql          # compose.yaml 生成（PHP は 8.4 に手動固定）
./vendor/bin/sail build
```

### 日常の起動（すべて Sail 経由）

```
cp .env.example .env
./vendor/bin/sail up -d               # PHP + PostgreSQL コンテナ起動
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev          # Vite（別ターミナル、または build）
./vendor/bin/sail artisan queue:work   # 採点ワーカー（別ターミナル）
# → http://localhost:8000
```

> `alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'` を通すと `sail artisan ...` と書ける。
> `sail composer` / `sail npm` / `sail artisan` / `sail test` はすべてコンテナ内で実行される。
> ホスト PHP には `pdo_pgsql` が無いので、artisan 系は必ず Sail 経由で。
