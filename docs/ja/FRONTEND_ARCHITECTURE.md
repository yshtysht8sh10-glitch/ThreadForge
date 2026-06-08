# フロントエンド構成

ThreadForge は、共通バックエンドをひとつ持ち、その上に複数のフロントエンドを載せる構成へ移行します。現在のドット絵板は「唯一の画面」ではなく、最初のフロントエンドとして扱います。

## 現在の配置

```text
frontends/
  image-board/          現在の本番画像投稿掲示板フロント
  file-uploader/        ファイルアップローダ用フロント
  guide-posts/          制作指南・記事投稿所用フロント
  proxy-release/        MUGEN キャラ代理公開所用フロント
  materials-library/    制作素材提供所用フロント
  shared/               フロント共通部品
server/                 共通 PHP バックエンドと SQLite ストレージ
```

`frontends/image-board` は、これまで `client/` にあった既存の本番アプリです。
それ以外のフロントにも、各 `legacy/` サイトを元にした React/Vite の初期実装があります。

## 目指す形

- 認証、投稿、返信、ファイル、分析、SSO、設定はできるだけ `server/` の共通バックエンドコードに集約します。
- バックエンドコードは共通にしますが、DB とアップロードファイル置き場はフロントエンドIDごとに分離します。
- 画面遷移、レイアウト、見た目、文言、ルート構成は各フロントエンドの中に閉じます。
- UI 部品、hooks、型、API ヘルパーは、複数フロントで本当に使うことが見えてから `frontends/shared/` に移します。
- ビルドとリリースはフロントエンド単位で進めます。現時点のリリース ZIP は `image-board` と共通バックエンドを梱包します。
- フロントごとの差分は、バックエンドを分岐させるより小さなアダプタで吸収する方針です。

## ランタイムデータの分離

各フロントエンドは、それぞれ別の `THREADFORGE_FRONTEND_ID` で動かします。標準では次の場所にデータを保存します。

```text
server/runtime/<frontend-id>/database.sqlite
server/runtime/<frontend-id>/storage/data/
```

たとえば `image-board` と `file-uploader` は、投稿、ユーザー、設定、セッション、アップロードファイルを共有しません。PHP のバックエンドコードは共通でも、DB とファイルは完全に別になります。

ローカル起動例:

```powershell
$env:THREADFORGE_FRONTEND_ID = 'image-board'
php -S 127.0.0.1:8000 -t server
```

```powershell
$env:THREADFORGE_FRONTEND_ID = 'file-uploader'
php -S 127.0.0.1:8001 -t server
```

## フロントエンド

- `image-board`: 現在の ThreadForge 画像投稿掲示板。
- `file-uploader`: コメント、サイズ情報、削除キー、ページングを持つファイルアップローダ。
- `guide-posts`: MUGEN やドット絵制作知識を扱う記事投稿型サイト。
- `proxy-release`: MUGEN キャラやプラグインなどの代理公開サイト。
- `materials-library`: 利用条件付きの素材とダウンロードを一覧できる素材庫。

## legacy からの移植方針

- 各新規フロントの `legacy/` は、見た目と内容の参照元として残します。
- 初期 React アプリは、legacy の色合いと大まかなレイアウトを残しつつ、Vite でビルドできる現代的な構成にします。
- `file-uploader` は、薄いグレーのアップロードフォームと表組みを維持します。
- `guide-posts` は、白背景、青緑の固定ヘッダ、左メニューと記事本文の構成を維持します。
- `proxy-release` は、暗い背景と明るい緑文字の雰囲気を維持します。
- `materials-library` は、黒背景、淡い黄色文字、利用条件表付き素材カードを維持します。
- legacy の画像を使う場合は `new URL('../legacy/...', import.meta.url).href` で React 側へ取り込みます。

## 作業ルール

1. 新しいフロントは `frontends/<name>/` の下に作ります。
2. 具体的な別フロントから直接 import しません。
3. 重複が出たら最初はローカルに持ち、再利用が確定してから `frontends/shared/` に移します。
4. 共通バックエンドの契約は `docs/ja/SPEC.md` に残します。
5. リリーススクリプトは、どのフロントを梱包するか明示します。

これで今のドット絵板を守りながら、新しい掲示板たちをひとつずつ育てられる状態にします。
