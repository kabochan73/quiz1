# quiz-1 — AI採点クイズアプリ

自作の記述式問題（クイズ）を、Claude API がルーブリックに基づいて採点する個人用学習アプリ。
「学んだ内容を自分の言葉で説明できるか（記憶の定着度）」を確かめる想起練習ツール。

- 設計ドキュメント: [`docs/`](./docs/)
- 要件定義: [docs/01-requirements.md](./docs/01-requirements.md)
- DB設計: [docs/02-database-design.md](./docs/02-database-design.md)
- 技術スタック: [docs/03-tech-stack.md](./docs/03-tech-stack.md)
- イテレーション計画: [docs/04-iteration-plan.md](./docs/04-iteration-plan.md)

## 技術スタック

PHP 8.4 / Laravel 12 / Laravel Sail (Docker) / PostgreSQL 18 / Breeze (Blade + Alpine + Tailwind v4) /
公式 `anthropic-ai/sdk` / Pest 3

## セットアップ

前提: Docker が動いていること。

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

`.env` に `ANTHROPIC_API_KEY` を設定する。→ http://localhost:8000

## よく使うコマンド

```bash
./vendor/bin/sail artisan queue:work   # 採点ワーカー
./vendor/bin/sail test                 # Pest
./vendor/bin/sail php ./vendor/bin/phpstan analyse
./vendor/bin/sail pint
```
