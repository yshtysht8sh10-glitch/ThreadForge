import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api, DEFAULT_PUBLIC_SETTINGS, PublicSettings } from '../api';
import { NewPostData } from '../types';
import { countTweetLength, createTweetText, TWEET_LIMIT } from '../tweet';

const PostFormPage = () => {
  const navigate = useNavigate();
  const [name, setName] = useState('');
  const [url, setUrl] = useState('');
  const [title, setTitle] = useState('');
  const [message, setMessage] = useState('');
  const [gdgd, setGdgd] = useState(false);
  const [tweetOff, setTweetOff] = useState(false);
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
      tweet_off: settings.config.tweetEnabled ? tweetOff : true,
    };

    try {
      const result = await api.createPost(payload);
      if (result.success) {
        setStatus('投稿が完了しました。トップページに戻ります。');
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

  const tweetText = tweetOff ? '' : createTweetText(name, title, message);
  const tweetLength = countTweetLength(tweetText);
  const frameClassName = [
    'post-form-page',
    gdgd && settings.config.gdgdEnabled ? 'post-form-gdgd' : '',
    tweetOff && settings.config.tweetEnabled ? 'post-form-tweet-off' : '',
  ].filter(Boolean).join(' ');

  return (
    <div className={frameClassName}>
      <form onSubmit={handleSubmit} className="legacy-post-form">
        <div className="post-form-title">通常投稿</div>

        <div className="post-form-top-row">
          <label>
            名前 (_/30文字)<span className="required">*</span>
            <input value={name} onChange={(event) => setName(event.target.value)} required />
          </label>
          {settings.config.gdgdEnabled && (
            <label className="legacy-checkbox">
              {settings.config.gdgdLabel}
              <input type="checkbox" checked={gdgd} onChange={(event) => setGdgd(event.target.checked)} />
            </label>
          )}
          {settings.config.tweetEnabled && (
            <label className="legacy-checkbox">
              Tweet OFF
              <input type="checkbox" checked={tweetOff} onChange={(event) => setTweetOff(event.target.checked)} />
            </label>
          )}
        </div>

        <label>
          タイトル (_/70文字)<span className="required">*</span>
          <input value={title} onChange={(event) => setTitle(event.target.value)} required />
        </label>

        <label>
          URL
          <input value={url} onChange={(event) => setUrl(event.target.value)} placeholder="http://" />
        </label>

        <label>
          画像アップロード<span className="required">*</span>
          <input type="file" accept="image/png,image/gif" onChange={(event) => setFile(event.target.files?.[0])} />
        </label>

        <label>
          メッセージ (_/100000文字)<span className="required">*</span>
          <textarea value={message} onChange={(event) => setMessage(event.target.value)} required />
        </label>

        {settings.config.tweetEnabled && !tweetOff && (
          <label>
            ツイートされる文言 ({tweetLength}/{TWEET_LIMIT}文字) * この項目は編集できません。
            <pre className="legacy-tweet-preview">{tweetText}</pre>
          </label>
        )}

        <div className="post-form-bottom-row">
          <label>
            パスワード<span className="required">*</span>
            <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required />
          </label>
          <button type="submit" className="post-submit-button" disabled={submitting}>{submitting ? '送信中...' : '送信'}</button>
          <button type="button" className="post-reset-button" onClick={() => navigate('/')}>やり直し</button>
        </div>
      </form>
      <p className="post-form-notes">
        * 受信できる画像の最大データサイズは 5100 KB までです。<br />
        * 画像ファイルには PNG・GIF が使用できます。<br />
        * パスワードは半角英数で8文字まで有効です。<br />
        * Tweet機能は投稿時のみ反映され、編集では更新されません。Tweet先URLは投稿後にシステムが記録します。
      </p>
      {status && <p className="status">{status}</p>}
    </div>
  );
};

export default PostFormPage;
