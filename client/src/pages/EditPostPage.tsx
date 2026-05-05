import { FormEvent, useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { api, DEFAULT_PUBLIC_SETTINGS, PublicSettings } from '../api';
import { Post } from '../types';

const EditPostPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [post, setPost] = useState<Post | null>(null);
  const [name, setName] = useState('');
  const [url, setUrl] = useState('');
  const [title, setTitle] = useState('');
  const [message, setMessage] = useState('');
  const [gdgd, setGdgd] = useState(false);
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);
  const [password, setPassword] = useState(() => {
    const state = location.state as { password?: string } | null;
    return state?.password ?? '';
  });
  const [file, setFile] = useState<File | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const isReply = post ? post.parent_id !== 0 : false;

  useEffect(() => {
    if (!id) return;
    api.getPost(id)
      .then((data) => {
        setPost(data);
        setName(data.name);
        setUrl(data.url ?? '');
        setTitle(data.title);
        setMessage(data.message);
        setGdgd(Boolean(data.gdgd));
      })
      .catch((err) => setError(err.message));
  }, [id]);

  useEffect(() => {
    api.publicSettings()
      .then((response) => response.success && setSettings(response.settings))
      .catch(() => setSettings(DEFAULT_PUBLIC_SETTINGS));
  }, []);

  const onSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!id) return;
    if (!password.trim()) {
      setError('パスワードを入力してください。');
      return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('name', name);
    formData.append('url', url);
    formData.append('title', title);
    formData.append('message', message);
    formData.append('password', password);
    if (!isReply && settings.config.gdgdEnabled) {
      formData.append('gdgd', gdgd ? '1' : '0');
    }
    if (!isReply && file) {
      formData.append('file', file);
    }

    setError(null);
    setStatus('更新中...');

    try {
      const response = await api.updatePost(formData);
      if (response.success) {
        navigate(post ? `/thread/${post.thread_id}` : '/');
      } else {
        setError(response.message);
        setStatus(null);
      }
    } catch (err: any) {
      setError(err.message);
      setStatus(null);
    }
  };

  return (
    <div>
      <div className="card">
        <h1>投稿編集</h1>
        {error && <div className="error">エラー: {error}</div>}
        {status && <div className="status">{status}</div>}
        {!password.trim() && <div className="error">編集モードから投稿を選択してください。</div>}
        {!post && !error && <p>投稿を読み込み中...</p>}
        {post && (
          <form onSubmit={onSubmit} className="form-card">
            <label>
              名前
              <input value={name} onChange={(e) => setName(e.target.value)} />
            </label>
            <label>
              URL / HOME
              <input value={url} onChange={(e) => setUrl(e.target.value)} />
            </label>
            <label>
              タイトル
              <input value={title} onChange={(e) => setTitle(e.target.value)} />
            </label>
            <label>
              本文
              <textarea value={message} onChange={(e) => setMessage(e.target.value)} rows={6} />
            </label>
            {!isReply && settings.config.gdgdEnabled && (
              <>
                <label className="checkbox-field">
                  <input type="checkbox" checked={gdgd} onChange={(e) => setGdgd(e.target.checked)} />
                  {settings.config.gdgdLabel}
                </label>
              </>
            )}
            {!isReply && (
              <label>
                画像置換 (任意)
                <input type="file" accept="image/*" onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
              </label>
            )}
            <div className="button-row">
              <button type="submit">更新する</button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
};

export default EditPostPage;
