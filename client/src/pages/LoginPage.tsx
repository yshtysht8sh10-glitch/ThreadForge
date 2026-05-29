import { FormEvent, UIEvent, useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, mediaUrl } from '../api';
import { useAuth } from '../auth';
import { metricOptions, MetricId, metricValue } from '../metrics';
import { Post } from '../types';
import { MAX_NAME_LENGTH } from '../name';

const CLAIM_SEARCH_BATCH_SIZE = 50;
const CLAIM_SEARCH_MAX_PAGES = 100;

const LoginPage = () => {
  const auth = useAuth();
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [loginId, setLoginId] = useState('');
  const [loginPassword, setLoginPassword] = useState('');
  const [loginPasswordConfirm, setLoginPasswordConfirm] = useState('');
  const [showLoginPassword, setShowLoginPassword] = useState(false);
  const [displayName, setDisplayName] = useState('Blank');
  const [postPassword, setPostPassword] = useState('');
  const [postPasswordConfirm, setPostPasswordConfirm] = useState('');
  const [showPostPassword, setShowPostPassword] = useState(false);
  const [homeUrl, setHomeUrl] = useState('');
  const [icon, setIcon] = useState<File | null>(null);
  const [idStatus, setIdStatus] = useState<string | null>(null);
  const [dashboardPosts, setDashboardPosts] = useState<Post[]>([]);
  const [analyticsPosts, setAnalyticsPosts] = useState<Post[]>([]);
  const [metric, setMetric] = useState<MetricId>('views');
  const [claimId, setClaimId] = useState('');
  const [claimQuery, setClaimQuery] = useState('');
  const [claimScope, setClaimScope] = useState('all');
  const [claimOrder, setClaimOrder] = useState<'oldest' | 'newest'>('oldest');
  const [claimResults, setClaimResults] = useState<Post[]>([]);
  const [claimNextPage, setClaimNextPage] = useState(2);
  const [claimHasMore, setClaimHasMore] = useState(false);
  const [claimLoading, setClaimLoading] = useState(false);
  const [claimLoadingMore, setClaimLoadingMore] = useState(false);
  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const ssoHandledRef = useRef(false);

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

  useEffect(() => {
    if (ssoHandledRef.current || auth.user || auth.loading) {
      return;
    }
    const ssoToken = ssoTokenFromLocation();
    if (!ssoToken) {
      return;
    }
    ssoHandledRef.current = true;
    setStatus('SSOログイン中...');
    setError(null);
    auth.ssoLogin(ssoToken)
      .then(() => {
        setStatus('ログインしました。');
        removeSsoTokenFromLocation();
      })
      .catch((err) => {
        setError((err as Error).message);
      });
  }, [auth]);

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
        if (loginPassword !== loginPasswordConfirm) {
          setError('ログインパスワードと確認入力が一致しません。');
          return;
        }
        if (postPassword !== postPasswordConfirm) {
          setError('投稿パスワードと確認入力が一致しません。');
          return;
        }
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
    await runClaimSearch();
  };

  const runClaimSearch = async () => {
    const query = claimQuery.trim();
    if (!query) {
      setClaimResults([]);
      setClaimHasMore(false);
      setClaimNextPage(2);
      return;
    }
    setClaimLoading(true);
    setClaimHasMore(false);
    setError(null);
    try {
      const allResults: Post[] = [];
      const seen = new Set<number>();
      let page = 1;
      let hasMore = true;
      while (hasMore && page <= CLAIM_SEARCH_MAX_PAGES) {
        const results = await api.search(query, claimScope, page, CLAIM_SEARCH_BATCH_SIZE, 'posts', claimOrder);
        results.forEach((post) => {
          if (!seen.has(post.id)) {
            seen.add(post.id);
            allResults.push(post);
          }
        });
        hasMore = results.length >= CLAIM_SEARCH_BATCH_SIZE;
        page += 1;
      }
      setClaimResults(allResults);
      setClaimNextPage(page);
      setClaimHasMore(hasMore);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setClaimLoading(false);
    }
  };

  const loadMoreClaimResults = async () => {
    const query = claimQuery.trim();
    if (!query || !claimHasMore || claimLoadingMore) return;
    setClaimLoadingMore(true);
    setError(null);
    try {
      const results = await api.search(query, claimScope, claimNextPage, CLAIM_SEARCH_BATCH_SIZE, 'posts', claimOrder);
      setClaimResults((current) => {
        const seen = new Set(current.map((post) => post.id));
        return [...current, ...results.filter((post) => !seen.has(post.id))];
      });
      setClaimNextPage((current) => current + 1);
      setClaimHasMore(results.length >= CLAIM_SEARCH_BATCH_SIZE);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setClaimLoadingMore(false);
    }
  };

  const handleClaimResultsScroll = (event: UIEvent<HTMLDivElement>) => {
    const element = event.currentTarget;
    if (element.scrollTop + element.clientHeight >= element.scrollHeight - 80) {
      void loadMoreClaimResults();
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
            <label>
              表示順
              <select value={claimOrder} onChange={(event) => setClaimOrder(event.target.value as 'oldest' | 'newest')}>
                <option value="oldest">古い順</option>
                <option value="newest">新しい順</option>
              </select>
            </label>
            <button type="submit" className="secondary">検索</button>
          </form>
          <div className="account-claim-results" onScroll={handleClaimResultsScroll}>
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
            {claimLoadingMore && <p>さらに読み込み中...</p>}
            {!claimLoadingMore && claimHasMore && (
              <button type="button" className="secondary" onClick={() => void loadMoreClaimResults()}>
                さらに読み込む
              </button>
            )}
          </div>
        </section>
        <section className="account-posts">
          <h2>自分の投稿/返信</h2>
          {dashboardPosts.length === 0 && <p>表示できる投稿/返信はまだありません。</p>}
          <div className="account-post-list">
            {dashboardPosts.map((post) => (
              <article className="account-post-row" key={post.id}>
                {post.parent_id === 0 && mediaUrl(post.image_path)
                  ? <img className="account-post-thumb" src={mediaUrl(post.image_path) ?? undefined} alt="" loading="lazy" decoding="async" />
                  : <span className="account-post-thumb account-post-thumb-placeholder" />}
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
          <span>ID<span className="required" aria-hidden="true">*</span></span>
          <input value={loginId} onBlur={checkId} onChange={(event) => setLoginId(event.target.value)} required />
        </label>
        {mode === 'register' && idStatus && <p className="status">{idStatus}</p>}
        <label>
          <span>ログインパスワード<span className="required" aria-hidden="true">*</span></span>
          <input type={showLoginPassword ? 'text' : 'password'} value={loginPassword} onChange={(event) => setLoginPassword(event.target.value)} required />
        </label>
        {mode === 'register' && (
          <>
            <label>
              <span>ログインパスワード（確認）<span className="required" aria-hidden="true">*</span></span>
              <input type={showLoginPassword ? 'text' : 'password'} value={loginPasswordConfirm} onChange={(event) => setLoginPasswordConfirm(event.target.value)} required />
            </label>
            <label className="checkbox-field">
              <input type="checkbox" checked={showLoginPassword} onChange={(event) => setShowLoginPassword(event.target.checked)} />
              ログインパスワードを表示
            </label>
            <label>
              <span>名前（/30文字）<span className="required" aria-hidden="true">*</span></span>
              <input value={displayName} maxLength={MAX_NAME_LENGTH} onChange={(event) => updateDisplayName(event.target.value)} required />
            </label>
            <label>
              <span>投稿パスワード<span className="required" aria-hidden="true">*</span></span>
              <input type={showPostPassword ? 'text' : 'password'} value={postPassword} maxLength={8} onChange={(event) => setPostPassword(event.target.value)} required />
            </label>
            <label>
              <span>投稿パスワード（確認）<span className="required" aria-hidden="true">*</span></span>
              <input type={showPostPassword ? 'text' : 'password'} value={postPasswordConfirm} maxLength={8} onChange={(event) => setPostPasswordConfirm(event.target.value)} required />
            </label>
            <label className="checkbox-field">
              <input type="checkbox" checked={showPostPassword} onChange={(event) => setShowPostPassword(event.target.checked)} />
              投稿パスワードを表示
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
  const labelStride = Math.max(1, Math.ceil(chartPoints.length / 18));
  const visibleChartLabels = chartPoints.filter((_, index) => (
    index === 0 || index === chartPoints.length - 1 || index % labelStride === 0
  ));
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

      <div className="analytics-total-grid">
        <section className="account-analytics-block analytics-total-panel">
          <h3>登録作品数</h3>
          <strong>{posts.length.toLocaleString('ja-JP')}</strong>
        </section>
        <section className="account-analytics-block analytics-total-panel">
          <h3>総計</h3>
          <strong>{total.toLocaleString('ja-JP')}</strong>
        </section>
      </div>

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
                <circle key={`${point.post.id}-${point.value}`} cx={point.x} cy={point.y} r="1.1">
                  <title>{`${point.label}: ${point.value.toLocaleString('ja-JP')}`}</title>
                </circle>
              ))}
            </svg>
          </div>
          <div className="analytics-point-labels" aria-hidden="true">
            {visibleChartLabels.map((point, index) => (
              <span
                key={`${point.post.id}-${index}`}
                className={index === 0 ? 'edge-start' : index === visibleChartLabels.length - 1 ? 'edge-end' : undefined}
                style={{ left: `${point.x}%` }}
              >
                {point.label}
              </span>
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

function ssoTokenFromLocation(): string {
  const searchToken = new URLSearchParams(window.location.search).get('sso');
  if (searchToken) {
    return searchToken;
  }
  const hashQuery = window.location.hash.includes('?') ? window.location.hash.split('?')[1] : '';
  return new URLSearchParams(hashQuery).get('sso') ?? '';
}

function removeSsoTokenFromLocation(): void {
  const url = new URL(window.location.href);
  url.searchParams.delete('sso');
  if (url.hash.includes('?')) {
    const [path, query] = url.hash.split('?');
    const params = new URLSearchParams(query);
    params.delete('sso');
    const nextQuery = params.toString();
    url.hash = nextQuery ? `${path}?${nextQuery}` : path;
  }
  window.history.replaceState(null, '', url.toString());
}
