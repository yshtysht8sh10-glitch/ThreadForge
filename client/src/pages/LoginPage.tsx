import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, mediaUrl } from '../api';
import { useAuth } from '../auth';
import { metricOptions, MetricId, metricValue } from '../metrics';
import { Post } from '../types';
import { MAX_NAME_LENGTH } from '../name';

const LoginPage = () => {
  const auth = useAuth();
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [loginId, setLoginId] = useState('');
  const [loginPassword, setLoginPassword] = useState('');
  const [displayName, setDisplayName] = useState('Blank');
  const [postPassword, setPostPassword] = useState('');
  const [homeUrl, setHomeUrl] = useState('');
  const [icon, setIcon] = useState<File | null>(null);
  const [idStatus, setIdStatus] = useState<string | null>(null);
  const [dashboardPosts, setDashboardPosts] = useState<Post[]>([]);
  const [analyticsPosts, setAnalyticsPosts] = useState<Post[]>([]);
  const [metric, setMetric] = useState<MetricId>('views');
  const [claimId, setClaimId] = useState('');
  const [claimQuery, setClaimQuery] = useState('');
  const [claimScope, setClaimScope] = useState('all');
  const [claimResults, setClaimResults] = useState<Post[]>([]);
  const [claimLoading, setClaimLoading] = useState(false);
  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadDashboard = async () => {
    if (!auth.token) return;
    const response = await api.listUserDashboard(auth.token);
    setDashboardPosts(response.posts);
    setAnalyticsPosts(response.analytics_posts);
  };

  useEffect(() => {
    if (!auth.user) return;
    setDisplayName(auth.user.display_name);
    setPostPassword(auth.user.post_password);
    setHomeUrl(auth.user.home_url ?? '');
    loadDashboard().catch((err) => setError((err as Error).message));
  }, [auth.user, auth.token]);

  const checkId = async () => {
    if (mode !== 'register' || loginId.trim() === '') {
      setIdStatus(null);
      return;
    }
    const result = await api.checkLoginId(loginId);
    setIdStatus(result.available ? 'このIDは使用できます。' : result.message ?? 'このIDは既に使われています。');
  };

  const submitLogin = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    setStatus(null);
    try {
      if (mode === 'login') {
        await auth.login(loginId, loginPassword);
      } else {
        const availability = await api.checkLoginId(loginId);
        if (!availability.available) {
          setError(availability.message ?? 'このIDは既に使われています。');
          return;
        }
        await auth.register({ login_id: loginId, password: loginPassword, display_name: displayName, post_password: postPassword, home_url: homeUrl, icon });
      }
      setStatus('ログインしました。');
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const submitProfile = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    setStatus(null);
    try {
      await auth.updateProfile({ display_name: displayName, post_password: postPassword, home_url: homeUrl, icon });
      setIcon(null);
      setStatus('プロフィールを保存しました。');
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const updateDisplayName = (value: string) => {
    setDisplayName(value.slice(0, MAX_NAME_LENGTH));
  };

  const claimPost = async (event: FormEvent) => {
    event.preventDefault();
    if (!claimId.trim() || !auth.token) return;
    await claimPostByNo(claimId);
  };

  const claimPostByNo = async (id: string) => {
    if (!id.trim() || !auth.token) return;
    setError(null);
    setStatus(null);
    try {
      const response = await api.claimUserPost(auth.token, id);
      setStatus(response.message);
      setClaimId('');
      await loadDashboard();
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const unclaimPostByNo = async (id: string) => {
    if (!id.trim() || !auth.token) return;
    setError(null);
    setStatus(null);
    try {
      const response = await api.unclaimUserPost(auth.token, id);
      setStatus(response.message);
      await loadDashboard();
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const searchClaimPosts = async (event: FormEvent) => {
    event.preventDefault();
    const query = claimQuery.trim();
    if (!query) {
      setClaimResults([]);
      return;
    }
    setClaimLoading(true);
    setError(null);
    try {
      const results = await api.search(query, claimScope);
      setClaimResults(results.filter((post) => post.parent_id === 0));
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setClaimLoading(false);
    }
  };

  const deleteOwnPost = async (post: Post) => {
    if (!auth.token || !window.confirm(`No.${displayPostNo(post)} を削除しますか？`)) return;
    setError(null);
    try {
      const response = await api.deletePost(String(post.id), '', auth.token);
      setStatus(response.message);
      await loadDashboard();
    } catch (err) {
      setError((err as Error).message);
    }
  };

  if (auth.user) {
    const claimedDisplayNos = new Set(
      dashboardPosts
        .filter((post) => post.claimed_by_user && !post.can_manage)
        .map((post) => displayPostNo(post))
    );

    return (
      <section className="card account-page">
        <h1>ユーザー設定</h1>
        <div className="account-summary">
          {auth.user.icon_path && <img className="account-icon-preview" src={mediaUrl(auth.user.icon_path) ?? undefined} alt="" />}
          <strong>{auth.user.login_id}</strong>
          <button type="button" onClick={auth.logout}>ログアウト</button>
        </div>
        <form className="form-card" onSubmit={submitProfile}>
          <label>
            名前（/30文字）
            <input value={displayName} maxLength={MAX_NAME_LENGTH} onChange={(event) => updateDisplayName(event.target.value)} required />
          </label>
          <label>
            投稿パスワード
            <input value={postPassword} maxLength={8} onChange={(event) => setPostPassword(event.target.value)} required />
          </label>
          <label>
            URL / HOME
            <input value={homeUrl} onChange={(event) => setHomeUrl(event.target.value)} />
          </label>
          <label>
            アイコン
            <input type="file" accept="image/png,image/jpeg,image/gif" onChange={(event) => setIcon(event.target.files?.[0] ?? null)} />
          </label>
          <div className="button-row align-right">
            <button type="submit">保存</button>
          </div>
        </form>

        <UserAnalytics posts={analyticsPosts} metric={metric} onMetricChange={setMetric} />

        <section className="account-claim-section">
          <h2>自分の作品として登録</h2>
          <form className="form-card account-claim-form" onSubmit={claimPost}>
            <label>
              投稿No
              <input value={claimId} onChange={(event) => setClaimId(event.target.value)} placeholder="例: 92" />
            </label>
            <button type="submit">登録</button>
          </form>
          <form className="form-card account-claim-search-form" onSubmit={searchClaimPosts}>
            <label>
              キーワード検索
              <input value={claimQuery} onChange={(event) => setClaimQuery(event.target.value)} placeholder="キーワード" />
            </label>
            <label>
              検索対象
              <select value={claimScope} onChange={(event) => setClaimScope(event.target.value)}>
                <option value="all">すべて</option>
                <option value="title">タイトル</option>
                <option value="message">本文</option>
                <option value="name">名前</option>
              </select>
            </label>
            <button type="submit" className="secondary">検索</button>
          </form>          <div className="account-claim-results">
            {claimLoading && <p>検索中...</p>}
            {!claimLoading && claimQuery.trim() !== '' && claimResults.length === 0 && <p>候補はありません。</p>}
            {claimResults.map((post) => {
              const no = displayPostNo(post);
              const claimed = claimedDisplayNos.has(no);
              return (
                <article className="account-claim-result" key={post.id}>
                  {mediaUrl(post.image_path)
                    ? <img src={mediaUrl(post.image_path) ?? undefined} alt="" />
                    : <span className="account-claim-image-placeholder" />}
                  <div>
                    <strong>No.{no} {post.title || '無題'}</strong>
                    <p>{post.name} / {new Date(post.created_at).toLocaleString()}</p>
                  </div>
                  <button type="button" className={claimed ? 'secondary' : undefined} onClick={() => claimed ? unclaimPostByNo(no) : claimPostByNo(no)}>
                    {claimed ? '解除' : '登録'}
                  </button>
                </article>
              );
            })}
          </div>
        </section>
        <section className="account-posts">
          <h2>自分の投稿/返信</h2>
          {dashboardPosts.length === 0 && <p>表示できる投稿/返信はまだありません。</p>}
          <div className="account-post-list">
          {dashboardPosts.map((post) => (
            <article className="account-post-row" key={post.id}>
              <div>
                <strong>No.{displayPostNo(post)} {post.title || '無題'}</strong>
                <p>{post.claimed_by_user && !post.can_manage ? '自分の作品として紐づけ済み' : '自分のIDで投稿'}</p>
              </div>
              <div className="account-post-actions">
                <Link to={listTargetHref(post)}>表示</Link>
                {post.can_manage && (
                  <>
                    <Link to={`/edit/${post.id}`}>編集</Link>
                    <button type="button" onClick={() => deleteOwnPost(post)}>削除</button>
                  </>
                )}
                {post.claimed_by_user && !post.can_manage && (
                  <button type="button" className="secondary" onClick={() => unclaimPostByNo(displayPostNo(post))}>解除</button>
                )}
              </div>
            </article>
          ))}
          </div>
        </section>

        {status && <p className="status">{status}</p>}
        {error && <p className="error">{error}</p>}
      </section>
    );
  }

  return (
    <section className="card account-page">
      <h1>ログイン</h1>
      <div className="button-row">
        <button type="button" className={mode === 'login' ? 'active' : undefined} onClick={() => setMode('login')}>ログイン</button>
        <button type="button" className={mode === 'register' ? 'active' : undefined} onClick={() => setMode('register')}>新規作成</button>
      </div>
      <form className="form-card" onSubmit={submitLogin}>
        <label>
          ID
          <input value={loginId} onBlur={checkId} onChange={(event) => setLoginId(event.target.value)} required />
        </label>
        {mode === 'register' && idStatus && <p className="status">{idStatus}</p>}
        <label>
          ログインパスワード
          <input type="password" value={loginPassword} onChange={(event) => setLoginPassword(event.target.value)} required />
        </label>
        {mode === 'register' && (
          <>
            <label>
              名前（/30文字）
              <input value={displayName} maxLength={MAX_NAME_LENGTH} onChange={(event) => updateDisplayName(event.target.value)} required />
            </label>
            <label>
              投稿パスワード
              <input value={postPassword} maxLength={8} onChange={(event) => setPostPassword(event.target.value)} required />
            </label>
            <label>
              URL / HOME
              <input value={homeUrl} onChange={(event) => setHomeUrl(event.target.value)} />
            </label>
            <label>
              アイコン
              <input type="file" accept="image/png,image/jpeg,image/gif" onChange={(event) => setIcon(event.target.files?.[0] ?? null)} />
            </label>
          </>
        )}
        <div className="button-row align-right">
          <button type="submit">{mode === 'login' ? 'ログイン' : '作成してログイン'}</button>
        </div>
      </form>
      {status && <p className="status">{status}</p>}
      {error && <p className="error">{error}</p>}
    </section>
  );
};

function UserAnalytics({ posts, metric, onMetricChange }: { posts: Post[]; metric: MetricId; onMetricChange: (metric: MetricId) => void }) {
  const selectedMetric = metricOptions.find((option) => option.id === metric) ?? metricOptions[0];
  const cumulativeRows = useMemo(() => {
    let cumulative = 0;
    return [...posts]
      .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
      .map((post) => {
        const current = metricValue(post, metric);
        cumulative += current;
        return { post, current, value: cumulative };
      });
  }, [metric, posts]);
  const individualRows = useMemo(() => {
    return [...posts]
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      .map((post) => ({ post, value: metricValue(post, metric) }));
  }, [metric, posts]);
  const total = cumulativeRows.at(-1)?.value ?? 0;
  const cumulativeMax = Math.max(total, 0);
  const individualMax = Math.max(...individualRows.map((row) => row.value), 0);
  const chartPoints = cumulativeRows.map((row, index) => {
    const x = cumulativeRows.length <= 1 ? 50 : (index / (cumulativeRows.length - 1)) * 100;
    const y = cumulativeMax === 0 ? 38 : 38 - (row.value / cumulativeMax) * 34;
    return { ...row, x, y, label: row.post.created_at.slice(0, 10) };
  });
  const points = chartPoints.map((point) => `${point.x},${point.y}`).join(' ');

  return (
    <section className="account-analytics">
      <h2>アナリティクス</h2>
      <label>
        表示データ
        <select value={metric} onChange={(event) => onMetricChange(event.target.value as MetricId)}>
          {metricOptions.map((option) => <option key={option.id} value={option.id}>{option.label}</option>)}
        </select>
      </label>

      <section className="account-analytics-block analytics-total-panel">
        <h3>総計</h3>
        <strong>{total.toLocaleString('ja-JP')}</strong>
      </section>

      <section className="account-analytics-block">
        <h3>各投稿内訳</h3>
        <div className="analytics-bar-list">
          {individualRows.map(({ post, value }) => (
            <div className="analytics-bar-row" key={post.id}>
              <span>No.{displayPostNo(post)}</span>
              <time>{post.created_at.slice(0, 10)}</time>
              <i style={{ width: `${value === 0 || individualMax === 0 ? 0 : Math.max(3, (value / individualMax) * 100)}%` }} />
              <strong>{value.toLocaleString('ja-JP')}</strong>
            </div>
          ))}
        </div>
      </section>

      <section className="account-analytics-block">
        <h3>推移（累積）</h3>
        <div className="analytics-axis-chart">
          <span className="analytics-y-max">{cumulativeMax.toLocaleString('ja-JP')}</span>
          <span className="analytics-y-min">0</span>
          <div className="analytics-line-chart" role="img" aria-label={`${selectedMetric.label}の投稿日時順の累積折れ線グラフ`}>
            <svg viewBox="0 0 100 42" preserveAspectRatio="none">
              <line x1="0" y1="38" x2="100" y2="38" />
              {points && <polyline points={points} />}
              {chartPoints.map((point) => (
                <circle key={`${point.post.id}-${point.value}`} cx={point.x} cy={point.y} r="1.1" />
              ))}
            </svg>
          </div>
          <div className="analytics-point-labels" aria-hidden="true">
            {chartPoints.map((point, index) => (
              <span key={`${point.post.id}-${index}`} style={{ left: `${point.x}%` }}>{point.label}</span>
            ))}
          </div>
        </div>
      </section>
    </section>
  );
}
function sumMetric(posts: Post[], metric: MetricId): string {
  return posts.reduce((sum, post) => sum + metricValue(post, metric), 0).toLocaleString('ja-JP');
}

function displayPostNo(post: Post): string {
  if (post.parent_id === 0) {
    return String(post.display_no ?? post.id);
  }
  return `${post.display_no ?? post.thread_id}-${post.reply_no ?? post.id}`;
}

function listTargetHref(post: Post): string {
  const targetId = post.parent_id === 0 ? post.id : post.thread_id;
  return `/?target=${encodeURIComponent(String(targetId))}#post-${targetId}`;
}

export default LoginPage;
