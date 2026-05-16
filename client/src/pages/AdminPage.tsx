import { FormEvent, useEffect, useState } from 'react';
import { api, apiBase } from '../api';
import { Post } from '../types';
import SelectableThreadList from '../components/SelectableThreadList';

type Settings = {
  config: Record<string, string | number | boolean>;
  skin: Record<string, string | number | boolean>;
};

type AdminTab = 'posts' | 'maintenance' | 'backup' | 'analytics' | 'settings' | 'deleted';
type AnalyticsMetric = 'postCount' | 'commentCount' | 'accessCount' | 'viewCount' | 'boardEejanaika' | 'boardOmigoto' | 'boardGoodjob' | 'xLikes' | 'xReposts' | 'xImpressions' | 'blueskyLikes' | 'blueskyReposts' | 'mastodonBoosts' | 'mastodonFavs' | 'misskeyReactions' | 'misskeyFire' | 'misskeyEyes' | 'misskeyCry' | 'misskeyThinking' | 'misskeyParty' | 'misskeyOther';
type AnalyticsUnit = 'dayTotal' | 'dayCumulative' | 'monthTotal' | 'monthAverage' | 'monthCumulative' | 'yearTotal' | 'yearAverage' | 'yearCumulative';

const DEFAULT_HIDDEN_ADMIN_PASSWORD = 'admin';

