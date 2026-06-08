import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { useAuth } from '../auth';
import { metricOptions, MetricId, metricValue } from '../metrics';
import { Post } from '../types';

const BoardAnalyticsPage = () => {
  const { user } = useAuth();
  const [posts, setPosts] = useState<Post[]>([]);
  const [metric, setMetric] = useState<MetricId>('views');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!user) return;
    api.listBoardAnalyticsPosts().then(setPosts).catch((err) => setError((err as Error).message));
  }, [user]);

  const totals = useMemo(() => ({
    views: posts.reduce((sum, post) => sum + metricValue(post, 'views'), 0),
    eejanaika: posts.reduce((sum, post) => sum + metricValue(post, 'eejanaika'), 0),
    omigoto: posts.reduce((sum, post) => sum + metricValue(post, 'omigoto'), 0),
    goodjob: posts.reduce((sum, post) => sum + metricValue(post, 'goodjob'), 0),
  }), [posts]);
  const rows = useMemo(() => {
    let cumulative = 0;
    return [...posts]
      .sort((a, b) => (a.display_no ?? a.id) - (b.display_no ?? b.id))
      .map((post) => {
        cumulative += metricValue(post, metric);
        return { post, value: cumulative };
      });
  }, [metric, posts]);
  const max = Math.max(...rows.map((row) => row.value), 0);
  const linePoints = rows.map((row, index) => {
    const x = rows.length <= 1 ? 50 : (index / (rows.length - 1)) * 100;
    const y = max === 0 ? 38 : 38 - (row.value / max) * 34;
    return { ...row, x, y };
  });
  const polylinePoints = linePoints.map((point) => `${point.x},${point.y}`).join(' ');

  if (!user) {
    return <section className="card"><h1>アナリティクス</h1><p>ログインすると表示できます。</p></section>;
  }

  return (
    <section className="card board-analytics-page">
      <h1>アナリティクス</h1>
      <div className="analytics-total-grid">
        <strong>閲覧数: {totals.views.toLocaleString('ja-JP')}</strong>
        <strong>ええじゃ数: {totals.eejanaika.toLocaleString('ja-JP')}</strong>
        <strong>お美事数: {totals.omigoto.toLocaleString('ja-JP')}</strong>
        <strong>いい仕事数: {totals.goodjob.toLocaleString('ja-JP')}</strong>
      </div>
      <label>
        内訳
        <select value={metric} onChange={(event) => setMetric(event.target.value as MetricId)}>
          {metricOptions.map((option) => <option key={option.id} value={option.id}>{option.label}</option>)}
        </select>
      </label>
      {error && <p className="error">{error}</p>}
      <div className="analytics-line-chart" role="img" aria-label="投稿No順の累積折れ線グラフ">
        <svg viewBox="0 0 100 42" preserveAspectRatio="none">
          <line x1="0" y1="38" x2="100" y2="38" />
          {polylinePoints && <polyline points={polylinePoints} />}
          {linePoints.map((point) => (
            <circle key={point.post.id} cx={point.x} cy={point.y} r="0.9">
              <title>{`No.${point.post.display_no ?? point.post.id}: ${point.value}`}</title>
            </circle>
          ))}
        </svg>
      </div>
      <div className="cumulative-chart">
        {rows.map((row) => (
          <Link to={`/thread/${row.post.id}`} className="cumulative-row" key={row.post.id}>
            <span>No.{row.post.display_no ?? row.post.id}</span>
            <i style={{ width: `${row.value === 0 || max === 0 ? 0 : Math.max(3, (row.value / max) * 100)}%` }} />
            <b>{row.value.toLocaleString('ja-JP')}</b>
          </Link>
        ))}
      </div>
    </section>
  );
};

export default BoardAnalyticsPage;
