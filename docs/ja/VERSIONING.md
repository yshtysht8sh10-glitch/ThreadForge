# アプリ単位のVersion管理

ThreadForgeはmonorepoですが、Versionはアプリ単位で独立管理します。リポジトリ全体のリリースVersionは持ちません。

## 正本

各 `frontends/<frontend-id>/package.json` が、そのアプリのVersionの唯一の正本です。全アプリの現在値は `npm run versions` で取得し、`npm run versions:check` で構造とSemVerを検証できます。

配布処理は正本を読み、選択したアプリの配布ZIP内へ `VERSION` を生成します。この生成ファイルは配布先での表示用であり、正本ではありません。

## リリースルール

- 各アプリでSemVer（`MAJOR.MINOR.PATCH`）を使用します。
- 変更・リリースするアプリだけVersionを上げ、無関係なアプリは変更しません。
- CHANGELOGの見出しにはアプリ名とVersionを記載します。
- 新しいtagは `<app-name>-vX.Y.Z` とします。
- GitHub Release名には、例 `Proxy Release 0.9.8` のようにアプリ名を含めます。
- 過去のtagとGitHub Releaseは変更・削除しません。旧 `vX.Y.Z` tagはImage Boardの履歴として残し、新規リリースでは使用しません。

## リリース手順

1. リリース対象の `frontends/<frontend-id>/package.json` だけを更新します。
2. `CHANGELOG.md` と `CHANGELOG.ja.md` に対象アプリの履歴を追加します。
3. `npm run versions:check` と対象アプリのテスト・ビルドを実行します。
4. `npm run release:<frontend-id>` を実行し、ZIP名とZIP内の `VERSION` を確認します。
5. `<frontend-id>-v<version>` tagと、`<App Name> <version>` 形式のGitHub Releaseを作成します。
