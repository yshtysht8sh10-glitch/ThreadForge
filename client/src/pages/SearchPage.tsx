import { FormEvent, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { SearchResult } from '../types';
import LinkedText from '../components/LinkedText';

const SearchPage = () => {
  const [query, setQuery] = useState('');
  const [scope, setScope] = useState('all');
  const [results, setResults] = useState<SearchResult[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSearch = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const data = await api.search(query, scope);
      setResults(data);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <div className="card">
        <h1>検索</h1>
        <form onSubmit={handleSearch}>
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
          <button type="submit">検索する</button>
        </form>
      </div>

      {loading && <div className="card">検索中...</div>}
      {error && <div className="card">エラー: {error}</div>}

      {results.length > 0 && (
        <div>
          {results.map((item) => (
            <div key={item.id} className="card">
              <h2>
                <Link to={`/thread/${item.thread_id || item.id}`}>{item.title || '無題'}</Link>
              </h2>
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

export default SearchPage;
