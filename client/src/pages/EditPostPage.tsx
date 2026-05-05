import { FormEvent, useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { api } from '../api';
import { Post } from '../types';
import { countTweetLength, createTweetText, TWEET_LIMIT } from '../tweet';

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
  const [tweetOff, setTweetOff] = useState(false);
  const [tweetUrl, setTweetUrl] = useState('');
  const [tweetLikeCount, setTweetLikeCount] = useState(0);
  const [tweetRetweetCount, setTweetRetweetCount] = useState(0);
  const [tweetCommentCount, setTweetCommentCount] = useState(0);
  const [tweetImpressionCount, setTweetImpressionCount] = useState(0);
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
        setTweetOff(Boolean(data.tweet_off));
        setTweetUrl(data.tweet_url ?? '');
        setTweetLikeCount(data.tweet_like_count ?? 0);
        setTweetRetweetCount(data.tweet_retweet_count ?? 0);
        setTweetCommentCount(data.tweet_comment_count ?? 0);
        setTweetImpressionCount(data.tweet_impression_count ?? 0);
      })
      .catch((err) => setError(err.message));
  }, [id]);

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
    if (!isReply) {
      formData.append('gdgd', gdgd ? '1' : '0');
      formData.append('tweet_off', tweetOff ? '1' : '0');
      formData.append('tweet_url', tweetUrl);
      formData.append('tweet_like_count', String(tweetLikeCount));
      formData.append('tweet_retweet_count', String(tweetRetweetCount));
      formData.append('tweet_comment_count', String(tweetCommentCount));
      formData.append('tweet_impression_count', String(tweetImpressionCount));
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

  const tweetText = tweetOff ? '' : createTweetText(name, title, message);
  const tweetLength = countTweetLength(tweetText);

  return (
    <div>
      <div className="card">
        <h1>投稿編集</h1>
        {error && <div className="error">エラー: {error}</div>}
        {status && <div className="status">{status}</div>}
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
            {!isReply && (
              <>
                <label className="checkbox-field">
                  <input type="checkbox" checked={gdgd} onChange={(e) => setGdgd(e.target.checked)} />
                  gdgd投稿
                </label>
                <label className="checkbox-field">
                  <input type="checkbox" checked={tweetOff} onChange={(e) => setTweetOff(e.target.checked)} />
                  Tweet OFF
                </label>
                {!tweetOff && (
                  <div className="tweet-preview">
                    <div className={tweetLength > TWEET_LIMIT ? 'error' : 'status'}>
                      Tweet文言 ({tweetLength}/{TWEET_LIMIT})
                    </div>
                    <pre>{tweetText}</pre>
                  </div>
                )}
                <label>
                  Tweet先 URL
                  <input value={tweetUrl} onChange={(e) => setTweetUrl(e.target.value)} />
                </label>
                <div className="stats-grid">
                  <label>
                    いいね数
                    <input type="number" min="0" value={tweetLikeCount} onChange={(e) => setTweetLikeCount(Number(e.target.value))} />
                  </label>
                  <label>
                    リツイート数
                    <input type="number" min="0" value={tweetRetweetCount} onChange={(e) => setTweetRetweetCount(Number(e.target.value))} />
                  </label>
                  <label>
                    コメント数
                    <input type="number" min="0" value={tweetCommentCount} onChange={(e) => setTweetCommentCount(Number(e.target.value))} />
                  </label>
                  <label>
                    インプレッション数
                    <input type="number" min="0" value={tweetImpressionCount} onChange={(e) => setTweetImpressionCount(Number(e.target.value))} />
                  </label>
                </div>
              </>
            )}
            <label>
              パスワード
              <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
            </label>
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
