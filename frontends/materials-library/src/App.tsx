import { CatalogCard, LegacyLayout } from '../../shared/src/LegacyLayout';

const rules = [
  { label: '改変', value: '○' as const },
  { label: '2次配布', value: '○' as const },
  { label: 'readmeへの記載しなくてよい', value: '○' as const },
  { label: 'mugen以外での利用（非営利）', value: '○' as const },
  { label: 'mugen以外での利用（営利目的）', value: '×' as const },
];

const materials = [
  {
    category: '1.エフェクト素材',
    title: '衝撃波エフェクト？',
    author: 'CYAMON氏',
    image: new URL('../legacy/05_Sozaiko/img/CYAMON/CYAMON003.gif', import.meta.url).href,
    description: 'After Effects CS4 を使って作成されたエフェクト素材。',
  },
  {
    category: '1.エフェクト素材',
    title: '斬撃エフェクト',
    author: 'CYAMON氏',
    image: new URL('../legacy/05_Sozaiko/img/CYAMON/CYAMON004.gif', import.meta.url).href,
    description: 'フォトショで作った素材を使い、MUGEN向けに扱いやすい形にした素材。',
  },
  {
    category: '3.その他ドット絵素材',
    title: 'ドット絵素材',
    author: 'code9653氏',
    image: new URL('../legacy/05_Sozaiko/img/code9653/DOTIMG_009470.png', import.meta.url).href,
    description: '小物や演出に使えるドット絵素材。',
  },
  {
    category: '4.効果音/ボイス素材',
    title: '音声素材',
    author: 'way-oh氏',
    image: undefined,
    description: '音声素材はサムネイルなしの配布カードとして扱います。',
  },
];

function App() {
  return (
    <LegacyLayout title="■素材庫■" subtitle="制作に役立つエフェクトや音声素材" className="materials-page">
      <section className="materials-intro" id="top">
        <p>制作に役立つエフェクトや音声素材が格納されています。</p>
        <p>利用規約を守ってご使用ください。エフェクトは画像クリックで原寸大表示、という旧サイトの感覚を残します。</p>
        <p>素材庫の拡充にご協力をお願いします。</p>
      </section>

      <nav className="materials-index">
        <a href="#effect">1.エフェクト素材</a>
        <a href="#sprite">2.キャラクタースプライト素材</a>
        <a href="#pixel">3.その他ドット絵素材</a>
        <a href="#sound">4.効果音/ボイス素材</a>
        <a href="#template">5.CNSテンプレ素材</a>
      </nav>

      <h2 className="materials-heading" id="effect">1.エフェクト素材</h2>
      <h3 className="materials-subheading">CYAMON氏</h3>
      <section className="materials-grid" id="list">
        {materials.map((material) => (
          <CatalogCard
            key={material.title}
            title={material.title}
            author={material.author}
            image={material.image}
            rules={rules}
            description={
              <>
                <strong>{material.title}</strong>
                <p>{material.description}</p>
                <small>{material.category}</small>
              </>
            }
          />
        ))}
      </section>

      <section className="materials-post" id="post">
        <h2>素材提供フォーム</h2>
        <label>素材名<input placeholder="素材名" /></label>
        <label>作者<input placeholder="作者名" /></label>
        <label>利用条件<textarea placeholder="改変、2次配布、readme記載など" /></label>
        <button>提供内容をプレビュー</button>
      </section>
    </LegacyLayout>
  );
}

export default App;
