import { Link } from 'react-router-dom';
import { mediaUrl } from '../api';
import { Post } from '../types';
import LinkedText from './LinkedText';
import { replyTextClassName } from './ThreadList';

type SelectableThreadListProps = {
  threads: Post[];
  selectedIds: string[];
  onToggle: (id: string) => void;
  multiple?: boolean;
};

const SelectableThreadList = ({ threads, selectedIds, onToggle, multiple = false }: SelectableThreadListProps) => {
  const inputType = multiple ? 'checkbox' : 'radio';
  return (
    <div className="thread-list">
      {threads.length === 0 && <div className="board-message">投稿はまだありません。</div>}
      {threads.map((thread) => (
        <article key={thread.id} id={`post-${thread.id}`} className={threadClassName(thread)}>
          <label className="mode-select-checkbox">
            <input
              type={inputType}
              name={multiple ? undefined : 'selected-post'}
              checked={selectedIds.includes(String(thread.id))}
              onChange={() => onToggle(String(thread.id))}
            />
            No.{thread.display_no ?? thread.id} を選択
          </label>

          <header className="board-thread-title">
            <Link to={`/thread/${thread.id}`}>[No・{thread.display_no ?? thread.id}] {thread.title || '無題'}</Link>
          </header>

          <div className="board-thread-body">
            <p className="board-meta">
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

            {(thread.replies ?? []).slice(0, 10).map((reply) => (
              <section key={reply.id} className="board-reply selectable-board-reply">
                <label className="mode-select-checkbox mode-select-checkbox-reply">
                  <input
                    type={inputType}
                    name={multiple ? undefined : 'selected-post'}
                    checked={selectedIds.includes(String(reply.id))}
                    onChange={() => onToggle(String(reply.id))}
                  />
                  返信No.{thread.display_no ?? thread.id}-{reply.reply_no ?? reply.id} を選択
                </label>
                <p className="board-meta">
                  NAME：<strong>{reply.name}</strong>
                  {reply.url && <> <a href={reply.url} target="_blank" rel="noreferrer">[HOME]</a></>}
                  {' '}<span className="board-meta-sub">- {formatDate(reply.created_at)}</span>
                </p>
                <div className={replyTextClassName(reply.message)}>
                  <LinkedText text={reply.message} />
                </div>
              </section>
            ))}
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

export default SelectableThreadList;
