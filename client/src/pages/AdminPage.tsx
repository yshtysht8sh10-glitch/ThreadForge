import { FormEvent, useEffect, useState } from 'react';
import { api, apiBase } from '../api';
import { Post } from '../types';
import SelectableThreadList from '../components/SelectableThreadList';

type Settings = {
  config: Record<string, string | number | boolean>;
  skin: Record<string, string | number | boolean>;
};

type AdminTab = 'posts' | 'maintenance' | 'backup' | 'settings' | 'deleted';

const DEFAULT_HIDDEN_ADMIN_PASSWORD = 'admin';

const AdminPage = () => {
  const [adminPassword, setAdminPassword] = useState(() => {
    return window.localStorage.getItem('threadforgeAdminPassword') || DEFAULT_HIDDEN_ADMIN_PASSWORD;
  });
  const [activeTab, setActiveTab] = useState<AdminTab>('posts');
  const [threads, setThreads] = useState<Post[]>([]);
  const [deletedPosts, setDeletedPosts] = useState<Post[]>([]);
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [settings, setSettings] = useState<Settings | null>(null);
  const [backupFile, setBackupFile] = useState<File | null>(null);
  const [legacyDir, setLegacyDir] = useState('data');
  const [newAdminPassword, setNewAdminPassword] = useState('');
  const [status, setStatus] = useState<string | null>('管理データを読み込み中...');
  const [error, setError] = useState<string | null>(null);

  const loadAll = async () => {
    setStatus('管理データを読み込み中...');
    setError(null);
    try {
      const [loadedThreads, deleted, settingResponse] = await Promise.all([
        api.listThreads(),
        api.listDeletedPosts(adminPassword),
        api.getSettings(adminPassword),
      ]);
      setThreads(loadedThreads);
      setDeletedPosts(deleted);
      setSettings(settingResponse.settings);
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

  const importLegacyBbsnote = guarded(async () => {
    setStatus('旧BBSnoteログをインポート中...');
    const response = await api.importLegacyBbsnote(legacyDir, adminPassword);
    setStatus(`${response.message} スレッド ${response.imported_threads}件 / 返信 ${response.imported_replies}件 / スキップ ${response.skipped_threads + response.skipped_replies}件 / 画像欠損 ${response.missing_images.length}件`);
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
              <h2>管理パスワード変更</h2>
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
              <section className="card">
                <h2>旧BBSnoteログ追加インポート</h2>
                <p className="form-help">既存DB、画像、管理設定は初期化せず、旧ログを追加で取り込みます。</p>
                <form onSubmit={importLegacyBbsnote} className="form-card">
                  <label>
                    旧BBSnoteログディレクトリ
                    <input value={legacyDir} onChange={(event) => setLegacyDir(event.target.value)} placeholder="data" />
                  </label>
                  <div className="button-row align-right">
                    <button type="submit">旧BBSnoteログを追加インポート</button>
                  </div>
                </form>
              </section>
            </div>
          )}

          {activeTab === 'settings' && (
            <section className="card">
              <h2>掲示板設定</h2>
              <p>HOMEリンク先、取説、投稿機能、色設定をここで設定できます。</p>
              <SettingsForm values={settings.config} onChange={(key, value) => updateSetting('config', key, value)} />
              <SettingsForm values={settings.skin} onChange={(key, value) => updateSetting('skin', key, value)} compact />
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
  compact = false,
}: {
  values: Record<string, string | number | boolean>;
  onChange: (key: string, value: string) => void;
  compact?: boolean;
}) {
  const tweetEnabled = values.tweetEnabled === true || values.tweetEnabled === 'true';

  return (
    <div className={compact ? 'admin-settings-grid admin-settings-grid-compact' : 'admin-settings-grid'}>
      {Object.entries(values).map(([key, value]) => {
        const label = settingLabels[key] ?? key;
        const stringValue = String(value);
        const disabled = isTweetSetting(key) && key !== 'tweetEnabled' && !tweetEnabled;
        return (
          <label key={key} className={key === 'manualBody' ? 'admin-setting-wide' : undefined}>
            <span>{label}</span>
            {key === 'manualBody' ? (
              <textarea value={stringValue} rows={12} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
            ) : key === 'tweetEnabled' || key === 'gdgdEnabled' ? (
              <select value={stringValue} onChange={(event) => onChange(key, event.target.value)}>
                <option value="true">ON</option>
                <option value="false">OFF</option>
              </select>
            ) : key === 'tweetConsumerSecret' || key === 'tweetAccessTokenSecret' ? (
              <input type="password" value={stringValue} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
            ) : (
              <input value={stringValue} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
            )}
          </label>
        );
      })}
    </div>
  );
}

function isTweetSetting(key: string): boolean {
  return key.startsWith('tweet');
}

const adminTabs: Array<{ id: AdminTab; label: string }> = [
  { id: 'posts', label: '投稿管理' },
  { id: 'maintenance', label: '保守' },
  { id: 'backup', label: 'バックアップ' },
  { id: 'settings', label: '掲示板設定' },
  { id: 'deleted', label: '削除済み' },
];

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
  gdgdEnabled: 'gdgd投稿機能',
  gdgdLabel: 'gdgd投稿の表示名',
  logView: '一覧表示件数',
  maxUploadBytes: '最大アップロードサイズ(byte)',
  maxImageWidth: '最大画像幅(px)',
  maxImageHeight: '最大画像高さ(px)',
  normalFrameColor: '通常投稿の枠色',
  gdgdFrameColor: 'gdgd投稿の枠色',
  tweetOffFrameColor: 'Tweet OFF投稿の枠色',
  backgroundColor: '背景色',
};

function adminDisplayNo(post: Post): string {
  if (post.parent_id === 0) {
    return String(post.display_no ?? post.id);
  }
  return `${post.display_no ?? post.thread_id}-${post.reply_no ?? post.id}`;
}

export default AdminPage;
