import { mediaUrl } from '../api';
import { Post } from '../types';
import LinkedText from './LinkedText';
import { replyTextClassName } from './ThreadList';

type SelectableThreadListProps = {
  threads: Post[];
  selectedIds: string[];
  onToggle: (id: string) => void;
  multiple?: boolean;
  isDisabled?: (post: Post) => boolean;
  disabledLabel?: string;
};

const SelectableThreadList = ({ threads, selectedIds, onToggle, multiple = false, isDisabled, disabledLabel = '選択不可' }: SelectableThreadListProps) => {
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
              disabled={isDisabled?.(thread) ?? false}
              onChange={() => onToggle(String(thread.id))}
            />
            No.{thread.display_no ?? thread.id} を選択
            {isDisabled?.(thread) && <span className="mode-select-disabled-note">{disabledLabel}</span>}
          </label>

          <header className="board-thread-title">
            [No.{thread.display_no ?? thread.id}] {thread.title || '無題'}
          </header>

          <div className="board-thread-body">
            <p className="board-meta">
              {thread.user_icon_path && <img className="user-icon" src={mediaUrl(thread.user_icon_path) ?? undefined} alt="" />}
              NAME：<strong>{thread.name}</strong>
              {thread.url && <> <a href={thread.url} target="_blank" rel="noreferrer">[HOME]</a></>}
              {' '}<span className="board-meta-sub">投稿日時：{formatDate(thread.created_at)}</span>
            </p>

            {mediaUrl(thread.image_path) && (
              <div className="board-image-link">
                <img className="board-post-image" src={mediaUrl(thread.image_path) ?? undefined} alt={thread.title || '投稿画像'} />
              </div>
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
                    disabled={isDisabled?.(reply) ?? false}
                    onChange={() => onToggle(String(reply.id))}
                  />
                  返信No.{thread.display_no ?? thread.id}-{reply.reply_no ?? reply.id} を選択
                  {isDisabled?.(reply) && <span className="mode-select-disabled-note">{disabledLabel}</span>}
                </label>
                <p className="board-meta">
                  {reply.user_icon_path && <img className="user-icon" src={mediaUrl(reply.user_icon_path) ?? undefined} alt="" />}
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
