import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { metricOptions, MetricId, metricValue } from '../metrics';
import { Post } from '../types';
import UserIconLink from '../components/UserIconLink';

const RankingPage = () => {
  const [posts, setPosts] = useState<Post[]>([]);
  const [metric, setMetric] = useState<MetricId>('views');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.listRankingPosts().then(setPosts).catch((err) => setError((err as Error).message));
  }, []);

  const ranked = useMemo(() => {
    const sorted = [...posts].sort((a, b) => metricValue(b, metric) - metricValue(a, metric));
    return sorted.map((post, index) => {
      const value = metricValue(post, metric);
      const previousHigherCount = sorted.slice(0, index).filter((item) => metricValue(item, metric) > value).length;
      return {
        post,
        value,
        rank: previousHigherCount + 1,
      };
    });
  }, [metric, posts]);

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
        {ranked.map(({ post, rank, value }) => (
          <div className={`ranking-row ${rankClassName(rank, value)}`} key={post.id}>
            <span className="ranking-position">{rank}</span>
            <strong className="ranking-title">
              <Link to={listTargetHref(post)}>No.{post.display_no ?? post.id} {post.title || '無題'}</Link>
              <span className="ranking-author">
                <UserIconLink post={post} />
                NAME: {post.name}
              </span>
            </strong>
            <b>{value.toLocaleString('ja-JP')}</b>
          </div>
        ))}
      </div>
    </section>
  );
};

function rankClassName(rank: number, value: number): string {
  if (value <= 0) return '';
  if (rank === 1) return 'ranking-row-gold';
  if (rank === 2) return 'ranking-row-silver';
  if (rank === 3) return 'ranking-row-bronze';
  return '';
}

function listTargetHref(post: Post): string {
  return `/?target=${encodeURIComponent(String(post.id))}#post-${post.id}`;
}

export default RankingPage;
