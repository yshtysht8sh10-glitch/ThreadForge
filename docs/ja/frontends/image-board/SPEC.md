# image-board 仕様

[仕様索引へ戻る](../../SPEC.md)
[English image-board specification](../../../frontends/image-board/SPEC.md)

## 目的

`image-board` は現行の画像投稿掲示板フロントです。

ドット絵板として運用してきた機能を維持しつつ、今後の複数フロント構成の1つとして扱います。

## 主要機能

- 親投稿と返信
- 親投稿への画像アップロード
- 返信投稿
- 投稿編集と削除
- 返信付き投稿の編集画面表示
- 検索
- 順位/ランキング
- 取説/マニュアルページ
- RSS
- ユーザー登録、ログイン、プロフィール編集、アイコン表示
- SSOログイン
- 管理画面
- 管理者による一括削除、復元、完全削除
- DB整合性チェック
- フルバックアップのエクスポート/インポート
- ローカルアーカイブ取り込み
- SNS連携設定と投稿プレビュー
- X、Bluesky、Mastodon、Misskey の反応数キャッシュ
- gdgd/特殊投稿モード
- アクセス数と投稿分析

## 見た目

- 黒背景
- ドット絵板らしいクラシックな掲示板ナビゲーション
- 親投稿カードと返信表示
- 画像を大きく見せるレイアウト

## Runtime

開発環境:

```text
server/runtime/image-board/database.sqlite
server/runtime/image-board/storage/data/
```

リリースZip配置先:

```text
11_image_board/database.sqlite
11_image_board/storage/data/
```

## 現在の状態

本番相当の成熟フロントです。新しい4フロントは、この `image-board` と共通バックエンドを共有しつつ、DBとストレージは独立させます。
