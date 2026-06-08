import { FormEvent, useCallback, useEffect, useRef, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { api, mediaUrl } from '../api';
import { SearchResult } from '../types';
import LinkedText from '../components/LinkedText';

const SEARCH_BATCH_SIZE = 50;

const SearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [query, setQuery] = useState(() => searchParams.get('q') ?? '');
  const [scope, setScope] = useState(() => searchParams.get('scope') ?? 'all');
  const [includePosts, setIncludePosts] = useState(() => searchParams.get('posts') !== '0');
  const [includeReplies, setIncludeReplies] = useState(() => searchParams.get('replies') !== '0');
  const [results, setResults] = useState<SearchResult[]>([]);
  const [nextPage, setNextPage] = useState(2);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const loadMoreRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    const nextQuery = searchParams.get('q') ?? '';
    const nextScope = searchParams.get('scope') ?? 'all';
    const nextIncludePosts = searchParams.get('posts') !== '0';
    const nextIncludeReplies = searchParams.get('replies') !== '0';
    setQuery(nextQuery);
    setScope(nextScope);
    setIncludePosts(nextIncludePosts);
    setIncludeReplies(nextIncludeReplies);
    if (nextQuery.trim() === '') {
      setResults([]);
      setHasMore(false);
      return;
    }

    let ignore = false;
    setLoading(true);
    setHasMore(false);
    setError(null);
    api.search(nextQuery, nextScope, 1, SEARCH_BATCH_SIZE, searchKindParam(nextIncludePosts, nextIncludeReplies))
      .then((data) => {
        if (ignore) return;
        setResults(data);
        setNextPage(2);
        setHasMore(data.length >= SEARCH_BATCH_SIZE);
      })
      .catch((err) => {
        if (!ignore) setError((err as Error).message);
      })
      .finally(() => {
        if (!ignore) setLoading(false);
      });
    return () => {
      ignore = true;
    };
  }, [searchParams]);

  const loadMore = useCallback(async () => {
    const activeQuery = searchParams.get('q') ?? '';
    const activeScope = searchParams.get('scope') ?? 'all';
    const activeIncludePosts = searchParams.get('posts') !== '0';
    const activeIncludeReplies = searchParams.get('replies') !== '0';
    if (activeQuery.trim() === '' || loadingMore || !hasMore) return;
    setLoadingMore(true);
    try {
      const data = await api.search(
        activeQuery,
        activeScope,
        nextPage,
        SEARCH_BATCH_SIZE,
        searchKindParam(activeIncludePosts, activeIncludeReplies),
      );
      setResults((current) => {
        const seen = new Set(current.map((item) => item.id));
        const nextItems = data.filter((item) => !seen.has(item.id));
        return [...current, ...nextItems];
      });
      setNextPage((current) => current + 1);
      setHasMore(data.length >= SEARCH_BATCH_SIZE);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoadingMore(false);
    }
  }, [hasMore, loadingMore, nextPage, searchParams]);

  useEffect(() => {
    const element = loadMoreRef.current;
    if (!element || !hasMore || !('IntersectionObserver' in window)) {
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        void loadMore();
      }
    }, { rootMargin: '800px 0px' });

    observer.observe(element);
    return () => observer.disconnect();
  }, [hasMore, loadMore]);

  const updateSearchParams = (nextQuery: string, nextScope: string, nextIncludePosts: boolean, nextIncludeReplies: boolean) => {
    const trimmed = nextQuery.trim();
    if (trimmed === '') {
      setSearchParams({});
      setResults([]);
      setHasMore(false);
      return;
    }
    setSearchParams({
      q: trimmed,
      scope: nextScope,
      posts: nextIncludePosts ? '1' : '0',
      replies: nextIncludeReplies ? '1' : '0',
    });
  };

  const handleSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    updateSearchParams(query, scope, includePosts, includeReplies);
  };

  const changeIncludePosts = (checked: boolean) => {
    setIncludePosts(checked);
    updateSearchParams(query, scope, checked, includeReplies);
  };

  const changeIncludeReplies = (checked: boolean) => {
    setIncludeReplies(checked);
    updateSearchParams(query, scope, includePosts, checked);
  };

  const canUseAutoLoad = 'IntersectionObserver' in window;

  return (
    <div>
      <div className="card">
        <h1>検索</h1>
        <form onSubmit={handleSearch} className="form-card">
          <div className="field">
            <label htmlFor="search">キーワード</label>
            <input id="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="タイトル・本文・投稿者名を検索" />
          </div>
          <div className="field">
            <label htmlFor="search-scope">検索対象</label>
            <select id="search-scope" value={scope} onChange={(event) => setScope(event.target.value)}>
              <option value="all">全て</option>
              <option value="title">タイトル</option>
              <option value="message">本文</option>
              <option value="name">投稿者名</option>
            </select>
          </div>
          <fieldset className="inline-options">
            <legend>表示対象</legend>
            <label>
              <input
                type="checkbox"
                checked={includePosts}
                onChange={(event) => changeIncludePosts(event.target.checked)}
              />
              投稿
            </label>
            <label>
              <input
                type="checkbox"
                checked={includeReplies}
                onChange={(event) => changeIncludeReplies(event.target.checked)}
              />
              返信
            </label>
          </fieldset>
          <div className="button-row align-right">
            <button type="submit">検索する</button>
          </div>
        </form>
      </div>

      {loading && <div className="card">検索中...</div>}
      {error && <div className="card">エラー: {error}</div>}

      {results.length > 0 && (
        <div>
          {results.map((item) => (
            <div key={item.id} className="card search-result-card">
              <h2>
                <Link to={listTargetHref(item)}>{item.title || '無題'}</Link>
              </h2>
              {mediaUrl(item.image_path) && (
                <Link to={listTargetHref(item)} className="search-result-image-link">
                  <img
                    className="search-result-image"
                    src={mediaUrl(item.image_path) ?? undefined}
                    alt={item.title || '投稿画像'}
                    loading="lazy"
                    decoding="async"
                  />
                </Link>
              )}
              <p><LinkedText text={item.message.length > 120 ? `${item.message.slice(0, 120)}...` : item.message} /></p>
              <p>
                <strong>{item.name}</strong> - {new Date(item.created_at).toLocaleString()}
              </p>
            </div>
          ))}
        </div>
      )}

      {searchParams.get('q') && !loading && results.length === 0 && !error && (
        <div className="card">該当する投稿はありません。</div>
      )}

      {results.length > 0 && (
        <div className="infinite-loader" ref={loadMoreRef}>
          {loadingMore && <div className="board-message">読み込み中...</div>}
          {!loadingMore && hasMore && !canUseAutoLoad && (
            <button type="button" className="secondary" onClick={() => void loadMore()}>
              さらに読み込む
            </button>
          )}
          {!hasMore && <div className="board-message compact">ここまでです。</div>}
        </div>
      )}
    </div>
  );
};

function listTargetHref(item: SearchResult): string {
  const targetId = item.parent_id === 0 ? item.id : item.thread_id;
  return `/?target=${encodeURIComponent(String(targetId))}#post-${targetId}`;
}

function searchKindParam(includePosts: boolean, includeReplies: boolean): string {
  if (includePosts && includeReplies) return 'all';
  if (includePosts) return 'posts';
  if (includeReplies) return 'replies';
  return 'none';
}

export default SearchPage;
