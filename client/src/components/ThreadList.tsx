import { CSSProperties, useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, mediaUrl, type PublicSettings } from '../api';
import { BoardReactions, NewPostData, Post } from '../types';
import LinkedText from './LinkedText';
import { useAuth } from '../auth';

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

const DEFAULT_REPLY_NAME = 'Blank';
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

const ThreadList = ({ threads, action }: ThreadListProps) => {
  const { token, user } = useAuth();
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);
  const viewedPostIds = useRef<Set<number>>(new Set());
  const [activePanel, setActivePanel] = useState<{ threadId: number; mode: InlineMode } | null>(null);
  const [expandedReplies, setExpandedReplies] = useState<Record<number, Post[]>>({});
  const [replyName, setReplyName] = useState(DEFAULT_REPLY_NAME);
  const [replyUrl, setReplyUrl] = useState('');
  const [replyMessage, setReplyMessage] = useState('');
  const [replyPassword, setReplyPassword] = useState('');
  const [eejanaikaName, setEejanaikaName] = useState(DEFAULT_REPLY_NAME);
  const [eejanaikaMessage, setEejanaikaMessage] = useState(DEFAULT_PUBLIC_SETTINGS.config.eejanaikaEejanaikaText);
  const [inlineStatus, setInlineStatus] = useState<Record<number, string>>({});
  const [boardReactionOverrides, setBoardReactionOverrides] = useState<Record<number, BoardReactions>>({});
  const eejanaikaOptions = eejanaikaOptionsFromSettings(settings.config);

  useEffect(() => {
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
    if (!user) {
      return;
    }
    setReplyName(user.display_name || DEFAULT_REPLY_NAME);
    setReplyUrl(user.home_url ?? '');
    setReplyPassword(user.post_password ?? '');
    setEejanaikaName(user.display_name || DEFAULT_REPLY_NAME);
  }, [user]);

  useEffect(() => {
    if (!('IntersectionObserver' in window)) {
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const id = Number((entry.target as HTMLElement).dataset.postId);
        if (!id || viewedPostIds.current.has(id)) return;
        viewedPostIds.current.add(id);
        api.recordPostView(id).catch(() => undefined);
      });
    }, { threshold: 0.35 });

    threads.forEach((thread) => {
      const element = document.querySelector<HTMLElement>(`[data-post-id="${thread.id}"]`);
      if (element) {
        observer.observe(element);
      }
    });

    return () => observer.disconnect();
  }, [threads]);

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

  const resetReplyDraft = () => {
    setReplyName(DEFAULT_REPLY_NAME);
    setReplyUrl('');
    setReplyMessage('');
    setReplyPassword('');
  };

  const resetEejanaikaDraft = () => {
    setEejanaikaName(DEFAULT_REPLY_NAME);
    setEejanaikaMessage(settings.config.eejanaikaEejanaikaText);
  };

  const closePanel = (mode: InlineMode) => {
    const hasDraft = mode === 'comment'
      ? [replyUrl, replyMessage, replyPassword].some((value) => value.trim() !== '') || replyName.trim() !== DEFAULT_REPLY_NAME
      : eejanaikaName.trim() !== DEFAULT_REPLY_NAME || eejanaikaMessage !== settings.config.eejanaikaEejanaikaText;
    if (hasDraft && !window.confirm('入力内容は破棄されます。閉じますか？')) {
      return;
    }
    if (mode === 'comment') {
      resetReplyDraft();
    } else {
      resetEejanaikaDraft();
    }
    setActivePanel(null);
  };

  const submitReply = async (thread: Post, payload: NewPostData) => {
    setInlineStatus((current) => ({ ...current, [thread.id]: '返信を投稿中...' }));
    await api.createPost(payload);
    await loadFullReplies(thread.id);
    updateBoardReactionSummary(thread, payload.message);
    setInlineStatus((current) => ({ ...current, [thread.id]: '返信を投稿しました。' }));
    resetReplyDraft();
    resetEejanaikaDraft();
    setActivePanel(null);
  };

  const updateBoardReactionSummary = (thread: Post, message: string) => {
    const matched = eejanaikaOptions.find((option) => option.text === message);
    if (!matched) {
      return;
    }

    setBoardReactionOverrides((current) => {
      const base = current[thread.id] ?? thread.board_reactions ?? {
        views: thread.view_count ?? 0,
        eejanaika: 0,
        omigoto: 0,
        goodjob: 0,
      };
      const next = { ...base };
      if (matched.key === 'eejanaika') {
        next.eejanaika += 1;
      } else if (matched.key === 'omigoto') {
        next.omigoto += 1;
      } else {
        next.goodjob += 1;
      }
      return { ...current, [thread.id]: next };
    });
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
      auth_token: token,
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
      password: user?.post_password || 'eejanaika',
      auth_token: token,
    });
  };

  const quickReaction = async (thread: Post, message: string) => {
    if (!user) return;
    await submitReply(thread, {
      thread_id: thread.id,
      parent_id: thread.id,
      name: user.display_name,
      url: user.home_url ?? '',
      title: `Re: ${thread.title || '返信'}`,
      message,
      password: user.post_password || 'eejanaika',
      auth_token: token,
    });
  };

  return (
    <div className="thread-list">
      {threads.length === 0 && <div className="board-message">投稿はまだありません。</div>}
      {threads.map((thread) => {
        const previewReplies = thread.replies ?? [];
        const replies = expandedReplies[thread.id] ?? previewReplies.slice(0, 10);
        const hiddenReplyCount = Math.max(0, Number(thread.reply_count ?? 0) - replies.length);
        const omittedStart = 1;
        const omittedEnd = hiddenReplyCount;
        const panelMode = activePanel?.threadId === thread.id ? activePanel.mode : null;
        const displayedThread = boardReactionOverrides[thread.id]
          ? { ...thread, board_reactions: boardReactionOverrides[thread.id] }
          : thread;

        return (
          <article key={thread.id} id={`post-${thread.id}`} data-post-id={thread.id} className={threadClassName(thread)}>
            <header className="board-thread-title">
              <Link to={`/thread/${thread.id}`}>[No・{thread.display_no ?? thread.id}] {thread.title || '無題'}</Link>
            </header>

            <div className="board-thread-body">
              <p className="board-meta">
                {thread.user_icon_path && <img className="user-icon" src={mediaUrl(thread.user_icon_path) ?? undefined} alt="" />}
                NAME：<strong>{thread.name}</strong>
                {thread.url && <> <a href={thread.url} target="_blank" rel="noreferrer">[HOME]</a></>}
                {' '}<span className="board-meta-sub">投稿日時：{formatDate(thread.created_at)}</span>
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
                    {reply.user_icon_path && <img className="user-icon" src={mediaUrl(reply.user_icon_path) ?? undefined} alt="" />}
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

              {hiddenReplyCount > 0 && (
                <div className="board-reply-omitted">
                  ※コメント{omittedStart}-{omittedEnd}は省略されています
                  <button type="button" onClick={() => loadFullReplies(thread.id)}>
                    [全て表示]
                  </button>
                </div>
              )}

              {inlineStatus[thread.id] && <p className="status inline-status">{inlineStatus[thread.id]}</p>}

              {panelMode === 'comment' && (
                <form className="inline-reply-form" onSubmit={(event) => onCommentSubmit(event, thread)}>
                  <div className="inline-form-heading">
                    <h3>コメント</h3>
                    <button type="button" className="inline-form-close-button" onClick={() => closePanel('comment')} aria-label="コメントフォームを閉じる">×</button>
                  </div>
                  <label>
                    <span>名前（/30文字）<span className="required" aria-hidden="true">*</span></span>
                    <input aria-label="名前" value={replyName} maxLength={30} onChange={(event) => setReplyName(event.target.value)} required />
                  </label>
                  <label>
                    URL / HOME
                    <input value={replyUrl} onChange={(event) => setReplyUrl(event.target.value)} placeholder="https://example.com" />
                  </label>
                  <label>
                    <span>本文（/100000文字）<span className="required" aria-hidden="true">*</span></span>
                    <textarea aria-label="本文" value={replyMessage} maxLength={100000} onChange={(event) => setReplyMessage(event.target.value)} rows={4} required />
                  </label>
                  <div className="inline-form-bottom-row">
                    <label>
                      <span>パスワード<span className="required" aria-hidden="true">*</span></span>
                      <input aria-label="パスワード" type="password" value={replyPassword} maxLength={8} onChange={(event) => setReplyPassword(event.target.value)} required />
                      <span className="inline-form-field-help">※半角英数字8文字まで有効です。</span>
                    </label>
                    <button type="submit" className="post-submit-button">送信</button>
                  </div>
                </form>
              )}

              {panelMode === 'eejanaika' && (
                <form className="inline-eejanaika-form" onSubmit={(event) => onEejanaikaSubmit(event, thread)}>
                  <div className="inline-form-heading">
                    <h3>ええじゃないか</h3>
                    <button type="button" className="inline-form-close-button" onClick={() => closePanel('eejanaika')} aria-label="ええじゃないかフォームを閉じる">×</button>
                  </div>
                  <label>
                    <span>名前（/30文字）<span className="required" aria-hidden="true">*</span></span>
                    <input aria-label="名前" value={eejanaikaName} maxLength={30} onChange={(event) => setEejanaikaName(event.target.value)} required />
                  </label>
                  <div className="eejanaika-options">
                    {eejanaikaOptions.map((option) => (
                      <label key={option.key} className={replyTextClassName(option.text, settings.config)} style={{ color: option.color }}>
                        <input
                          type="radio"
                          name={`eejanaika-${thread.id}`}
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
              )}

              <footer className="board-thread-actions">
                <SocialRows thread={displayedThread} enabled={settings.config} />
                {!panelMode && (
                  <div className={user && !action ? 'board-action-group board-action-group-quick' : 'board-action-group'}>
                    {action ? action(thread) : user ? (
                      <>
                        <button type="button" className="board-action-button" onClick={() => openPanel(thread.id, 'comment')}>コメント</button>
                        <span className="quick-reaction-stack">
                          {eejanaikaOptions.map((option) => (
                            <button
                              type="button"
                              className="board-action-button quick-reaction-button"
                              key={option.key}
                              title={option.text}
                              style={{ color: option.color }}
                              onClick={() => quickReaction(thread, option.text)}
                            >
                              {shortReactionLabel(option.text)}
                            </button>
                          ))}
                        </span>
                      </>
                    ) : (
                      <>
                        <button type="button" className="board-action-button" onClick={() => openPanel(thread.id, 'comment')}>コメント</button>
                        <button type="button" className="board-action-button" onClick={() => openPanel(thread.id, 'eejanaika')}>ええじゃないか</button>
                      </>
                    )}
                  </div>
                )}
              </footer>
            </div>
          </article>
        );
      })}
    </div>
  );
};

function shortReactionLabel(value: string): string {
  const chars = Array.from(value.trim());
  return chars.length > 4 ? `${chars.slice(0, 4).join('')}..` : value;
}

function threadClassName(thread: Post): string {
  const classes = ['board-thread'];
  if (thread.gdgd) {
    classes.push('board-thread-gdgd');
  }
  return classes.join(' ');
}

function SocialRows({ thread, enabled }: { thread: Post; enabled: PublicSettings['config'] }) {
  const links = thread.social_links ?? { x: thread.tweet_url };
  const reactions = thread.social_reactions ?? {};
  const boardReactions = thread.board_reactions ?? {
    views: thread.view_count ?? 0,
    eejanaika: 0,
    omigoto: 0,
    goodjob: 0,
  };
  const boardMetrics: Array<[string, number]> = [
    ['閲覧数', boardReactions.views],
    ['ええじゃ数', boardReactions.eejanaika],
    ['お美事数', boardReactions.omigoto],
    ['いい仕事数', boardReactions.goodjob],
  ];
  const rows = [
    !thread.tweet_off && enabled.tweetEnabled && {
      key: 'x',
      label: 'X',
      url: links.x ?? thread.tweet_url,
      metrics: [
        ['閲覧数', reactions.x?.impressions ?? 0],
        ['いいね数', reactions.x?.likes ?? 0],
        ['RP数', reactions.x?.reposts ?? 0],
      ],
    },
    enabled.blueskyEnabled && {
      key: 'bluesky',
      label: 'Bluesky',
      url: links.bluesky,
      metrics: [
        ['Like数', reactions.bluesky?.likes ?? 0],
        ['Repost数', reactions.bluesky?.reposts ?? 0],
        ['Quote数', reactions.bluesky?.quotes ?? 0],
      ],
    },
    enabled.mastodonEnabled && {
      key: 'mastodon',
      label: 'Mastodon',
      url: links.mastodon,
      metrics: [
        ['Boost数', reactions.mastodon?.boosts ?? 0],
        ['Fav数', reactions.mastodon?.favs ?? 0],
      ],
    },
    enabled.misskeyEnabled && {
      key: 'misskey',
      label: 'Misskey',
      url: links.misskey,
      metrics: [
        ['🔥', reactions.misskey?.fire ?? 0],
        ['👀', reactions.misskey?.eyes ?? 0],
        ['😭', reactions.misskey?.cry ?? 0],
        ['🤔', reactions.misskey?.thinking ?? 0],
        ['🎉', reactions.misskey?.party ?? 0],
        ['他', reactions.misskey?.other ?? 0],
      ],
    },
  ].filter(Boolean) as Array<{ key: string; label: string; url?: string | null; metrics: Array<[string, number]> }>;

  return (
    <div className="board-social-rows">
      <p className="board-social-row">
        <span className="board-social-label">当板：</span>
        {boardMetrics.map(([label, value]) => (
          <span className="board-social-metric board-social-metric-board" key={label}>
            {label}: {value}
          </span>
        ))}
      </p>
      {rows.map((row) => (
        <p className="board-social-row" key={row.key}>
          <span className="board-social-label">{row.label}先：</span>
          {row.url ? (
            <a className="board-tweet-link" href={row.url} target="_blank" rel="noreferrer" aria-label={`${row.label}先`}>
              ■
            </a>
          ) : (
            <span className="board-tweet-placeholder" aria-label={`${row.label}先未登録`}>■</span>
          )}
          {row.metrics.map(([label, value]) => (
            <span className={`board-social-metric board-social-metric-${row.key}`} key={label}>
              {label}: {value}
            </span>
          ))}
        </p>
      ))}
    </div>
  );
}

type EejanaikaOption = {
  key: 'omigoto' | 'goodjob' | 'eejanaika';
  text: string;
  color: string;
  className: string;
};

export function eejanaikaOptionsFromSettings(config: PublicSettings['config']): EejanaikaOption[] {
  return [
    {
      key: 'omigoto',
      text: config.eejanaikaOmigotoText,
      color: config.eejanaikaOmigotoColor,
      className: 'eejanaika-reply-omigoto',
    },
    {
      key: 'goodjob',
      text: config.eejanaikaGoodjobText,
      color: config.eejanaikaGoodjobColor,
      className: 'eejanaika-reply-goodjob',
    },
    {
      key: 'eejanaika',
      text: config.eejanaikaEejanaikaText,
      color: config.eejanaikaEejanaikaColor,
      className: 'eejanaika-reply-eejanaika',
    },
  ];
}

export function replyTextStyle(message: string, config = DEFAULT_PUBLIC_SETTINGS.config): CSSProperties | undefined {
  const option = eejanaikaOptionsFromSettings(config).find((item) => item.text === message);
  return option ? { color: option.color } : undefined;
}

export function replyTextClassName(message: string, config = DEFAULT_PUBLIC_SETTINGS.config): string {
  const classes = ['board-reply-text'];
  const dynamicOption = eejanaikaOptionsFromSettings(config).find((item) => item.text === message);
  if (dynamicOption) {
    classes.push(dynamicOption.className);
    return classes.join(' ');
  }
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
