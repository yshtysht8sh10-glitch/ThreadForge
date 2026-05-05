import { useEffect, useLayoutEffect, useState } from 'react';
import { api } from '../api';
import { Post } from '../types';
import ThreadList from '../components/ThreadList';

const HomePage = () => {
  const [threads, setThreads] = useState<Post[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.listThreads()
      .then((items) => setThreads(items))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, []);

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
