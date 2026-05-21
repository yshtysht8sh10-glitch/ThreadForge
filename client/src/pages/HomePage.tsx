import { useEffect, useLayoutEffect, useState } from 'react';
import { useLocation, useSearchParams } from 'react-router-dom';
import { api } from '../api';
import { Post } from '../types';
import ThreadList from '../components/ThreadList';

const HomePage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [threads, setThreads] = useState<Post[]>([]);
  const [pageSize, setPageSize] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const location = useLocation();
  const targetId = searchParams.get('target');
  const page = Math.max(1, Number(searchParams.get('page') || '1') || 1);

  useEffect(() => {
    setLoading(true);
    Promise.all([api.publicSettings(), api.listThreads(targetId, targetId ? null : page)])
      .then(([settingsResponse, items]) => {
        const configuredLimit = Number(settingsResponse.settings.config.logView);
        setPageSize(Number.isFinite(configuredLimit) && configuredLimit > 0 ? configuredLimit : null);
        setThreads(items);
        setError(null);
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [targetId, page]);

  useLayoutEffect(() => {
    if (loading || error || !location.hash) return;
    const target = document.querySelector(location.hash);
    target?.scrollIntoView({ block: 'start' });
  }, [loading, error, location.hash, threads]);

  if (loading) {
    return <div className="board-message">読み込み中...</div>;
  }

  if (error) {
    return <div className="board-message">エラー: {error}</div>;
  }

  const goPage = (nextPage: number) => {
    const next = new URLSearchParams(searchParams);
    if (nextPage <= 1) {
      next.delete('page');
    } else {
      next.set('page', String(nextPage));
    }
    setSearchParams(next);
  };
  const showPager = !targetId && pageSize !== null;
  const hasNext = showPager && threads.length >= pageSize;

  return (
    <>
      <ThreadList threads={threads} />
      {showPager && (
        <nav className="board-pager" aria-label="一覧ページ">
          <button type="button" className="secondary" onClick={() => goPage(page - 1)} disabled={page <= 1}>前へ</button>
          <span>{page}ページ</span>
          <button type="button" className="secondary" onClick={() => goPage(page + 1)} disabled={!hasNext}>次へ</button>
        </nav>
      )}
    </>
  );
};

export default HomePage;
