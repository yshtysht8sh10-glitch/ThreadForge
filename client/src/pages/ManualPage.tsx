const ManualPage = () => {
  return (
    <article className="manual-page">
      <h1>MUGEN ドット絵板の説明書</h1>

      <section>
        <h2>■ドット絵板■</h2>
        <p>ジャンル不問のドット絵投稿掲示板です。</p>
        <p>ドット絵であれば何でも投稿してください！</p>
        <p>MUGEN っぽければイラストや 3DCG の投稿も OK です。</p>
      </section>

      <section>
        <h3>&lt;禁止事項&gt;</h3>
        <ul>
          <li>他人の作品の無断投稿。</li>
          <li>投稿作品の無断利用。</li>
        </ul>
      </section>

      <section>
        <h3>&lt;注意事項&gt;</h3>
        <ul>
          <li>投稿時に「Tweet OFF」にチェックを入れると、投稿作品はTweet対象外になります。</li>
          <li>Tweet機能は新規投稿のみです。返信にはTweet機能はありません。</li>
          <li>返信では画像投稿はできません。</li>
          <li>削除は見た目上消えますが、内部データは保持されます。</li>
        </ul>
      </section>

      <section>
        <h3>&lt;投稿可能な作品の条件&gt;</h3>
        <ul>
          <li>拡張子 .png .gif。</li>
          <li>5MB以下。</li>
          <li>1280 x 960px以下。</li>
        </ul>
      </section>

      <section>
        <h3>&lt;Twitter ドット絵投稿テクニック&gt;</h3>
        <ul>
          <li>506 x 400pxが一番きれい。</li>
          <li>620 x 400pxを超えると再生されないことも。</li>
          <li>背景色が原色だと劣化が大きい。</li>
          <li>静止画は gif 非推奨。</li>
        </ul>
      </section>
    </article>
  );
};

export default ManualPage;