const AdminPage = () => {
  const [adminPassword, setAdminPassword] = useState(() => {
    return window.localStorage.getItem('threadforgeAdminPassword') || DEFAULT_HIDDEN_ADMIN_PASSWORD;
  });
  const [activeTab, setActiveTab] = useState<AdminTab>('posts');
  const [threads, setThreads] = useState<Post[]>([]);
  const [deletedPosts, setDeletedPosts] = useState<Post[]>([]);
  const [analyticsPosts, setAnalyticsPosts] = useState<Post[]>([]);
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [settings, setSettings] = useState<Settings | null>(null);
  const [cronPath, setCronPath] = useState('');
  const [cronApiUrl, setCronApiUrl] = useState('');
  const [cronApiKey, setCronApiKey] = useState('');
  const [backupFile, setBackupFile] = useState<File | null>(null);
  const [newAdminPassword, setNewAdminPassword] = useState('');
  const [analyticsMetric, setAnalyticsMetric] = useState<AnalyticsMetric>('postCount');
  const [analyticsUnit, setAnalyticsUnit] = useState<AnalyticsUnit>('monthTotal');
  const [status, setStatus] = useState<string | null>('管理データを読み込み中...');
  const [error, setError] = useState<string | null>(null);

  const loadAll = async () => {
    setStatus('管理データを読み込み中...');
    setError(null);
    try {
      const [loadedThreads, deleted, analytics, settingResponse] = await Promise.all([
        api.listThreads(),
        api.listDeletedPosts(adminPassword),
        api.listAnalyticsPosts(adminPassword),
        api.getSettings(adminPassword),
      ]);
      setThreads(loadedThreads);
      setDeletedPosts(deleted);
      setAnalyticsPosts(analytics);
      setSettings(settingResponse.settings);
      setCronPath(settingResponse.system?.cronPath ?? '');
      setCronApiUrl(settingResponse.system?.cronApiUrl ?? '');
      setCronApiKey(settingResponse.system?.cronApiKey ?? '');
      setSelectedIds([]);
      setStatus('管理データを読み込みました。');
    } catch (err) {
      setError((err as Error).message);
      setStatus(null);
    }
  };

  useEffect(() => {
    loadAll();
  }, []);

  const guarded = (callback: () => Promise<void>) => async (event?: FormEvent) => {
    event?.preventDefault();
    setError(null);
    await callback();
  };

  const reloadThreads = async () => {
    setThreads(await api.listThreads());
  };

  const reloadDeletedPosts = async () => {
    setDeletedPosts(await api.listDeletedPosts(adminPassword));
  };

  const toggleSelected = (id: string) => {
    setSelectedIds((current) => (
      current.includes(id) ? current.filter((item) => item !== id) : [...current, id]
    ));
  };

  const bulkDelete = guarded(async () => {
    if (selectedIds.length === 0) {
      setError('削除する投稿または返信を選択してください。');
      return;
    }
    setStatus('一括削除中...');
    const response = await api.adminDeletePosts(selectedIds, adminPassword);
    setStatus(response.message);
    setSelectedIds([]);
    await reloadThreads();
    await reloadDeletedPosts();
  });

  const restore = async (id: number) => {
    setError(null);
    setStatus('復元中...');
    const response = await api.restorePost(String(id), adminPassword);
    setStatus(response.message);
    await reloadThreads();
    await reloadDeletedPosts();
  };

  const exportBackup = () => {
    window.location.href = `${apiBase()}?action=exportBackup&admin_password=${encodeURIComponent(adminPassword)}`;
  };

  const importBackup = guarded(async () => {
    if (!backupFile) {
      setError('インポートするバックアップJSONを選択してください。');
      return;
    }
    setStatus('インポート中...');
    const response = await api.importBackup(backupFile, adminPassword);
    setStatus(response.message);
    await loadAll();
  });

  const refreshSocialReactions = guarded(async () => {
    setStatus('SNSリアクションを更新中...');
    const response = await api.refreshSocialReactions(adminPassword);
    setStatus(`${response.message} 更新: ${response.updated}件 / エラー: ${response.errors.length}件`);
    await reloadThreads();
  });

  const saveSettings = guarded(async () => {
    if (!settings) {
      setError('設定を読み込んでください。');
      return;
    }
    setStatus('設定を保存中...');
    const response = await api.updateSettings(settings, adminPassword);
    setStatus(response.message);
  });

  const changeAdminPassword = guarded(async () => {
    if (!newAdminPassword.trim()) {
      setError('新しい管理パスワードを入力してください。');
      return;
    }
    const response = await api.changeAdminPassword(adminPassword, newAdminPassword);
    setStatus(response.message);
    setAdminPassword(newAdminPassword);
    window.localStorage.setItem('threadforgeAdminPassword', newAdminPassword);
    setNewAdminPassword('');
  });

  const updateSetting = (section: keyof Settings, key: string, value: string) => {
    setSettings((current) => {
      if (!current) return current;
      return {
        ...current,
        [section]: {
          ...current[section],
          [key]: value,
        },
      };
    });
  };

  return (
    <div className="admin-page">
      <section className="card admin-system-card">
        <h1>管理</h1>
        <div className="admin-system-message" aria-live="polite">
          {status && <p className="status">{status}</p>}
          {error && <p className="error">エラー: {error}</p>}
        </div>
      </section>

      {settings && (
        <>
          <nav className="admin-tabs" aria-label="管理メニュー">
            {adminTabs.map((tab) => (
              <button
                key={tab.id}
                type="button"
                className={activeTab === tab.id ? 'active' : undefined}
                onClick={() => setActiveTab(tab.id)}
              >
                {tab.label}
              </button>
            ))}
          </nav>

          {activeTab === 'posts' && (
            <section className="card">
              <h2>投稿管理</h2>
              <p>投稿と返信を複数選択して、管理者権限で一括削除できます。</p>
              <div className="button-row align-right admin-bulk-actions">
                <button type="button" className="danger" onClick={bulkDelete}>チェックした項目を一括削除</button>
              </div>
              <SelectableThreadList threads={threads} selectedIds={selectedIds} onToggle={toggleSelected} multiple />
            </section>
          )}

          {activeTab === 'maintenance' && (
            <section className="card">
              <div className="form-card">
                <h2>cron設定</h2>
                <p>SNSリアクションはcronまたは外部サービスから自動更新できます。更新対象は投稿から7日以内の親投稿のみです。</p>
                <label>
                  Cron用ファイルパス
                  <input value={cronPath} readOnly onFocus={(event) => event.currentTarget.select()} />
                </label>
                <label>
                  API定期実行URL
                  <input value={`${cronApiUrl}${cronApiKey}`} readOnly onFocus={(event) => event.currentTarget.select()} />
                </label>
                <label>
                  APIキー
                  <input value={cronApiKey} readOnly onFocus={(event) => event.currentTarget.select()} />
                </label>
              </div>
              <h2>管理パスワード変更</h2>
              <div className="button-row align-right">
                <button type="button" onClick={refreshSocialReactions}>SNSリアクションを更新</button>
              </div>
              <form onSubmit={changeAdminPassword} className="form-card">
                <label>
                  新しい管理パスワード
                  <input type="password" value={newAdminPassword} onChange={(event) => setNewAdminPassword(event.target.value)} />
                </label>
                <div className="button-row align-right">
                  <button type="submit">管理パスワードを変更</button>
                </div>
              </form>
            </section>
          )}

          {activeTab === 'backup' && (
            <div className="admin-backup-stack">
              <section className="card">
                <h2>バックアップ</h2>
                <p>DBと画像をまとめてバックアップJSONとしてダウンロードします。</p>
                <div className="button-row align-right">
                  <button type="button" onClick={exportBackup}>DBと画像をエクスポート</button>
                </div>
              </section>
              <section className="card">
                <h2>インポート</h2>
                <p>バックアップJSONをアップロードしてDBと画像を復元します。</p>
                <form onSubmit={importBackup} className="form-card">
                  <label>
                    バックアップJSON
                    <input type="file" accept="application/json,.json" onChange={(event) => setBackupFile(event.target.files?.[0] ?? null)} />
                  </label>
                  <div className="button-row align-right">
                    <button type="submit">インポートして復元</button>
                  </div>
                </form>
              </section>
            </div>
          )}

          {activeTab === 'analytics' && (
            <section className="card admin-analytics-card">
              <h2>アナリティクス</h2>
              <div className="admin-analytics-controls">
                <label>
                  表示データ
                  <select value={analyticsMetric} onChange={(event) => setAnalyticsMetric(event.target.value as AnalyticsMetric)}>
                    {analyticsMetricOptions.map((option) => (
                      <option key={option.id} value={option.id}>{option.label}</option>
                    ))}
                  </select>
                </label>
                <label>
                  単位
                  <select value={analyticsUnit} onChange={(event) => setAnalyticsUnit(event.target.value as AnalyticsUnit)}>
                    {analyticsUnitOptions.map((option) => (
                      <option key={option.id} value={option.id}>{option.label}</option>
                    ))}
                  </select>
                </label>
              </div>
              <AnalyticsChart rows={buildAnalyticsRows(analyticsPosts, analyticsMetric, analyticsUnit)} />
            </section>
          )}

          {activeTab === 'settings' && (
            <section className="card">
              <h2>掲示板設定</h2>
              <p>HOMEリンク先、取説、投稿機能、色設定をここで設定できます。</p>
              <SettingsForm
                values={{ ...settings.config, ...settings.skin }}
                onChange={(key, value) => updateSetting(key in settings.skin ? 'skin' : 'config', key, value)}
              />
              <div className="button-row align-right">
                <button type="button" onClick={saveSettings}>設定を保存</button>
              </div>
            </section>
          )}

          {activeTab === 'deleted' && (
            <section className="card">
              <h2>削除済み投稿</h2>
              {deletedPosts.length === 0 && <p>削除済み投稿はありません。</p>}
              {deletedPosts.map((post) => (
                <div className="admin-deleted-row" key={post.id}>
                  <strong>No.{adminDisplayNo(post)} {post.title || '無題'}</strong>
                  <span>{post.name} / 削除日時: {post.deleted_at}</span>
                  <button type="button" onClick={() => restore(post.id)}>復元する</button>
                </div>
              ))}
            </section>
          )}
        </>
      )}
    </div>
  );
};

