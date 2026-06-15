# ThreadForge Materials Library

圧縮ファイルを主役として、用途（タグ）と作者で整理する配布素材保管庫です。

## 主な機能

- タグ → 作者、または作者 → タグで切り替え可能な素材一覧
- 圧縮ファイル、説明画像、名称、備考、作者名、利用規約の投稿
- 投稿パスワードまたはログインユーザーによる編集・論理削除
- ローカルログインと、管理画面でON/OFF・共有秘密鍵を設定できる親サイトSSO
- ユーザーごとの作者名、アイコン、利用規約初期値
- 管理者による一括削除、復元、完全消去、作者ID割当、ユーザー情報管理
- SSO利用時のローカル新規登録・ユーザー編集・消去制限
- タグ・利用規約・HOME・取説・表示順・デザイン設定
- アナリティクス、フルバックアップ、インポート

## ローカル起動

```powershell
$env:THREADFORGE_FRONTEND_ID='materials-library'
cd server
.\.php\php.exe -S 127.0.0.1:8000 -t .
```

別のターミナル:

```powershell
npm run dev:materials-library
```

`http://127.0.0.1:4277` を開きます。

## テストと配布

```powershell
npm run test:materials-library
npm run build:materials-library
npm run release:materials-library
```

Zip内の公開フォルダ名は `15_materials_library` です。既存環境の更新時は
`database.sqlite` と `storage/data/` を保持してください。
