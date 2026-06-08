const posts = [
  '「キャラ作ろう！」と思ってからの、はじめに',
  '初心者ゆえのあやまち',
  'ゼロベースキャラクター制作のすすめ',
  '改変キャラクター制作のすすめ',
  '「歩き動作と走り動作」',
  '格闘ゲームのドット絵における色の使い方例',
  'グラデーションとは？',
  'ぼくのかんがえた最強の4色塗り',
  'ドット絵における、質感表現講座',
];

function App() {
  return (
    <div className="guide-page" id="top">
      <header className="guide-head">
        <div className="guide-head__inner">
          <strong>-Topページ-</strong>
          <span>管理人:Ｙｅｓ</span>
        </div>
      </header>
      <div className="guide-layout">
        <aside className="guide-menu">
          <div className="guide-logo">Do||Mu,File</div>
          <section>
            <h2>スタート</h2>
            {posts.slice(0, 4).map((post) => <a key={post} href="#article">› {post}</a>)}
          </section>
          <section>
            <h2>スプライト関連</h2>
            {posts.slice(4).map((post) => <a key={post} href="#article">› {post}</a>)}
          </section>
        </aside>

        <main className="guide-main">
          <article className="guide-box" id="article">
            <h1>注意</h1>
            <p>
              当サイトは皆様に作成していただいた、ドット絵の製作を始めとする
              MUGEN のキャラ製作に役立つ情報ページを集めて掲載していくサイトです。
            </p>
            <p className="guide-strong">
              当サイトを閲覧する上で、以下のことにご注意ください。
            </p>
            <ul>
              <li>ページを転載する際は、ページの製作者さんに許可を取るようにしてください。</li>
              <li>ご意見等ございましたら、掲示板の企画スレへお願いします。</li>
            </ul>
          </article>

          <article className="guide-box guide-recruit" id="post">
            <h2>ページ募集のお願い</h2>
            <p>
              MUGEN のキャラ製作に役立つ内容であるならば、どんなページでもかまいません。
              投稿ページは ThreadForge の投稿部品を使って、記事と添付画像を管理できる形にしていきます。
            </p>
            <ul>
              <li>背景色は白を基本にします。</li>
              <li>画像は原寸表示を尊重します。</li>
              <li>投稿者ごとのフォルダ感を、Web アプリ上ではカテゴリと作者で表現します。</li>
            </ul>
          </article>

          <section className="guide-post-form">
            <h2>記事投稿プレビュー</h2>
            <label>タイトル<input placeholder="記事タイトル" /></label>
            <label>作者<input placeholder="投稿者名" /></label>
            <label>本文<textarea placeholder="制作指南を書きます" /></label>
            <button>下書きを作成</button>
          </section>
        </main>
      </div>
    </div>
  );
}

export default App;
