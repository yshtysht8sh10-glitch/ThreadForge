import { useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { api, mediaUrl } from '../api';
import { NewPostData, Post, ThreadResponse } from '../types';
import LinkedText from '../components/LinkedText';

const EEJAIKA_OPTIONS = [
  'お美事にございまする',
  'いい仕事してますねぇ',
  'ええじゃないか',
];

const ThreadPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const isEejanaikaMode = searchParams.get('mode') === 'eejanaika';
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
      .then((data) => setThreadData(data))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [id]);

  useEffect(() => {
    if (isEejanaikaMode) {
      document.getElementById('eejanaika-form')?.scrollIntoView({ block: 'center' });
    }
  }, [isEejanaikaMode, threadData]);

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
      setError('返信には名前・本文・パスワードが必要です。');
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

  return (
    <div>
      {loading && <div className="card">読み込み中...</div>}
      {error && <div className="card">エラー: {error}</div>}
      {threadData && (
        <>
          {threadData.thread ? (
            <div className="card">
              <h1>{threadData.thread.title || '無題'}</h1>
              <p><LinkedText text={threadData.thread.message} /></p>
              {threadData.thread.image_path && (
                <img className="post-image" src={mediaUrl(threadData.thread.image_path) ?? undefined} alt={threadData.thread.title || '投稿画像'} />
              )}
              <p>
                投稿者: <strong>{threadData.thread.name}</strong>
                {threadData.thread.url && <> ・ <a href={threadData.thread.url} target="_blank" rel="noreferrer">HOME</a></>}
                {' ・ '}{new Date(threadData.thread.created_at).toLocaleString()}
              </p>
            </div>
          ) : (
            <div className="card">スレッドが見つかりません。</div>
          )}

          {!isEejanaikaMode && (
            <div className="card">
              <h2>返信</h2>
              {threadData.replies.length === 0 && <p>返信はありません。</p>}
              {threadData.replies.map((reply: Post) => (
                <div key={reply.id} className="card" style={{ marginBottom: '0.75rem' }}>
                  <p className={replyTextClassName(reply.message)}><LinkedText text={reply.message} /></p>
                  <p>
                    <strong>{reply.name}</strong>
                    {reply.url && <> ・ <a href={reply.url} target="_blank" rel="noreferrer">HOME</a></>}
                    {' ・ '}{new Date(reply.created_at).toLocaleString()}
                    {reply.reply_no && <> ・ 返信No.{threadData.thread?.display_no ?? threadData.thread?.id}-{reply.reply_no}</>}
                  </p>
                </div>
              ))}
            </div>
          )}

          {isEejanaikaMode ? (
            <div id="eejanaika-form" className="eejanaika-panel">
              <form onSubmit={onEejanaikaSubmit} className="eejanaika-form">
                <div className="post-form-mode">+ No.{id}へのええじゃないか +</div>
                <label>
                  名前 (_/30文字)
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
                <button type="submit">送 信</button>
              </form>
            </div>
          ) : (
            <div className="card">
              <h2>返信を書く</h2>
              {replyStatus && <div className="status">{replyStatus}</div>}
              <form onSubmit={onReplySubmit} className="form-card">
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
                  <textarea value={replyMessage} onChange={(e) => setReplyMessage(e.target.value)} rows={4} />
                </label>
                <label>
                  パスワード
                  <input type="password" value={replyPassword} onChange={(e) => setReplyPassword(e.target.value)} />
                </label>
                <button type="submit">返信を投稿</button>
              </form>
            </div>
          )}
        </>
      )}
    </div>
  );
};

function replyTextClassName(message: string): string {
  const classes = ['eejanaika-reply-text'];
  if (message === 'お美事にございまする') {
    classes.push('eejanaika-reply-omigoto');
  } else if (message === 'いい仕事してますねぇ') {
    classes.push('eejanaika-reply-goodjob');
  } else if (message === 'ええじゃないか') {
    classes.push('eejanaika-reply-eejanaika');
  }
  return classes.join(' ');
}

export default ThreadPage;
