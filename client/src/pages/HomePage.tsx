import { useEffect, useLayoutEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api } from '../api';
import { Post } from '../types';
import ThreadList from '../components/ThreadList';

const HomePage = () => {
  const [searchParams] = useSearchParams();
  const [threads, setThreads] = useState<Post[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const targetId = searchParams.get('target');

  useEffect(() => {
    setLoading(true);
    api.listThreads(targetId)
      .then((items) => {
        setThreads(items);
        setError(null);
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [targetId]);

  useLayoutEffect(() => {
    if (loading || error || !window.location.hash) return;
    const target = document.querySelector(window.location.hash);
    target?.scrollIntoView({ block: 'start' });
  }, [loading, error, threads]);

  if (loading) {
    return <div className="board-message">読み込み中...</div>;
  }

  if (error) {
    return <div className="board-message">エラー: {error}</div>;
  }

  return <ThreadList threads={threads} />;
};

export default HomePage;
