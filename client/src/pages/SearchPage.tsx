import { FormEvent, useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { api, mediaUrl } from '../api';
import { SearchResult } from '../types';
import LinkedText from '../components/LinkedText';

const SearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [query, setQuery] = useState(() => searchParams.get('q') ?? '');
  const [scope, setScope] = useState(() => searchParams.get('scope') ?? 'all');
  const [results, setResults] = useState<SearchResult[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const nextQuery = searchParams.get('q') ?? '';
    const nextScope = searchParams.get('scope') ?? 'all';
    setQuery(nextQuery);
    setScope(nextScope);
    if (nextQuery.trim() === '') {
      setResults([]);
      return;
    }

    let ignore = false;
    setLoading(true);
    setError(null);
    api.search(nextQuery, nextScope)
      .then((data) => {
        if (!ignore) setResults(data);
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

  const handleSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const trimmed = query.trim();
    if (trimmed === '') {
      setSearchParams({});
      setResults([]);
      return;
    }
    setSearchParams({ q: trimmed, scope });
  };

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
                  <img className="search-result-image" src={mediaUrl(item.image_path) ?? undefined} alt={item.title || '投稿画像'} />
                </Link>
              )}
              <p><LinkedText text={item.message.length > 120 ? `${item.message.slice(0, 120)}...` : item.message} /></p>
              <p>
                <strong>{item.name}</strong> · {new Date(item.created_at).toLocaleString()}
              </p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

function listTargetHref(item: SearchResult): string {
  const targetId = item.parent_id === 0 ? item.id : item.thread_id;
  return `/?target=${encodeURIComponent(String(targetId))}#post-${targetId}`;
}

export default SearchPage;
