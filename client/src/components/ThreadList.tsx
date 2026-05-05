import { Link } from 'react-router-dom';
import { mediaUrl } from '../api';
import { Post } from '../types';
import LinkedText from './LinkedText';

type ThreadListProps = {
  threads: Post[];
  action?: (post: Post) => React.ReactNode;
};

const ThreadList = ({ threads, action }: ThreadListProps) => {
  return (
    <div className="thread-list">
      {threads.length === 0 && <div className="board-message">投稿はまだありません。</div>}
      {threads.map((thread) => (
        <article key={thread.id} id={`post-${thread.id}`} className={threadClassName(thread)}>
          <header className="board-thread-title">
            <Link to={`/thread/${thread.id}`}>[No・{thread.id}] {thread.title || '無題'}</Link>
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

            {(thread.replies ?? []).slice(0, 10).map((reply) => (
              <section key={reply.id} className="board-reply">
                <p className="board-meta">
                  NAME：<strong>{reply.name}</strong>
                  {reply.url && <> <a href={reply.url} target="_blank" rel="noreferrer">[HOME]</a></>}
                  {' '} - {formatDate(reply.created_at)}
                </p>
                <div className={replyTextClassName(reply.message)}>
                  <LinkedText text={reply.message} />
                </div>
              </section>
            ))}

            <footer className="board-thread-actions">
              {action ? action(thread) : (
                <>
                  <Link className="board-action-button" to={`/thread/${thread.id}`}>コメント</Link>
                  <Link className="board-action-button" to={`/thread/${thread.id}?mode=eejanaika`}>ええじゃないか</Link>
                </>
              )}
              {Number(thread.reply_count ?? 0) > 10 && (
                <Link className="board-more-link" to={`/thread/${thread.id}`}>
                  ほか{Number(thread.reply_count) - 10}件の返信
                </Link>
              )}
            </footer>
          </div>
        </article>
      ))}
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

function replyTextClassName(message: string): string {
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
