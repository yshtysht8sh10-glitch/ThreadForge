import { FormEvent, useEffect, useState } from 'react';
import { mediaUrl } from '../api';
import { useAuth } from '../auth';

const LoginPage = () => {
  const auth = useAuth();
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [loginId, setLoginId] = useState('');
  const [loginPassword, setLoginPassword] = useState('');
  const [displayName, setDisplayName] = useState('Blank');
  const [postPassword, setPostPassword] = useState('');
  const [homeUrl, setHomeUrl] = useState('');
  const [icon, setIcon] = useState<File | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!auth.user) return;
    setDisplayName(auth.user.display_name);
    setPostPassword(auth.user.post_password);
    setHomeUrl(auth.user.home_url ?? '');
  }, [auth.user]);

  const submitLogin = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    setStatus(null);
    try {
      if (mode === 'login') {
        await auth.login(loginId, loginPassword);
      } else {
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

  if (auth.user) {
    return (
      <section className="card account-page">
        <h1>ログイン設定</h1>
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
          <input value={loginId} onChange={(event) => setLoginId(event.target.value)} required />
        </label>
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

export default LoginPage;
