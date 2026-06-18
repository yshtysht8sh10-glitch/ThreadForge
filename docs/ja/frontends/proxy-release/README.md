# proxy-release

MUGENキャラクターデータの代理公開所向けフロントエンドです。
materials-libraryをベースに、zip投稿、作者・タグ整理、管理機能を利用します。

- [仕様](SPEC.md)
- [DB](DB.md)
- フロントエンド実装: `frontends/proxy-release/`

主な差分:

- 投稿zipからAIR/SFFを読み取り、待機モーションGIFの自動生成を試みます。
- 手動の説明画像も選択できます。
- 上部メニューに、管理画面で設定できる「試遊」リンクを持ちます。
- 一覧カードは外枠を消し、キャラクター画像がカードからはみ出せる表示を想定します。

開発時ランタイム:

```text
server/runtime/proxy-release/database.sqlite
server/runtime/proxy-release/storage/data/
```
