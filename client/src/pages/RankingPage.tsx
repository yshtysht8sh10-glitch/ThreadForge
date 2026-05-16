import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { metricOptions, MetricId, metricValue } from '../metrics';
import { Post } from '../types';

const RankingPage = () => {
  const [posts, setPosts] = useState<Post[]>([]);
  const [metric, setMetric] = useState<MetricId>('views');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.listRankingPosts().then(setPosts).catch((err) => setError((err as Error).message));
  }, []);

  const ranked = useMemo(() => [...posts].sort((a, b) => metricValue(b, metric) - metricValue(a, metric)), [metric, posts]);

  return (
    <section className="card ranking-page">
      <h1>順位</h1>
      <label>
        表示データ
        <select value={metric} onChange={(event) => setMetric(event.target.value as MetricId)}>
          {metricOptions.map((option) => <option key={option.id} value={option.id}>{option.label}</option>)}
        </select>
      </label>
      {error && <p className="error">{error}</p>}
      <div className="ranking-list">
        {ranked.map((post, index) => (
          <Link className="ranking-row" to={`/thread/${post.id}`} key={post.id}>
            <span>{index + 1}</span>
            <strong>No.{post.display_no ?? post.id} {post.title || '無題'}</strong>
            <b>{metricValue(post, metric).toLocaleString('ja-JP')}</b>
          </Link>
        ))}
      </div>
    </section>
  );
};

export default RankingPage;
