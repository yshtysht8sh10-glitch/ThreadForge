import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, DEFAULT_PUBLIC_SETTINGS, mediaUrl, PublicSettings } from '../api';
import { NewPostData, Post } from '../types';
import LinkedText from './LinkedText';

type ThreadListProps = {
  threads: Post[];
  action?: (post: Post) => React.ReactNode;
};

type InlineMode = 'comment' | 'eejanaika';

const EEJAIKA_OPTIONS = [
  'お美事にございまする',
  'いい仕事してますねぇ',
  'ええじゃないか',
];

const ThreadList = ({ threads, action }: ThreadListProps) => {
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);
  const [activePanel, setActivePanel] = useState<{ threadId: number; mode: InlineMode } | null>(null);
  const [expandedReplies, setExpandedReplies] = useState<Record<number, Post[]>>({});
  const [replyName, setReplyName] = useState('');
  const [replyUrl, setReplyUrl] = useState('');
  const [replyMessage, setReplyMessage] = useState('');
  const [replyPassword, setReplyPassword] = useState('');
  const [eejanaikaName, setEejanaikaName] = useState('');
  const [eejanaikaMessage, setEejanaikaMessage] = useState(EEJAIKA_OPTIONS[2]);
  const [inlineStatus, setInlineStatus] = useState<Record<number, string>>({});

  useEffect(() => {
    api.publicSettings()
      .then((response) => response.success && setSettings(response.settings))
      .catch(() => setSettings(DEFAULT_PUBLIC_SETTINGS));
  }, []);

  const loadFullReplies = async (threadId: number) => {
    const response = await api.getThread(String(threadId));
    setExpandedReplies((current) => ({
      ...current,
      [threadId]: response.replies,
    }));
  };

  const openPanel = (threadId: number, mode: InlineMode) => {
    setInlineStatus((current) => ({ ...current, [threadId]: '' }));
    setActivePanel((current) => (
      current?.threadId === threadId && current.mode === mode ? null : { threadId, mode }
    ));
  };

  const submitReply = async (thread: Post, payload: NewPostData) => {
    setInlineStatus((current) => ({ ...current, [thread.id]: '返信を投稿中...' }));
    await api.createPost(payload);
    await loadFullReplies(thread.id);
    setInlineStatus((current) => ({ ...current, [thread.id]: '返信を投稿しました。' }));
    setReplyName('');
    setReplyUrl('');
    setReplyMessage('');
    setReplyPassword('');
    setEejanaikaName('');
    setEejanaikaMessage(EEJAIKA_OPTIONS[2]);
    setActivePanel(null);
  };

  const onCommentSubmit = async (event: React.FormEvent, thread: Post) => {
    event.preventDefault();
    if (!replyName || !replyMessage || !replyPassword) {
      setInlineStatus((current) => ({ ...current, [thread.id]: '名前、本文、パスワードを入力してください。' }));
      return;
    }

    await submitReply(thread, {
      thread_id: thread.id,
      parent_id: thread.id,
      name: replyName,
      url: replyUrl,
      title: `Re: ${thread.title || '返信'}`,
      message: replyMessage,
      password: replyPassword,
    });
  };

  const onEejanaikaSubmit = async (event: React.FormEvent, thread: Post) => {
    event.preventDefault();
    if (!eejanaikaName) {
      setInlineStatus((current) => ({ ...current, [thread.id]: '名前を入力してください。' }));
      return;
    }

    await submitReply(thread, {
      thread_id: thread.id,
      parent_id: thread.id,
      name: eejanaikaName,
      title: `Re: ${thread.title || '返信'}`,
      message: eejanaikaMessage,
      password: 'eejanaika',
    });
  };

  return (
    <div className="thread-list">
      {threads.length === 0 && <div className="board-message">投稿はまだありません。</div>}
      {threads.map((thread) => {
        const replies = expandedReplies[thread.id] ?? (thread.replies ?? []).slice(0, 10);
        const hiddenReplyCount = Math.max(0, Number(thread.reply_count ?? 0) - replies.length);
        const panelMode = activePanel?.threadId === thread.id ? activePanel.mode : null;

        return (
          <article key={thread.id} id={`post-${thread.id}`} className={threadClassName(thread)}>
            <header className="board-thread-title">
              <Link to={`/thread/${thread.id}`}>[No・{thread.display_no ?? thread.id}] {thread.title || '無題'}</Link>
            </header>

            <div className="board-thread-body">
              <p className="board-meta">
                NAME：<strong>{thread.name}</strong>
                {thread.url && <> <a href={thread.url} target="_blank" rel="noreferrer">[HOME]</a></>}
                {' '}投稿日時：{formatDate(thread.created_at)}
              </p>

              {mediaUrl(thread.image_path) && (
                <Link to={`/thread/${thread.id}`} className="board-image-link">
                  <img className="board-post-image" src={mediaUrl(thread.image_path) ?? undefined} alt={thread.title || '投稿画像'} />
                </Link>
              )}

              <div className="board-message-text">
                <LinkedText text={thread.message} />
              </div>

              {replies.map((reply) => (
                <section key={reply.id} className="board-reply">
                  <p className="board-meta">
                    NAME：<strong>{reply.name}</strong>
                    {reply.url && <> <a href={reply.url} target="_blank" rel="noreferrer">[HOME]</a></>}
                    {' '} - {formatDate(reply.created_at)}
                    {reply.reply_no && <> / 返信No.{thread.display_no ?? thread.id}-{reply.reply_no}</>}
                  </p>
                  <div className={replyTextClassName(reply.message)}>
                    <LinkedText text={reply.message} />
                  </div>
                </section>
              ))}

              {inlineStatus[thread.id] && <p className="status inline-status">{inlineStatus[thread.id]}</p>}

              {panelMode === 'comment' && (
                <form className="inline-reply-form" onSubmit={(event) => onCommentSubmit(event, thread)}>
                  <h3>コメント</h3>
                  <label>
                    名前
                    <input value={replyName} onChange={(event) => setReplyName(event.target.value)} />
                  </label>
                  <label>
                    URL / HOME
                    <input value={replyUrl} onChange={(event) => setReplyUrl(event.target.value)} placeholder="https://example.com" />
                  </label>
                  <label>
                    本文
                    <textarea value={replyMessage} onChange={(event) => setReplyMessage(event.target.value)} rows={4} />
                  </label>
                  <div className="inline-form-bottom-row">
                    <label>
                      パスワード
                      <input type="password" value={replyPassword} onChange={(event) => setReplyPassword(event.target.value)} />
                    </label>
                    <button type="submit" className="post-submit-button">送信</button>
                  </div>
                </form>
              )}

              {panelMode === 'eejanaika' && (
                <form className="inline-eejanaika-form" onSubmit={(event) => onEejanaikaSubmit(event, thread)}>
                  <h3>No.{thread.display_no ?? thread.id}へのええじゃないか</h3>
                  <label>
                    名前
                    <input value={eejanaikaName} onChange={(event) => setEejanaikaName(event.target.value)} />
                  </label>
                  <div className="eejanaika-options">
                    {EEJAIKA_OPTIONS.map((option) => (
                      <label key={option} className={replyTextClassName(option)}>
                        <input
                          type="radio"
                          name={`eejanaika-${thread.id}`}
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
              )}

              <footer className="board-thread-actions">
                {settings.config.tweetEnabled && !thread.tweet_off && (
                  <p className="board-social-row">
                    <span>Tweet先：</span>
                    {thread.tweet_url ? (
                      <a className="board-tweet-link" href={thread.tweet_url} target="_blank" rel="noreferrer" aria-label="Tweet先">
                        ■
                      </a>
                    ) : (
                      <span className="board-tweet-placeholder" aria-label="Tweet先未登録">■</span>
                    )}
                  </p>
                )}
                <div className="board-action-group">
                  {action ? action(thread) : (
                    <>
                      <button type="button" className="board-action-button" onClick={() => openPanel(thread.id, 'comment')}>コメント</button>
                      <button type="button" className="board-action-button" onClick={() => openPanel(thread.id, 'eejanaika')}>ええじゃないか</button>
                    </>
                  )}
                  {hiddenReplyCount > 0 && (
                    <button type="button" className="board-more-link" onClick={() => loadFullReplies(thread.id)}>
                      ほか{hiddenReplyCount}件の返信
                    </button>
                  )}
                </div>
              </footer>
            </div>
          </article>
        );
      })}
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

export function replyTextClassName(message: string): string {
  const classes = ['board-reply-text'];
  if (message === 'お美事にございまする') {
    classes.push('eejanaika-reply-omigoto');
  } else if (message === 'いい仕事してますねぇ') {
    classes.push('eejanaika-reply-goodjob');
  } else if (message === 'ええじゃないか') {
    classes.push('eejanaika-reply-eejanaika');
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

export default ThreadList;