function SettingsForm({
  values,
  onChange,
}: {
  values: Record<string, string | number | boolean>;
  onChange: (key: string, value: string) => void;
}) {
  return (
    <>
      {settingGroups.map((group) => {
        const keys = group.keys.filter((key) => key in values);
        if (keys.length === 0) {
          return null;
        }
        return (
          <div className="admin-settings-grid" key={group.title}>
            {group.title && <h3 className="admin-settings-heading">{group.title}</h3>}
            {keys.map((key) => {
              const value = values[key];
              const label = settingLabels[key] ?? key;
              const stringValue = String(value);
              const disabled = isDisabledPlatformSetting(values, key);
              return (
                <label key={key} className={key === 'manualBody' ? 'admin-setting-wide' : undefined}>
                  <span>{label}</span>
                  {key === 'manualBody' ? (
                    <textarea value={stringValue} rows={12} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
                  ) : isBooleanSetting(key) ? (
                    <select value={stringValue} onChange={(event) => onChange(key, event.target.value)}>
                      <option value="true">ON</option>
                      <option value="false">OFF</option>
                    </select>
                  ) : isSecretSetting(key) ? (
                    <input type="password" value={stringValue} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
                  ) : key.endsWith('Color') ? (
                    <input type="color" value={stringValue} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
                  ) : (
                    <input value={stringValue} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
                  )}
                </label>
              );
            })}
          </div>
        );
      })}
    </>
  );
}

