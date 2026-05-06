import { useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { api, mediaUrl } from '../api';
import { NewPostData, Post, ThreadResponse } from '../types';
import LinkedText from '../components/LinkedText';
import { replyTextClassName } from '../components/ThreadList';

const EEJAIKA_OPTIONS = [
  'お美事にございまする',
  'いい仕事してますねぇ',
  'ええじゃないか',
];

const ThreadPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const shouldFocusEejanaika = searchParams.get('mode') === 'eejanaika';
  const [threadData, setThreadData] = useState<ThreadResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [replyName, setReplyName] = useState('');
  const [replyUrl, setReplyUrl] = useState('');
  const [replyMessage, setReplyMessage] = useState('');
  const [replyPassword, setReplyPassword] = useState('');
  const [eejanaikaName, setEejanaikaName] = useState('');
  const [eejanaikaMessage, setEejanaikaMessage] = useState(EEJAIKA_OPTIONS[2]);
  const [replyStatus, setReplyStatus] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    api.getThread(id)
      .then((data) => {
        setThreadData(data);
        setError(null);
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [id]);

  useEffect(() => {
    if (shouldFocusEejanaika) {
      document.getElementById('eejanaika-form')?.scrollIntoView({ block: 'center' });
    }
  }, [shouldFocusEejanaika, threadData]);

  const submitReply = async (payload: NewPostData) => {
    if (!id) return;
    setReplyStatus('返信を投稿中...');
    setError(null);

    try {
      await api.createPost(payload);
      navigate(`/#post-${id}`);
    } catch (err: any) {
      setError(err.message);
      setReplyStatus(null);
    }
  };

  const onReplySubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!id) return;

    if (!replyName || !replyMessage || !replyPassword) {
      setError('返信には名前、本文、パスワードが必要です。');
      return;
    }

    await submitReply({
      thread_id: Number(id),
      parent_id: Number(id),
      name: replyName,
      url: replyUrl,
      title: `Re: ${threadData?.thread?.title || '返信'}`,
      message: replyMessage,
      password: replyPassword,
    });
  };

  const onEejanaikaSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!id) return;

    if (!eejanaikaName) {
      setError('ええじゃないか投稿には名前が必要です。');
      return;
    }

    await submitReply({
      thread_id: Number(id),
      parent_id: Number(id),
      name: eejanaikaName,
      title: `Re: ${threadData?.thread?.title || '返信'}`,
      message: eejanaikaMessage,
      password: 'eejanaika',
    });
  };

  const thread = threadData?.thread;
  const replies = threadData?.replies ?? [];

  return (
    <div className="thread-detail-page">
      {loading && <div className="board-message">読み込み中...</div>}
      {error && <div className="board-message error">エラー: {error}</div>}

      {threadData && (
        <>
          {thread ? (
            <article id={`post-${thread.id}`} className={threadClassName(thread)}>
              <header className="board-thread-title">
                [No・{thread.display_no ?? thread.id}] {thread.title || '無題'}
              </header>

              <div className="board-thread-body">
                <p className="board-meta">
                  NAME：<strong>{thread.name}</strong>
                  {thread.url && <> <a href={thread.url} target="_blank" rel="noreferrer">[HOME]</a></>}
                  {' '}<span className="board-meta-sub">投稿日時：{formatDate(thread.created_at)}</span>
                </p>

                {mediaUrl(thread.image_path) && (
                  <a href={mediaUrl(thread.image_path) ?? undefined} className="board-image-link" target="_blank" rel="noreferrer">
                    <img className="board-post-image" src={mediaUrl(thread.image_path) ?? undefined} alt={thread.title || '投稿画像'} />
                  </a>
                )}

                <div className="board-message-text">
                  <LinkedText text={thread.message} />
                </div>

                {replies.length === 0 && (
                  <div className="board-reply">
                    <div className="board-reply-text">返信はありません。</div>
                  </div>
                )}

                {replies.map((reply: Post) => (
                  <section key={reply.id} className="board-reply">
                    <p className="board-meta">
                      NAME：<strong>{reply.name}</strong>
                      {reply.url && <> <a href={reply.url} target="_blank" rel="noreferrer">[HOME]</a></>}
                      {' '}<span className="board-meta-sub">- {formatDate(reply.created_at)}</span>
                      {reply.reply_no && <> <span className="board-meta-sub">/ 返信No.{thread.display_no ?? thread.id}-{reply.reply_no}</span></>}
                    </p>
                    <div className={replyTextClassName(reply.message)}>
                      <LinkedText text={reply.message} />
                    </div>
                  </section>
                ))}
              </div>
            </article>
          ) : (
            <div className="board-message">スレッドが見つかりません。</div>
          )}

          {thread && (
            <div className="thread-detail-forms">
              {replyStatus && <div className="status thread-detail-status">{replyStatus}</div>}

              <form aria-label="返信フォーム" onSubmit={onReplySubmit} className="inline-reply-form thread-detail-form">
                <h3>返信を書く</h3>
                <label>
                  名前
                  <input value={replyName} onChange={(e) => setReplyName(e.target.value)} />
                </label>
                <label>
                  URL / HOME
                  <input value={replyUrl} onChange={(e) => setReplyUrl(e.target.value)} placeholder="https://example.com" />
                </label>
                <label>
                  本文
                  <textarea value={replyMessage} onChange={(e) => setReplyMessage(e.target.value)} rows={5} />
                </label>
                <div className="inline-form-bottom-row">
                  <label>
                    パスワード
                    <input type="password" value={replyPassword} onChange={(e) => setReplyPassword(e.target.value)} />
                  </label>
                  <button type="submit" className="post-submit-button">返信を投稿</button>
                </div>
              </form>

              <form id="eejanaika-form" aria-label="ええじゃないかフォーム" onSubmit={onEejanaikaSubmit} className="inline-eejanaika-form thread-detail-form">
                <h3>No.{thread.display_no ?? thread.id}へのええじゃないか</h3>
                <label>
                  名前
                  <input value={eejanaikaName} onChange={(e) => setEejanaikaName(e.target.value)} />
                </label>
                <div className="eejanaika-options">
                  {EEJAIKA_OPTIONS.map((option) => (
                    <label key={option} className={replyTextClassName(option)}>
                      <input
                        type="radio"
                        name="eejanaika"
                        value={option}
                        checked={eejanaikaMessage === option}
                        onChange={() => setEejanaikaMessage(option)}
                      />
                      {option}
                    </label>
                  ))}
                </div>
                <div className="inline-form-bottom-row">
                  <span aria-hidden="true" />
                  <button type="submit" className="post-submit-button">送信</button>
                </div>
              </form>
            </div>
          )}
        </>
      )}
    </div>
  );
};

function threadClassName(thread: Post): string {
  const classes = ['board-thread'];
  if (thread.gdgd) {
    classes.push('board-thread-gdgd');
  }
  if (thread.tweet_off) {
    classes.push('board-thread-tweet-off');
  }
  return classes.join(' ');
}

function formatDate(value: string): string {
  return new Date(value).toLocaleString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export default ThreadPage;
