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
  const [previousPage, setPreviousPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [hasNewer, setHasNewer] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [loadingNewer, setLoadingNewer] = useState(false);
  const [loadingToTop, setLoadingToTop] = useState(false);
  const [periodExpanded, setPeriodExpanded] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [periods, setPeriods] = useState<{ year: string; month: string; count: number }[]>([]);
  const [filteredTotal, setFilteredTotal] = useState(0);
  const loadMoreRef = useRef<HTMLDivElement | null>(null);
  const loadNewerRef = useRef<HTMLDivElement | null>(null);
  const listTopRef = useRef<HTMLDivElement | null>(null);
  const loadingNewerRef = useRef(false);
  const autoPeriodExpandedRef = useRef(false);
  const location = useLocation();
  const targetId = searchParams.get('target');
  const page = Math.max(1, Number(searchParams.get('page') || '1') || 1);
  const selectedYears = queryList(searchParams.get('years')).filter((year) => /^\d{4}$/.test(year));
  const selectedMonths = queryList(searchParams.get('months')).filter((month) => /^\d{4}-\d{2}$/.test(month));

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setHasMore(false);
    setHasNewer(false);
    loadingNewerRef.current = false;
    setError(null);

    Promise.all([
      api.listThreadArchiveMeta(selectedYears, selectedMonths),
      api.listThreads(targetId, page, DEFAULT_BATCH_SIZE, selectedYears, selectedMonths),
    ])
      .then(([meta, items]) => {
        if (cancelled) return;
        setPeriods(meta.periods);
        setFilteredTotal(meta.total);
        setThreads(items);
        setNextPage(page + 1);
        setPreviousPage(1);
        setHasMore(items.length >= DEFAULT_BATCH_SIZE);
        setHasNewer(Boolean(targetId));
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
    const target = document.querySelector<HTMLElement>(location.hash);
    if (!target) return;
    target.scrollIntoView({ block: 'start' });
    window.requestAnimationFrame(() => {
      const periodHeight = document.querySelector<HTMLElement>('.period-filter-sticky')?.offsetHeight ?? 0;
      window.scrollBy({ top: -(periodHeight + 12), behavior: 'auto' });
    });
  }, [loading, error, location.hash, threads]);

  const loadMore = useCallback(async () => {
    if (loadingMore || !hasMore) return;
    setLoadingMore(true);
    try {
      const items = await api.listThreads(targetId, nextPage, DEFAULT_BATCH_SIZE, selectedYears, selectedMonths);
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

  const loadNewer = useCallback(async () => {
    if (!targetId || loadingNewerRef.current || loadingNewer || !hasNewer) return;
    loadingNewerRef.current = true;
    setLoadingNewer(true);
    const beforeHeight = document.documentElement.scrollHeight;
    const beforeY = window.scrollY;
    try {
      const items = await api.listThreads(targetId, previousPage, DEFAULT_BATCH_SIZE, selectedYears, selectedMonths, 'newer');
      const knownIds = new Set(threads.map((thread) => thread.id));
      const newThreads = items.filter((thread) => !knownIds.has(thread.id));
      setThreads((current) => {
        const seen = new Set(current.map((thread) => thread.id));
        const nextItems = newThreads.filter((thread) => !seen.has(thread.id));
        return [...nextItems, ...current];
      });
      setPreviousPage((current) => current + 1);
      setHasNewer(items.length >= DEFAULT_BATCH_SIZE && newThreads.length > 0);
      setError(null);
      if (newThreads.length > 0) {
        window.requestAnimationFrame(() => {
          const afterHeight = document.documentElement.scrollHeight;
          window.scrollTo({ top: beforeY + Math.max(0, afterHeight - beforeHeight), behavior: 'auto' });
        });
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoadingNewer(false);
      window.setTimeout(() => {
        loadingNewerRef.current = false;
      }, 120);
    }
  }, [hasNewer, loadingNewer, previousPage, selectedYears.join(','), selectedMonths.join(','), targetId, threads]);

  useEffect(() => {
    const shouldShowPeriod = !targetId && page === 1;
    setPeriodExpanded(shouldShowPeriod);
    autoPeriodExpandedRef.current = shouldShowPeriod;
  }, [targetId, page]);

  useEffect(() => {
    const hideAutoPeriodOnScroll = () => {
      if (autoPeriodExpandedRef.current && window.scrollY > 24) {
        autoPeriodExpandedRef.current = false;
        setPeriodExpanded(false);
      }
    };
    window.addEventListener('scroll', hideAutoPeriodOnScroll, { passive: true });
    return () => window.removeEventListener('scroll', hideAutoPeriodOnScroll);
  }, []);

  useEffect(() => {
    if (!targetId || !hasNewer) return;
    const maybeLoadNewer = () => {
      const loadNewerTop = loadNewerRef.current;
      if (!loadNewerTop) return;
      const loadNewerY = loadNewerTop.getBoundingClientRect().top + window.scrollY;
      if (window.scrollY <= loadNewerY + 120) {
        void loadNewer();
      }
    };
    window.addEventListener('scroll', maybeLoadNewer, { passive: true });
    maybeLoadNewer();
    return () => window.removeEventListener('scroll', maybeLoadNewer);
  }, [hasNewer, loadNewer, targetId]);

  useEffect(() => {
    const element = loadNewerRef.current;
    if (!element || !targetId || !hasNewer || !('IntersectionObserver' in window)) {
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        void loadNewer();
      }
    }, { rootMargin: '0px 0px 0px 0px' });

    observer.observe(element);
    return () => observer.disconnect();
  }, [hasNewer, loadNewer, targetId]);

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

  const scrollToNewest = useCallback(async () => {
    if (loadingToTop) return;
    setLoadingToTop(true);
    try {
      const items = await api.listThreads(null, 1, DEFAULT_BATCH_SIZE, selectedYears, selectedMonths);
      setThreads(items);
      setNextPage(2);
      setPreviousPage(1);
      setHasMore(items.length >= DEFAULT_BATCH_SIZE);
      setHasNewer(false);
      setError(null);

      const next = new URLSearchParams(searchParams);
      next.delete('target');
      next.delete('page');
      setSearchParams(next, { replace: true });

      window.requestAnimationFrame(() => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoadingToTop(false);
    }
  }, [loadingToTop, searchParams, selectedMonths.join(','), selectedYears.join(','), setSearchParams]);
  if (loading && threads.length === 0) {
    return <div className="board-message">{'\u8aad\u307f\u8fbc\u307f\u4e2d...'}</div>;
  }

  if (error && threads.length === 0) {
    return <div className="board-message">{'\u30a8\u30e9\u30fc'}: {error}</div>;
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
      <div ref={listTopRef} className="list-top-sentinel" aria-hidden="true" />
      <PeriodFilter
        periods={periods}
        selectedYears={selectedYears}
        selectedMonths={selectedMonths}
        total={filteredTotal}
        onChange={updatePeriods}
        expanded={periodExpanded}
        onExpandedChange={(expanded) => {
          autoPeriodExpandedRef.current = false;
          setPeriodExpanded(expanded);
        }}
        className={`period-filter-sticky ${periodExpanded ? 'expanded' : 'compact'}`}
      />
      <div className="target-top-sentinel" ref={loadNewerRef} aria-hidden="true" />
      <div className="target-newer-status">
        {loadingNewer && <div className="board-message compact">{'\u8aad\u307f\u8fbc\u307f\u4e2d...'}</div>}
      </div>
      {loading && threads.length > 0 && <div className="board-message compact">{'\u8868\u793a\u671f\u9593\u3092\u66f4\u65b0\u4e2d...'}</div>}
      {error && threads.length > 0 && <div className="board-message">{'\u30a8\u30e9\u30fc'}: {error}</div>}
      <ThreadList threads={threads} />
      <div className="infinite-loader" ref={loadMoreRef}>
          {loadingMore && <div className="board-message">{'\u8aad\u307f\u8fbc\u307f\u4e2d...'}</div>}
          {!loadingMore && hasMore && !canUseAutoLoad && (
            <button type="button" className="secondary" onClick={() => void loadMore()}>
              {'\u3055\u3089\u306b\u8aad\u307f\u8fbc\u3080'}
            </button>
          )}
          {!hasMore && threads.length > 0 && <div className="board-message compact">{'\u3053\u3053\u307e\u3067\u3067\u3059\u3002'}</div>}
      </div>
      <button type="button" className="back-to-top-button" onClick={() => void scrollToNewest()}>
        {loadingToTop ? '\u8aad\u307f\u8fbc\u307f\u4e2d' : '\u4e0a\u3078'}
      </button>
    </>
  );
};

export default HomePage;
