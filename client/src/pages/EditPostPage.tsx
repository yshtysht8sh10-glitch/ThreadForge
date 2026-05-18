import { FormEvent, useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { api, DEFAULT_PUBLIC_SETTINGS, mediaUrl, PublicSettings } from '../api';
import { Post } from '../types';
import { useAuth } from '../auth';
import { clampUserNameSuffix, composeUserName, userNameSuffixLimit } from '../name';

const EditPostPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const { token, user } = useAuth();
  const [post, setPost] = useState<Post | null>(null);
  const [name, setName] = useState('');
  const [nameSuffix, setNameSuffix] = useState('');
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
  const [filePreviewUrl, setFilePreviewUrl] = useState<string | null>(null);
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

  useEffect(() => {
    if (!user) return;
    setUrl(user.home_url ?? '');
    setPassword((current) => current || user.post_password);
  }, [user]);

  useEffect(() => {
    if (!user || !post) return;
    const prefix = `${user.display_name}@`;
    if (post.name === user.display_name) {
      setNameSuffix('');
    } else if (post.name.startsWith(prefix)) {
      setNameSuffix(clampUserNameSuffix(user.display_name, post.name.slice(prefix.length)));
    }
  }, [post, user]);

  const nameSuffixLimit = user ? userNameSuffixLimit(user.display_name) : 0;
  const displayName = user ? composeUserName(user.display_name, nameSuffix) : name;
  const imagePreviewUrl = filePreviewUrl ?? mediaUrl(post?.image_path);
  const cardClassName = [
    'card',
    'edit-post-card',
    isReply ? 'edit-post-card-reply' : '',
    !isReply && gdgd && settings.config.gdgdEnabled ? 'edit-post-card-gdgd' : '',
  ].filter(Boolean).join(' ');

  useEffect(() => {
    if (!file) {
      setFilePreviewUrl(null);
      return;
    }
    const objectUrl = URL.createObjectURL(file);
    setFilePreviewUrl(objectUrl);
    return () => URL.revokeObjectURL(objectUrl);
  }, [file]);

  const onSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!id) return;
    if (!password.trim()) {
      setError('パスワードを入力してください。');
      return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('name', displayName);
    formData.append('url', url);
    formData.append('title', title);
    formData.append('message', message);
    formData.append('password', password);
    if (token) {
      formData.append('auth_token', token);
    }
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
      <div className={cardClassName}>
        <h1>投稿編集</h1>
        {error && <div className="error">エラー: {error}</div>}
        {status && <div className="status">{status}</div>}
        {!password.trim() && <div className="error">編集モードから投稿を選択してください。</div>}
        {!post && !error && <p>投稿を読み込み中...</p>}
        {post && (
          <form onSubmit={onSubmit} className="form-card">
            {user ? (
              <label>
                ひとこと（任意 / {nameSuffixLimit}文字まで）
                <input
                  value={nameSuffix}
                  maxLength={nameSuffixLimit}
                  onChange={(e) => setNameSuffix(clampUserNameSuffix(user.display_name, e.target.value))}
                  placeholder="NAMEに @付きで表示"
                />
                <span className="post-form-field-help">NAME: {displayName}（名前+@+ひとことで30文字まで）</span>
              </label>
            ) : (
              <label>
                <span className="post-form-label-title">名前<span className="required">*</span></span>
                <input value={name} maxLength={30} onChange={(e) => setName(e.target.value)} required />
              </label>
            )}
            <label>
              URL / HOME
              <input value={url} onChange={(e) => setUrl(e.target.value)} />
            </label>
            <label>
              <span className="post-form-label-title">タイトル<span className="required">*</span></span>
              <input value={title} onChange={(e) => setTitle(e.target.value)} required />
            </label>
            {!isReply && (
              <div className="edit-image-replace-block">
                <span className="post-form-label-title">画像置換 (任意)</span>
                {imagePreviewUrl && (
                  <section className="edit-image-preview" aria-label="投稿画像のプレビュー">
                    <img src={imagePreviewUrl} alt={title || '投稿画像'} />
                  </section>
                )}
                <input type="file" accept="image/*" onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
              </div>
            )}
            <label>
              <span className="post-form-label-title">本文<span className="required">*</span></span>
              <textarea value={message} onChange={(e) => setMessage(e.target.value)} rows={6} required />
            </label>
            {!isReply && settings.config.gdgdEnabled && (
              <label className="checkbox-field">
                <input type="checkbox" checked={gdgd} onChange={(e) => setGdgd(e.target.checked)} />
                {settings.config.gdgdLabel}
              </label>
            )}
            {!isReply && (
              <p className="post-form-field-help edit-social-note">
                <span>※</span>
                <span>
                  投稿内容を編集してもSNS側に転記された内容に対しては変更は反映されません。<br />
                  もしどうしても反映させたい場合は、投稿を削除し(SNS側も削除されます)、もう一度投稿してください。
                </span>
              </p>
            )}
            <div className="button-row align-right">
              <button type="submit">更新する</button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
};

export default EditPostPage;