function isBooleanSetting(key: string): boolean {
  return key.endsWith('Enabled');
}

function isSecretSetting(key: string): boolean {
  return key.endsWith('Secret') || key.endsWith('Token') || key === 'blueskyAppPassword';
}

function isDisabledPlatformSetting(values: Record<string, string | number | boolean>, key: string): boolean {
  const platformPrefixes = [
    ['tweet', 'tweetEnabled'],
    ['bluesky', 'blueskyEnabled'],
    ['mastodon', 'mastodonEnabled'],
    ['misskey', 'misskeyEnabled'],
  ];
  const match = platformPrefixes.find(([prefix]) => key.startsWith(prefix));
  if (!match || key === match[1]) {
    return false;
  }
  return !(values[match[1]] === true || values[match[1]] === 'true');
}

const settingGroups = [
  { title: '基本', keys: ['bbsTitle', 'homePageUrl', 'manualTitle', 'manualBody', 'gdgdEnabled', 'gdgdLabel', 'logView', 'maxUploadBytes', 'maxImageWidth', 'maxImageHeight', 'normalFrameColor', 'gdgdFrameColor', 'backgroundColor'] },
  { title: '簡単リアクション', keys: ['eejanaikaOmigotoText', 'eejanaikaOmigotoColor', 'eejanaikaGoodjobText', 'eejanaikaGoodjobColor', 'eejanaikaEejanaikaText', 'eejanaikaEejanaikaColor'] },
  { title: 'SNS共通', keys: ['socialHashtags'] },
  { title: 'X', keys: ['tweetEnabled', 'tweetBaseUrl', 'tweetConsumerKey', 'tweetConsumerSecret', 'tweetAccessToken', 'tweetAccessTokenSecret'] },
  { title: 'Bluesky', keys: ['blueskyEnabled', 'blueskyServiceUrl', 'blueskyPublicApiUrl', 'blueskyHandle', 'blueskyAppPassword'] },
  { title: 'Mastodon', keys: ['mastodonEnabled', 'mastodonInstanceUrl', 'mastodonAccessToken', 'mastodonVisibility'] },
  { title: 'Misskey', keys: ['misskeyEnabled', 'misskeyInstanceUrl', 'misskeyAccessToken'] },
];

const adminTabs: Array<{ id: AdminTab; label: string }> = [
  { id: 'posts', label: '投稿管理' },
  { id: 'maintenance', label: '保守' },
  { id: 'backup', label: 'バックアップ' },
  { id: 'analytics', label: 'アナリティクス' },
  { id: 'settings', label: '掲示板設定' },
  { id: 'deleted', label: '削除済み' },
];

