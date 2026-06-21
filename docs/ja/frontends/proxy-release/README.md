# proxy-release

MUGENキャラクターやステージなどの代理公開向けフロントエンドです。materials-library をベースに、zip投稿、作者・タグ整理、管理画面、説明画像表示を代理公開向けに調整しています。

- [仕様](SPEC.md)
- [DB](DB.md)
- フロントエンド実装: `frontends/proxy-release/`

## 主な機能

- 投稿zipから AIR/SFF を読み取り、待機モーションGIFの自動生成を試みます。
- 手動の説明用画像も選択できます。手動画像がある場合はそちらを優先します。
- 上部メニューに `試遊` リンクを持ちます。リンク先は管理画面で設定できます。
- 試遊リンクが未設定の場合、メニューには無効表示として出します。
- 管理画面の設定とデザイン機能で、legacy風の緑基調にも変更できます。

## ローカル実行

```powershell
npm run dev:proxy-release
```

開発時の runtime データ:

```text
server/runtime/proxy-release/database.sqlite
server/runtime/proxy-release/storage/data/
```

## リリースZip

```powershell
npm run release:proxy-release
```

出力先:

```text
release/threadforge-proxy-release-<version>.zip
```

リリースZipには runtime DB とアップロード済みファイルは含めません。運用データを移す場合は、管理画面のフルバックアップとインポートを使います。
