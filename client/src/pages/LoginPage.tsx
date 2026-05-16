import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, mediaUrl } from '../api';
import { useAuth } from '../auth';
import { metricOptions, MetricId, metricValue } from '../metrics';
import { Post } from '../types';

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

  const claimPost = async (event: FormEvent) => {
    event.preventDefault();
    if (!claimId.trim() || !auth.token) return;
    setError(null);
    setStatus(null);
    try {
      const response = await api.claimUserPost(auth.token, claimId);
      setStatus(response.message);
      setClaimId('');
      await loadDashboard();
    } catch (err) {
      setError((err as Error).message);
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
    return (
      <section className="card account-page">
        <h1>個人ページ</h1>
        <div className="account-summary">
          {auth.user.icon_path && <img className="account-icon-preview" src={mediaUrl(auth.user.icon_path) ?? undefined} alt="" />}
          <strong>{auth.user.login_id}</strong>
          <button type="button" onClick={auth.logout}>ログアウト</button>
        </div>
        <form className="form-card" onSubmit={submitProfile}>
          <label>
            名前
            <input value={displayName} maxLength={30} onChange={(event) => setDisplayName(event.target.value)} required />
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

        <form className="form-card account-claim-form" onSubmit={claimPost}>
          <label>
            自分の作品として紐づける投稿No
            <input value={claimId} onChange={(event) => setClaimId(event.target.value)} placeholder="例: 92" />
          </label>
          <button type="submit">自分の作品にする</button>
        </form>

        <section className="account-posts">
          <h2>自分の投稿/返信</h2>
          {dashboardPosts.length === 0 && <p>表示できる投稿/返信はまだありません。</p>}
          {dashboardPosts.map((post) => (
            <article className="account-post-row" key={post.id}>
              <div>
                <strong>No.{displayPostNo(post)} {post.title || '無題'}</strong>
                <p>{post.claimed_by_user && !post.can_manage ? '自分の作品として紐づけ済み' : '自分のIDで投稿'}</p>
              </div>
              <div className="account-post-actions">
                <Link to={`/thread/${post.thread_id || post.id}`}>表示</Link>
                {post.can_manage && (
                  <>
                    <Link to={`/edit/${post.id}`}>編集</Link>
                    <button type="button" onClick={() => deleteOwnPost(post)}>削除</button>
                  </>
                )}
              </div>
            </article>
          ))}
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
              名前
              <input value={displayName} maxLength={30} onChange={(event) => setDisplayName(event.target.value)} required />
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
  const rows = useMemo(() => {
    let cumulative = 0;
    return [...posts]
      .sort((a, b) => (a.display_no ?? a.id) - (b.display_no ?? b.id))
      .map((post) => {
        cumulative += metricValue(post, metric);
        return { post, value: cumulative };
      });
  }, [metric, posts]);
  const max = Math.max(...rows.map((row) => row.value), 0);
  const points = rows.map((row, index) => {
    const x = rows.length <= 1 ? 50 : (index / (rows.length - 1)) * 100;
    const y = max === 0 ? 38 : 38 - (row.value / max) * 34;
    return `${x},${y}`;
  }).join(' ');

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
        <strong>閲覧数: {sumMetric(posts, 'views')}</strong>
        <strong>ええじゃ数: {sumMetric(posts, 'eejanaika')}</strong>
        <strong>お美事数: {sumMetric(posts, 'omigoto')}</strong>
        <strong>いい仕事数: {sumMetric(posts, 'goodjob')}</strong>
      </div>
      <div className="analytics-line-chart" role="img" aria-label="自分の投稿No順の累積折れ線グラフ">
        <svg viewBox="0 0 100 42" preserveAspectRatio="none">
          <line x1="0" y1="38" x2="100" y2="38" />
          {points && <polyline points={points} />}
        </svg>
      </div>
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

export default LoginPage;