const analyticsMetricOptions: Array<{ id: AnalyticsMetric; label: string; value: (post: Post) => number }> = [
  { id: 'postCount', label: '投稿数', value: (post) => post.analytics_kind === 'access' ? 0 : 1 },
  { id: 'commentCount', label: 'コメント数', value: (post) => post.analytics_kind === 'access' ? 0 : post.reply_count ?? post.replies?.length ?? 0 },
  { id: 'accessCount', label: 'アクセス数', value: (post) => post.access_count ?? 0 },
  { id: 'viewCount', label: '閲覧数', value: (post) => post.board_reactions?.views ?? post.view_count ?? 0 },
  { id: 'boardEejanaika', label: 'ええじゃ数', value: (post) => post.board_reactions?.eejanaika ?? 0 },
  { id: 'boardOmigoto', label: 'お美事数', value: (post) => post.board_reactions?.omigoto ?? 0 },
  { id: 'boardGoodjob', label: 'いい仕事数', value: (post) => post.board_reactions?.goodjob ?? 0 },
  { id: 'xLikes', label: 'Xいいね数', value: (post) => post.social_reactions?.x?.likes ?? 0 },
  { id: 'xReposts', label: 'Xリポスト数', value: (post) => post.social_reactions?.x?.reposts ?? 0 },
  { id: 'xImpressions', label: 'X表示数', value: (post) => post.social_reactions?.x?.impressions ?? 0 },
  { id: 'blueskyLikes', label: 'Blueskyいいね数', value: (post) => post.social_reactions?.bluesky?.likes ?? 0 },
  { id: 'blueskyReposts', label: 'Blueskyリポスト数', value: (post) => post.social_reactions?.bluesky?.reposts ?? 0 },
  { id: 'mastodonBoosts', label: 'Mastodonブースト数', value: (post) => post.social_reactions?.mastodon?.boosts ?? 0 },
  { id: 'mastodonFavs', label: 'Mastodon favo数', value: (post) => post.social_reactions?.mastodon?.favs ?? 0 },
  {
    id: 'misskeyReactions',
    label: 'Misskeyリアクション数',
    value: (post) => {
      const reactions = post.social_reactions?.misskey;
      return reactions ? reactions.fire + reactions.eyes + reactions.cry + reactions.thinking + reactions.party + reactions.other : 0;
    },
  },
  { id: 'misskeyFire', label: 'Misskey 🔥数', value: (post) => post.social_reactions?.misskey?.fire ?? 0 },
  { id: 'misskeyEyes', label: 'Misskey 👀数', value: (post) => post.social_reactions?.misskey?.eyes ?? 0 },
  { id: 'misskeyCry', label: 'Misskey 😭数', value: (post) => post.social_reactions?.misskey?.cry ?? 0 },
  { id: 'misskeyThinking', label: 'Misskey 🤔数', value: (post) => post.social_reactions?.misskey?.thinking ?? 0 },
  { id: 'misskeyParty', label: 'Misskey 🎉数', value: (post) => post.social_reactions?.misskey?.party ?? 0 },
  { id: 'misskeyOther', label: 'Misskey その他数', value: (post) => post.social_reactions?.misskey?.other ?? 0 },
];

const analyticsUnitOptions: Array<{ id: AnalyticsUnit; label: string }> = [
  { id: 'dayTotal', label: '日の合計' },
  { id: 'dayCumulative', label: '日の累積' },
  { id: 'monthTotal', label: '月の合計' },
  { id: 'monthAverage', label: '月平均' },
  { id: 'monthCumulative', label: '月の累積' },
  { id: 'yearTotal', label: '年の合計' },
  { id: 'yearAverage', label: '年平均' },
  { id: 'yearCumulative', label: '年の累積' },
];

type AnalyticsRow = {
  label: string;
  total: number;
  divisor: number;
  value: number;
};

