import { CatalogCard, LegacyLayout } from '../../shared/src/LegacyLayout';

const releases = [
  {
    title: '花山薫',
    author: '夏羽氏',
    image: new URL('../legacy/06_DairiKoukai/img/yukikaedemoriya/Kaoru_Hanayama.gif', import.meta.url).href,
    description: '手描き率60%以上のMUGENキャラ代理公開。力強い立ち絵を大きく見せる旧サイトの雰囲気を残しています。',
  },
  {
    title: 'FF2皇帝',
    author: 'ダガー氏',
    image: new URL('../legacy/06_DairiKoukai/img/dagaa/Emperor.gif', import.meta.url).href,
    description: '旧代理公開所の緑文字と黒背景を踏襲したカード表示です。',
  },
  {
    title: 'FF10ジェクト',
    author: 'ダガー氏',
    image: new URL('../legacy/06_DairiKoukai/img/dagaa/Jecht.gif', import.meta.url).href,
    description: 'zip配布、作者表記、利用条件をThreadForgeの投稿データへ載せ替える想定です。',
  },
  {
    title: 'New Mario',
    author: 'MPL氏',
    image: new URL('../legacy/06_DairiKoukai/img/MPL/New_Mario.gif', import.meta.url).href,
    description: 'キャラクターごとの公開カードとして再構成します。',
  },
];

const rules = [
  { label: '改変', value: '○' as const },
  { label: '2次配布', value: '△' as const },
  { label: 'readmeへの記載', value: '○' as const },
  { label: 'mugen以外の利用', value: '×' as const },
];

function App() {
  return (
    <LegacyLayout title="■代理公開■" subtitle="MUGENキャラ・プラグインの代理公開所" className="proxy-page">
      <section className="proxy-intro" id="top">
        <p>代理公開受け付けています。</p>
        <p>キャラドットの手描き率が自己申告で60％あれば代理公開いたします。</p>
        <p>希望の方はしたらばの代理公開スレに書き込みをよろしくお願い致します。</p>
      </section>
      <section className="proxy-grid" id="list">
        {releases.map((release) => (
          <CatalogCard
            key={release.title}
            title={release.title}
            author={release.author}
            image={release.image}
            downloadLabel={release.title}
            rules={rules}
            description={<p>{release.description}</p>}
          />
        ))}
      </section>
      <section className="proxy-post" id="post">
        <h2>代理公開依頼</h2>
        <label>公開名<input placeholder="キャラ名・プラグイン名" /></label>
        <label>作者<input placeholder="作者名" /></label>
        <label>説明<textarea placeholder="利用条件や紹介文" /></label>
        <button>投稿プレビュー</button>
      </section>
    </LegacyLayout>
  );
}

export default App;
