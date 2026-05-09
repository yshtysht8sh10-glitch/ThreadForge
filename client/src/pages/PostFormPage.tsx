import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api, DEFAULT_PUBLIC_SETTINGS, PublicSettings } from '../api';
import { NewPostData } from '../types';
import { createSocialPostPreviews } from '../tweet';

const PostFormPage = () => {
  const navigate = useNavigate();
  const [name, setName] = useState('');
  const [url, setUrl] = useState('');
  const [title, setTitle] = useState('');
  const [message, setMessage] = useState('');
  const [gdgd, setGdgd] = useState(false);
  const [socialTransferOff, setSocialTransferOff] = useState(false);
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);
  const [password, setPassword] = useState('');
  const [file, setFile] = useState<File | undefined>(undefined);
  const [status, setStatus] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    api.publicSettings()
      .then((response) => response.success && setSettings(response.settings))
      .catch(() => setSettings(DEFAULT_PUBLIC_SETTINGS));
  }, []);

  const enabledSocialPlatforms = {
    x: settings.config.tweetEnabled,
    bluesky: settings.config.blueskyEnabled,
    mastodon: settings.config.mastodonEnabled,
    misskey: settings.config.misskeyEnabled,
  };
  const socialEnabled = Object.values(enabledSocialPlatforms).some(Boolean);
  const socialPreviews = socialTransferOff ? [] : createSocialPostPreviews(enabledSocialPlatforms, name, title, message);
  const hasInput = [name, url, title, message, password].some((value) => value.trim() !== '') || gdgd || socialTransferOff || file !== undefined;

  const handleClose = () => {
    if (!hasInput || window.confirm('入力内容は破棄されます。一覧画面に戻りますか？')) {
      navigate('/');
    }
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setSubmitting(true);
    setStatus(null);

    const payload: NewPostData = {
      name,
      url,
      title,
      message,
      password,
      file,
      gdgd: settings.config.gdgdEnabled ? gdgd : false,
      tweet_off: socialEnabled ? socialTransferOff : true,
    };

    try {
      const result = await api.createPost(payload);
      if (result.success) {
        setStatus('投稿が完了しました。一覧画面に戻ります。');
        setTimeout(() => navigate('/'), 1000);
      } else {
        setStatus(`エラー: ${result.message}`);
      }
    } catch (error) {
      setStatus(`投稿中にエラーが発生しました: ${error}`);
    } finally {
      setSubmitting(false);
    }
  };

  const frameClassName = [
    'post-form-page',
    gdgd && settings.config.gdgdEnabled ? 'post-form-gdgd' : '',
  ].filter(Boolean).join(' ');

  return (
    <div className={frameClassName}>
      <form onSubmit={handleSubmit} className="legacy-post-form">
        <div className="post-form-title">
          <span>通常投稿</span>
          <button type="button" className="post-form-close-button" onClick={handleClose} aria-label="投稿フォームを閉じる">×</button>
        </div>

        <div className="post-form-top-row">
          <label>
            <span className="post-form-label-title">名前（/30文字）<span className="required">*</span></span>
            <input value={name} onChange={(event) => setName(event.target.value)} required />
          </label>
          {settings.config.gdgdEnabled && (
            <label className="legacy-checkbox">
              {settings.config.gdgdLabel}
              <input type="checkbox" checked={gdgd} onChange={(event) => setGdgd(event.target.checked)} />
            </label>
          )}
          {socialEnabled && (
            <label className="legacy-checkbox">
              SNS転記OFF
              <input type="checkbox" checked={socialTransferOff} onChange={(event) => setSocialTransferOff(event.target.checked)} />
            </label>
          )}
        </div>

        <label>
          <span className="post-form-label-title">タイトル（/70文字）<span className="required">*</span></span>
          <input value={title} onChange={(event) => setTitle(event.target.value)} required />
        </label>

        <label>
          URL / HOME
          <input value={url} onChange={(event) => setUrl(event.target.value)} placeholder="http://" />
        </label>

        <label>
          <span className="post-form-label-title">画像アップロード<span className="required">*</span></span>
          <input type="file" accept="image/png,image/gif" onChange={(event) => setFile(event.target.files?.[0])} />
          <span className="post-form-field-help">※PNG・GIF が使用できます。最大データサイズは 5100 KB までです。</span>
        </label>

        <label>
          <span className="post-form-label-title">メッセージ（/100000文字）<span className="required">*</span></span>
          <textarea value={message} onChange={(event) => setMessage(event.target.value)} required />
        </label>

        <div className="post-form-bottom-row">
          <label>
            <span className="post-form-label-title">パスワード<span className="required">*</span></span>
            <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required />
            <span className="post-form-field-help">※半角英数字8文字まで有効です。</span>
          </label>
          <button type="submit" className="post-submit-button" disabled={submitting}>{submitting ? '送信中...' : '送信'}</button>
        </div>

        {socialPreviews.length > 0 && (
          <section className="social-transfer-preview" aria-label="SNS投稿のプレビュー">
            <h2>SNS投稿のプレビュー</h2>
            {socialPreviews.map((preview) => (
              <article className="social-transfer-preview-item" key={preview.platform}>
                <h3>
                  {preview.label}
                  <span>{preview.limit ? `${preview.length}/${preview.limit}文字` : `${preview.length}文字`}</span>
                </h3>
                <pre className="legacy-tweet-preview">{preview.text}</pre>
              </article>
            ))}
            <p className="social-transfer-help">※この項目は編集できません。</p>
          </section>
        )}
      </form>
      {status && <p className="status">{status}</p>}
    </div>
  );
};

export default PostFormPage;