function AnalyticsChart({ rows }: { rows: AnalyticsRow[] }) {
  const max = Math.max(...rows.map((row) => row.value), 0);

  if (rows.length === 0) {
    return <p>表示できる統計データはありません。</p>;
  }

  return (
    <div className="admin-analytics-output">
      <AnalyticsLineChart rows={rows} max={max} />
      <div className="admin-analytics-chart" role="img" aria-label="投稿作品の統計グラフ">
        {rows.map((row) => (
          <div className="admin-analytics-bar-row" key={row.label}>
            <span className="admin-analytics-bar-label">{row.label}</span>
            <div className="admin-analytics-bar-track">
              <span className="admin-analytics-bar" style={{ width: `${row.value === 0 || max === 0 ? 0 : Math.max(3, (row.value / max) * 100)}%` }} />
            </div>
            <span className="admin-analytics-bar-value">{formatAnalyticsValue(row.value)}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function AnalyticsLineChart({ rows, max }: { rows: AnalyticsRow[]; max: number }) {
  const width = 720;
  const height = 220;
  const padding = { top: 18, right: 18, bottom: 48, left: 42 };
  const chartWidth = width - padding.left - padding.right;
  const chartHeight = height - padding.top - padding.bottom;
  const denominator = max > 0 ? max : 1;
  const points = rows.map((row, index) => {
    const x = padding.left + (rows.length === 1 ? chartWidth / 2 : (index / (rows.length - 1)) * chartWidth);
    const y = padding.top + chartHeight - (row.value / denominator) * chartHeight;
    return { ...row, x, y };
  });
  const path = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
  const ticks = [0, denominator / 2, denominator];

  return (
    <div className="admin-analytics-line-wrap">
      <svg className="admin-analytics-line-chart" viewBox={`0 0 ${width} ${height}`} role="img" aria-label="投稿作品の推移グラフ">
        {ticks.map((tick) => {
          const y = padding.top + chartHeight - (tick / denominator) * chartHeight;
          return (
            <g key={tick}>
              <line x1={padding.left} x2={width - padding.right} y1={y} y2={y} className="admin-analytics-grid-line" />
              <text x={padding.left - 8} y={y + 4} textAnchor="end" className="admin-analytics-axis-label">{formatAnalyticsValue(tick)}</text>
            </g>
          );
        })}
        <line x1={padding.left} x2={width - padding.right} y1={padding.top + chartHeight} y2={padding.top + chartHeight} className="admin-analytics-axis-line" />
        <line x1={padding.left} x2={padding.left} y1={padding.top} y2={padding.top + chartHeight} className="admin-analytics-axis-line" />
        {path && <path d={path} className="admin-analytics-line" />}
        {points.map((point, index) => {
          const showLabel = rows.length <= 8 || index === 0 || index === rows.length - 1 || index % Math.ceil(rows.length / 6) === 0;
          return (
            <g key={point.label}>
              <circle cx={point.x} cy={point.y} r="4" className="admin-analytics-line-point" />
              {showLabel && (
                <text x={point.x} y={height - 18} textAnchor="middle" className="admin-analytics-axis-label">
                  {point.label}
                </text>
              )}
            </g>
          );
        })}
      </svg>
    </div>
  );
}

function buildAnalyticsRows(posts: Post[], metric: AnalyticsMetric, unit: AnalyticsUnit): AnalyticsRow[] {
  const metricOption = analyticsMetricOptions.find((option) => option.id === metric) ?? analyticsMetricOptions[0];
  const buckets = new Map<string, { total: number; date: Date }>();

  posts.forEach((post) => {
    const date = parsePostDate(post.created_at);
    if (!date) return;
    const key = analyticsBucketKey(date, unit);
    const current = buckets.get(key) ?? { total: 0, date };
    current.total += metricOption.value(post);
    buckets.set(key, current);
  });

  let cumulative = 0;

  return Array.from(buckets.entries())
    .sort(([, left], [, right]) => left.date.getTime() - right.date.getTime())
    .map(([label, bucket]) => {
      const divisor = analyticsDivisor(bucket.date, unit);
      cumulative += bucket.total;
      const value = isCumulativeAnalyticsUnit(unit) ? cumulative : bucket.total / divisor;
      return {
        label,
        total: bucket.total,
        divisor,
        value,
      };
    });
}

function analyticsBucketKey(date: Date, unit: AnalyticsUnit): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  if (unit === 'dayTotal' || unit === 'dayCumulative') {
    return `${year}-${month}-${day}`;
  }
  if (unit === 'yearTotal' || unit === 'yearAverage' || unit === 'yearCumulative') {
    return String(year);
  }
  return `${year}-${month}`;
}

function analyticsDivisor(date: Date, unit: AnalyticsUnit): number {
  if (unit === 'monthAverage') {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
  }
  if (unit === 'yearAverage') {
    return 12;
  }
  return 1;
}

function isCumulativeAnalyticsUnit(unit: AnalyticsUnit): boolean {
  return unit === 'dayCumulative' || unit === 'monthCumulative' || unit === 'yearCumulative';
}

function parsePostDate(value: string): Date | null {
  const normalized = value.includes('T') ? value : value.replace(' ', 'T');
  const date = new Date(normalized);
  return Number.isNaN(date.getTime()) ? null : date;
}

function formatAnalyticsValue(value: number): string {
  if (Number.isInteger(value)) {
    return value.toLocaleString('ja-JP');
  }
  return value.toLocaleString('ja-JP', { maximumFractionDigits: 2 });
}

const settingLabels: Record<string, string> = {
  bbsTitle: '掲示板タイトル',
  homePageUrl: 'HOMEリンク先',
  manualTitle: '取説タイトル',
  manualBody: '取説本文',
  tweetEnabled: 'Tweet機能',
  tweetBaseUrl: 'Tweet先URLベース',
  tweetConsumerKey: 'Consumer Key',
  tweetConsumerSecret: 'Consumer Secret',
  tweetAccessToken: 'Access Token',
  tweetAccessTokenSecret: 'Access Token Secret',
  blueskyEnabled: 'Bluesky機能',
  blueskyServiceUrl: 'Bluesky PDS URL',
  blueskyPublicApiUrl: 'Bluesky公開API URL',
  blueskyHandle: 'Bluesky Handle',
  blueskyAppPassword: 'Bluesky App Password',
  mastodonEnabled: 'Mastodon機能',
  mastodonInstanceUrl: 'MastodonインスタンスURL',
  mastodonAccessToken: 'Mastodon Access Token',
  mastodonVisibility: 'Mastodon公開範囲',
  misskeyEnabled: 'Misskey機能',
  misskeyInstanceUrl: 'MisskeyインスタンスURL',
  misskeyAccessToken: 'Misskey Access Token',
  eejanaikaOmigotoText: 'お美事の文字列',
  eejanaikaOmigotoColor: 'お美事の文字色',
  eejanaikaGoodjobText: 'いい仕事の文字列',
  eejanaikaGoodjobColor: 'いい仕事の文字色',
  eejanaikaEejanaikaText: 'ええじゃの文字列',
  eejanaikaEejanaikaColor: 'ええじゃの文字色',
  socialHashtags: 'SNS投稿ハッシュタグ',
  gdgdEnabled: 'gdgd投稿機能',
  gdgdLabel: 'gdgd投稿の表示名',
  logView: '一覧表示件数',
  maxUploadBytes: '最大アップロードサイズ(byte)',
  maxImageWidth: '最大画像幅(px)',
  maxImageHeight: '最大画像高さ(px)',
  normalFrameColor: '通常投稿の枠色',
  gdgdFrameColor: 'gdgd投稿の枠色',
  backgroundColor: '背景色',
};

function adminDisplayNo(post: Post): string {
  if (post.parent_id === 0) {
    return String(post.display_no ?? post.id);
  }
  return `${post.display_no ?? post.thread_id}-${post.reply_no ?? post.id}`;
}

export default AdminPage;
