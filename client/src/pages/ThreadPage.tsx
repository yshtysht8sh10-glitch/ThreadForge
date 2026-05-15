import { useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { api, mediaUrl, type PublicSettings } from '../api';
import { NewPostData, Post, ThreadResponse } from '../types';
import LinkedText from '../components/LinkedText';
import { eejanaikaOptionsFromSettings, replyTextClassName, replyTextStyle } from '../components/ThreadList';

const EEJAIKA_OPTIONS = [
  'お美事にございまする',
  'いい仕事してますねぇ',
  'ええじゃないか',
];

const DEFAULT_PUBLIC_SETTINGS: PublicSettings = {
  config: {
    bbsTitle: 'ThreadForge',
    homePageUrl: '/',
    manualTitle: '',
    manualBody: '',
    tweetEnabled: false,
    blueskyEnabled: false,
    mastodonEnabled: false,
    misskeyEnabled: false,
    gdgdEnabled: true,
    gdgdLabel: 'gdgd投稿',
    eejanaikaOmigotoText: 'お美事にございまする',
    eejanaikaOmigotoColor: '#ff72ff',
    eejanaikaGoodjobText: 'いい仕事してますねぇ',
    eejanaikaGoodjobColor: '#27a8ff',
    eejanaikaEejanaikaText: 'ええじゃないか',
    eejanaikaEejanaikaColor: '#fff200',
    socialHashtags: '#ドット絵 #pixelart',
  },
};

const ThreadPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const shouldFocusEejanaika = searchParams.get('mode') === 'eejanaika';
  const [threadData, setThreadData] = useState<ThreadResponse | null>(null);
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [replyName, setReplyName] = useState('Blank');
  const [replyUrl, setReplyUrl] = useState('');
  const [replyMessage, setReplyMessage] = useState('');
  const [replyPassword, setReplyPassword] = useState('');
  const [eejanaikaName, setEejanaikaName] = useState('Blank');
  const [eejanaikaMessage, setEejanaikaMessage] = useState(DEFAULT_PUBLIC_SETTINGS.config.eejanaikaEejanaikaText);
  const [replyStatus, setReplyStatus] = useState<string | null>(null);
  const eejanaikaOptions = eejanaikaOptionsFromSettings(settings.config);

  const loadThread = async () => {
    if (!id) return;
    setLoading(true);
    try {
      const data = await api.getThread(id);
      setThreadData(data);
      setError(null);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadThread();
  }, [id]);

  useEffect(() => {
    if (!api.publicSettings) {
      return;
    }
    api.publicSettings()
      .then((response) => response.success && setSettings(response.settings))
      .catch(() => setSettings(DEFAULT_PUBLIC_SETTINGS));
  }, []);

  useEffect(() => {
    setEejanaikaMessage((current) => {
      const available = eejanaikaOptions.some((option) => option.text === current);
      return available ? current : settings.config.eejanaikaEejanaikaText;
    });
  }, [eejanaikaOptions, settings.config.eejanaikaEejanaikaText]);

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
      await loadThread();
      setReplyStatus('返信を投稿しました。');
      setReplyName('Blank');
      setReplyUrl('');
      setReplyMessage('');
      setReplyPassword('');
      setEejanaikaName('Blank');
      setEejanaikaMessage(settings.config.eejanaikaEejanaikaText);
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
                    <div className={replyTextClassName(reply.message, settings.config)} style={replyTextStyle(reply.message, settings.config)}>
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
                <h3>コメント</h3>
                <label>
                  <span>名前（/30文字）<span className="required" aria-hidden="true">*</span></span>
                  <input aria-label="名前" value={replyName} maxLength={30} onChange={(e) => setReplyName(e.target.value)} required />
                </label>
                <label>
                  URL / HOME
                  <input value={replyUrl} onChange={(e) => setReplyUrl(e.target.value)} placeholder="https://example.com" />
                </label>
                <label>
                  <span>本文（/100000文字）<span className="required" aria-hidden="true">*</span></span>
                  <textarea aria-label="本文" value={replyMessage} maxLength={100000} onChange={(e) => setReplyMessage(e.target.value)} rows={5} required />
                </label>
                <div className="inline-form-bottom-row">
                  <label>
                    <span>パスワード<span className="required" aria-hidden="true">*</span></span>
                    <input aria-label="パスワード" type="password" value={replyPassword} maxLength={8} onChange={(e) => setReplyPassword(e.target.value)} required />
                    <span className="inline-form-field-help">※半角英数字8文字まで有効です。</span>
                  </label>
                  <button type="submit" className="post-submit-button">送信</button>
                </div>
              </form>

              <form id="eejanaika-form" aria-label="ええじゃないかフォーム" onSubmit={onEejanaikaSubmit} className="inline-eejanaika-form thread-detail-form">
                <h3>ええじゃないか</h3>
                <label>
                  <span>名前（/30文字）<span className="required" aria-hidden="true">*</span></span>
                  <input aria-label="名前" value={eejanaikaName} maxLength={30} onChange={(e) => setEejanaikaName(e.target.value)} required />
                </label>
                <div className="eejanaika-options">
                  {eejanaikaOptions.map((option) => (
                    <label key={option.key} className={replyTextClassName(option.text, settings.config)} style={{ color: option.color }}>
                      <input
                        type="radio"
                        name="eejanaika"
                        value={option.text}
                        checked={eejanaikaMessage === option.text}
                        onChange={() => setEejanaikaMessage(option.text)}
                      />
                      {option.text}
                    </label>
                  ))}
                </div>
                <div className="inline-form-bottom-row">
                  <span aria-hidden="true" />
                  <button type="submit" className="post-submit-button">送信</button>
                </div>
              </form>
              <div className="thread-detail-back-row">
                <button type="button" className="thread-detail-back-button" onClick={() => navigate('/')}>一覧に戻る</button>
              </div>
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
