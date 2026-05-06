import { FormEvent, useState } from 'react';
import { api, apiBase } from '../api';
import { Post } from '../types';
import SelectableThreadList from '../components/SelectableThreadList';

type Settings = {
  config: Record<string, string | number | boolean>;
  skin: Record<string, string | number | boolean>;
};

type AdminTab = 'posts' | 'maintenance' | 'backup' | 'settings' | 'deleted';

const AdminPage = () => {
  const [adminPassword, setAdminPassword] = useState('');
  const [unlocked, setUnlocked] = useState(false);
  const [activeTab, setActiveTab] = useState<AdminTab>('posts');
  const [threads, setThreads] = useState<Post[]>([]);
  const [deletedPosts, setDeletedPosts] = useState<Post[]>([]);
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [settings, setSettings] = useState<Settings | null>(null);
  const [backupFile, setBackupFile] = useState<File | null>(null);
  const [legacyDir, setLegacyDir] = useState('data');
  const [newAdminPassword, setNewAdminPassword] = useState('');
  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const guarded = (callback: () => Promise<void>) => async (event?: FormEvent) => {
    event?.preventDefault();
    if (!adminPassword.trim()) {
      setError('管理パスワードを入力してください。');
      return;
    }
    setError(null);
    await callback();
  };

  const loadAll = guarded(async () => {
    setStatus('読み込み中...');
    const [loadedThreads, deleted, settingResponse] = await Promise.all([
      api.listThreads(),
      api.listDeletedPosts(adminPassword),
      api.getSettings(adminPassword),
    ]);
    setThreads(loadedThreads);
    setDeletedPosts(deleted);
    setSettings(settingResponse.settings);
    setSelectedIds([]);
    setUnlocked(true);
    setActiveTab('posts');
    setStatus(null);
  });

  const reloadThreads = async () => {
    setThreads(await api.listThreads());
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
  });

  const restore = async (id: number) => {
    if (!adminPassword.trim()) {
      setError('管理パスワードを入力してください。');
      return;
    }
    setStatus('復元中...');
    await api.restorePost(String(id), adminPassword);
    const deleted = await api.listDeletedPosts(adminPassword);
    setDeletedPosts(deleted);
    await reloadThreads();
    setStatus('復元しました。');
  };

  const checkIntegrity = guarded(async () => {
    setStatus('DBを確認中...');
    const result = await api.adminCheckIntegrity(adminPassword);
    setStatus(`${result.message} 孤立返信: ${result.orphan_replies}件 / 画像欠損: ${result.missing_image_post_ids.length}件`);
  });

  const exportBackup = () => {
    if (!adminPassword.trim()) {
      setError('管理パスワードを入力してください。');
      return;
    }
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
      <section className="card">
        <h1>管理</h1>
        <form onSubmit={loadAll} className="form-card">
          <label>
            管理パスワード
            <input type="password" value={adminPassword} onChange={(event) => setAdminPassword(event.target.value)} />
          </label>
          <button type="submit">管理データを読み込む</button>
        </form>
        {status && <p className="status">{status}</p>}
        {error && <p className="error">エラー: {error}</p>}
      </section>

      {unlocked && (
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
              <p>投稿と返信を複数選択できます。管理者権限では投稿パスワードなしで一括削除できます。</p>
              <div className="button-row">
                <button type="button" className="danger" onClick={bulkDelete}>チェックした項目を一括削除</button>
              </div>
              <SelectableThreadList threads={threads} selectedIds={selectedIds} onToggle={toggleSelected} multiple />
            </section>
          )}

          {activeTab === 'maintenance' && (
            <>
              <section className="card">
                <h2>保守</h2>
                <div className="button-row">
                  <button type="button" onClick={checkIntegrity}>DB整合性を確認</button>
                </div>
                <p>旧CGIのシステムインデックス修復は、現行DB版では不要です。代わりに孤立返信と画像欠損を確認します。</p>
              </section>
              <section className="card">
                <h2>管理パスワード変更</h2>
                <form onSubmit={changeAdminPassword} className="form-card">
                  <label>
                    新しい管理パスワード
                    <input type="password" value={newAdminPassword} onChange={(event) => setNewAdminPassword(event.target.value)} />
                  </label>
                  <button type="submit">管理パスワードを変更</button>
                </form>
              </section>
            </>
          )}

          {activeTab === 'backup' && (
            <section className="card">
              <h2>バックアップ / インポート</h2>
              <div className="button-row">
                <button type="button" onClick={exportBackup}>DBと画像をエクスポート</button>
              </div>
              <form onSubmit={importBackup} className="form-card">
                <label>
                  バックアップJSON
                  <input type="file" accept="application/json,.json" onChange={(event) => setBackupFile(event.target.files?.[0] ?? null)} />
                </label>
                <button type="submit">インポートして復元</button>
              </form>
              <form onSubmit={importLegacyBbsnote} className="form-card">
                <label>
                  旧BBSnoteログディレクトリ
                  <input value={legacyDir} onChange={(event) => setLegacyDir(event.target.value)} placeholder="data" />
                </label>
                <button type="submit">旧BBSnoteログを追加インポート</button>
              </form>
              <p className="form-help">旧BBSnoteログの追加インポートは既存DB、画像、管理設定を初期化しません。</p>
            </section>
          )}

          {activeTab === 'settings' && (
            <section className="card">
              <h2>掲示板設定</h2>
              <p>HOMEリンク先、取説、投稿機能、色設定をここで設定できます。保存後、画面表示へ反映されます。</p>
              {settings && (
                <>
                  <SettingsForm values={settings.config} onChange={(key, value) => updateSetting('config', key, value)} />
                  <SettingsForm values={settings.skin} onChange={(key, value) => updateSetting('skin', key, value)} />
                  <button type="button" onClick={saveSettings}>設定を保存</button>
                </>
              )}
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

function SettingsForm({ values, onChange }: { values: Record<string, string | number | boolean>; onChange: (key: string, value: string) => void }) {
  return (
    <div className="admin-settings-grid">
      {Object.entries(values).map(([key, value]) => {
        const label = settingLabels[key] ?? key;
        const stringValue = String(value);
        return (
          <label key={key} className={key === 'manualBody' ? 'admin-setting-wide' : undefined}>
            {label}
            {key === 'manualBody' ? (
              <textarea value={stringValue} rows={12} onChange={(event) => onChange(key, event.target.value)} />
            ) : key === 'tweetEnabled' || key === 'gdgdEnabled' ? (
              <select value={stringValue} onChange={(event) => onChange(key, event.target.value)}>
                <option value="true">ON</option>
                <option value="false">OFF</option>
              </select>
            ) : (
              <input value={stringValue} onChange={(event) => onChange(key, event.target.value)} />
            )}
          </label>
        );
      })}
    </div>
  );
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
