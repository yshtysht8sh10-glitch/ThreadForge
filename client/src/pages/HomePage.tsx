import { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { useLocation, useSearchParams } from 'react-router-dom';
import { api } from '../api';
import { Post } from '../types';
import ThreadList from '../components/ThreadList';

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
    setThreads([]);
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

  if (loading) {
    return <div className="board-message">読み込み中...</div>;
  }

  if (error) {
    return <div className="board-message">エラー: {error}</div>;
  }

  const canUseAutoLoad = 'IntersectionObserver' in window;
  const years = groupPeriods(periods);
  const hasPeriodFilter = selectedYears.length > 0 || selectedMonths.length > 0;
  const updatePeriods = (yearsNext: string[], monthsNext: string[]) => {
    const next = new URLSearchParams(searchParams);
    next.delete('page');
    if (yearsNext.length > 0) {
      next.set('years', yearsNext.sort((a, b) => b.localeCompare(a)).join(','));
    } else {
      next.delete('years');
    }
    if (monthsNext.length > 0) {
      next.set('months', monthsNext.sort((a, b) => b.localeCompare(a)).join(','));
    } else {
      next.delete('months');
    }
    setSearchParams(next);
  };
  const toggleYear = (year: string) => {
    const yearSet = new Set(selectedYears);
    const monthSet = new Set(selectedMonths.filter((month) => !month.startsWith(`${year}-`)));
    if (yearSet.has(year)) {
      yearSet.delete(year);
    } else {
      yearSet.add(year);
    }
    updatePeriods([...yearSet], [...monthSet]);
  };
  const toggleMonth = (month: string) => {
    const year = month.slice(0, 4);
    const yearSet = new Set(selectedYears.filter((selectedYear) => selectedYear !== year));
    const monthSet = new Set(selectedMonths);
    if (monthSet.has(month)) {
      monthSet.delete(month);
    } else {
      monthSet.add(month);
    }
    updatePeriods([...yearSet], [...monthSet]);
  };
  const clearPeriods = () => updatePeriods([], []);

  return (
    <>
      {!targetId && (
        <section className="period-filter-card" aria-label="表示期間">
          <div className="period-filter-header">
            <div>
              <h2>表示期間</h2>
              <p>年を選ぶとその年全体、月を選ぶとその月だけを表示します。</p>
            </div>
            <strong>総投稿数: {filteredTotal.toLocaleString('ja-JP')}</strong>
          </div>
          <div className="period-filter-grid">
            {years.map((year) => (
              <section className="period-year-block" key={year.year}>
                <button
                  type="button"
                  className={selectedYears.includes(year.year) ? 'period-chip selected' : 'period-chip'}
                  onClick={() => toggleYear(year.year)}
                >
                  {year.year}年 <span>{year.count}</span>
                </button>
                <div className="period-month-list">
                  {year.months.map((month) => {
                    const key = `${year.year}-${month.month}`;
                    return (
                      <button
                        type="button"
                        className={selectedMonths.includes(key) ? 'period-chip month selected' : 'period-chip month'}
                        key={key}
                        onClick={() => toggleMonth(key)}
                      >
                        {Number(month.month)}月 <span>{month.count}</span>
                      </button>
                    );
                  })}
                </div>
              </section>
            ))}
          </div>
          {hasPeriodFilter && (
            <button type="button" className="secondary period-clear-button" onClick={clearPeriods}>期間指定を解除</button>
          )}
        </section>
      )}
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

function queryList(value: string | null): string[] {
  return (value ?? '').split(',').map((item) => item.trim()).filter(Boolean);
}

function groupPeriods(periods: { year: string; month: string; count: number }[]): Array<{ year: string; count: number; months: Array<{ month: string; count: number }> }> {
  const yearMap = new Map<string, { year: string; count: number; months: Array<{ month: string; count: number }> }>();
  periods.forEach((period) => {
    const year = yearMap.get(period.year) ?? { year: period.year, count: 0, months: [] };
    year.count += period.count;
    year.months.push({ month: period.month, count: period.count });
    yearMap.set(period.year, year);
  });
  return [...yearMap.values()]
    .sort((a, b) => b.year.localeCompare(a.year))
    .map((year) => ({
      ...year,
      months: year.months.sort((a, b) => b.month.localeCompare(a.month)),
    }));
}
