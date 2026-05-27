import { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { useLocation, useSearchParams } from 'react-router-dom';
import { api } from '../api';
import { Post } from '../types';
import ThreadList from '../components/ThreadList';
import PeriodFilter, { queryList } from '../components/PeriodFilter';

const DEFAULT_BATCH_SIZE = 20;

const HomePage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [threads, setThreads] = useState<Post[]>([]);
  const [nextPage, setNextPage] = useState(2);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [periods, setPeriods] = useState<{ year: string; month: string; count: number }[]>([]);
  const [filteredTotal, setFilteredTotal] = useState(0);
  const loadMoreRef = useRef<HTMLDivElement | null>(null);
  const location = useLocation();
  const targetId = searchParams.get('target');
  const page = Math.max(1, Number(searchParams.get('page') || '1') || 1);
  const selectedYears = queryList(searchParams.get('years')).filter((year) => /^\d{4}$/.test(year));
  const selectedMonths = queryList(searchParams.get('months')).filter((month) => /^\d{4}-\d{2}$/.test(month));

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setHasMore(false);
    setError(null);

    Promise.all([
      api.listThreadArchiveMeta(selectedYears, selectedMonths),
      api.listThreads(targetId, targetId ? null : page, DEFAULT_BATCH_SIZE, selectedYears, selectedMonths),
    ])
      .then(([meta, items]) => {
        if (cancelled) return;
        setPeriods(meta.periods);
        setFilteredTotal(meta.total);
        setThreads(items);
        setNextPage(page + 1);
        setHasMore(!targetId && items.length >= DEFAULT_BATCH_SIZE);
      })
      .catch((err) => {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : String(err));
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [targetId, page, selectedYears.join(','), selectedMonths.join(',')]);

  useLayoutEffect(() => {
    if (loading || error || !location.hash) return;
    const target = document.querySelector(location.hash);
    target?.scrollIntoView({ block: 'start' });
  }, [loading, error, location.hash, threads]);

  const loadMore = useCallback(async () => {
    if (targetId || loadingMore || !hasMore) return;
    setLoadingMore(true);
    try {
      const items = await api.listThreads(null, nextPage, DEFAULT_BATCH_SIZE, selectedYears, selectedMonths);
      setThreads((current) => {
        const seen = new Set(current.map((thread) => thread.id));
        const nextItems = items.filter((thread) => !seen.has(thread.id));
        return [...current, ...nextItems];
      });
      setNextPage((current) => current + 1);
      setHasMore(items.length >= DEFAULT_BATCH_SIZE);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoadingMore(false);
    }
  }, [hasMore, loadingMore, nextPage, selectedYears.join(','), selectedMonths.join(','), targetId]);

  useEffect(() => {
    const element = loadMoreRef.current;
    if (!element || targetId || !hasMore || !('IntersectionObserver' in window)) {
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        void loadMore();
      }
    }, { rootMargin: '800px 0px' });

    observer.observe(element);
    return () => observer.disconnect();
  }, [hasMore, loadMore, targetId]);

  if (loading && threads.length === 0) {
    return <div className="board-message">読み込み中...</div>;
  }

  if (error && threads.length === 0) {
    return <div className="board-message">エラー: {error}</div>;
  }

  const canUseAutoLoad = 'IntersectionObserver' in window;
  const updatePeriods = (yearsNext: string[], monthsNext: string[]) => {
    const next = new URLSearchParams(searchParams);
    next.delete('page');
    if (yearsNext.length > 0) {
      next.set('years', yearsNext.join(','));
    } else {
      next.delete('years');
    }
    if (monthsNext.length > 0) {
      next.set('months', monthsNext.join(','));
    } else {
      next.delete('months');
    }
    setSearchParams(next);
  };

  return (
    <>
      {!targetId && (
        <PeriodFilter
          periods={periods}
          selectedYears={selectedYears}
          selectedMonths={selectedMonths}
          total={filteredTotal}
          onChange={updatePeriods}
        />
      )}
      {loading && threads.length > 0 && <div className="board-message compact">表示期間を更新中...</div>}
      {error && threads.length > 0 && <div className="board-message">繧ｨ繝ｩ繝ｼ: {error}</div>}
      <ThreadList threads={threads} />
      {!targetId && (
        <div className="infinite-loader" ref={loadMoreRef}>
          {loadingMore && <div className="board-message">読み込み中...</div>}
          {!loadingMore && hasMore && !canUseAutoLoad && (
            <button type="button" className="secondary" onClick={() => void loadMore()}>
              さらに読み込む
            </button>
          )}
          {!hasMore && threads.length > 0 && <div className="board-message compact">ここまでです。</div>}
        </div>
      )}
    </>
  );
};

export default HomePage;
