import { FormEvent, useState } from 'react';
import { api, apiBase } from '../api';
import { Post } from '../types';

type Settings = {
  config: Record<string, string | number>;
  skin: Record<string, string | number>;
};

const AdminPage = () => {
  const [adminPassword, setAdminPassword] = useState('');
  const [activePosts, setActivePosts] = useState<Post[]>([]);
  const [deletedPosts, setDeletedPosts] = useState<Post[]>([]);
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [settings, setSettings] = useState<Settings | null>(null);
  const [backupFile, setBackupFile] = useState<File | null>(null);
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
    const [threads, deleted, settingResponse] = await Promise.all([
      api.listThreads(),
      api.listDeletedPosts(adminPassword),
      api.getSettings(adminPassword),
    ]);
    setActivePosts(flattenPosts(threads));
    setDeletedPosts(deleted);
    setSettings(settingResponse.settings);
    setStatus(null);
  });

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
    const threads = await api.listThreads();
    setActivePosts(flattenPosts(threads));
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
      setError('インポートするバックアップファイルを選択してください。');
      return;
    }
    setStatus('インポート中...');
    const response = await api.importBackup(backupFile, adminPassword);
    setStatus(response.message);
    await loadAll();
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

      <section className="card">
        <h2>投稿管理</h2>
        <p>管理者権限では投稿パスワードなしで一括削除できます。</p>
        <div className="button-row">
          <button type="button" className="danger" onClick={bulkDelete}>チェックした項目を一括削除</button>
        </div>
        <div className="admin-post-list">
          {activePosts.map((post) => (
            <label key={post.id} className="admin-post-row">
              <input type="checkbox" checked={selectedIds.includes(String(post.id))} onChange={() => toggleSelected(String(post.id))} />
              <span>No.{post.id}</span>
              <strong>{post.parent_id === 0 ? post.title || '無題' : `返信: ${post.message.slice(0, 40)}`}</strong>
              <span>{post.name}</span>
            </label>
          ))}
        </div>
      </section>

      <section className="card">
        <h2>保守</h2>
        <div className="button-row">
          <button type="button" onClick={checkIntegrity}>DB整合性を確認</button>
        </div>
        <p>旧CGIのシステムインデックス修復は、現行DB版では不要です。代わりに孤立返信と画像欠損を確認します。</p>
      </section>

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
      </section>

      <section className="card">
        <h2>削除済み投稿</h2>
        {deletedPosts.length === 0 && <p>削除済み投稿はありません。</p>}
        {deletedPosts.map((post) => (
          <div className="admin-deleted-row" key={post.id}>
            <strong>No.{post.id} {post.title || '無題'}</strong>
            <span>{post.name} / 削除日時: {post.deleted_at}</span>
            <button type="button" onClick={() => restore(post.id)}>復元する</button>
          </div>
        ))}
      </section>

      <section className="card">
        <h2>config.cgi 相当</h2>
        {!settings && <p>設定を編集するには管理データを読み込んでください。</p>}
        {settings && (
          <SettingsForm values={settings.config} onChange={(key, value) => updateSetting('config', key, value)} />
        )}
      </section>

      <section className="card">
        <h2>skincfg.cgi 相当</h2>
        {settings && (
          <>
            <SettingsForm values={settings.skin} onChange={(key, value) => updateSetting('skin', key, value)} />
            <button type="button" onClick={saveSettings}>設定を保存</button>
          </>
        )}
      </section>
    </div>
  );
};

function SettingsForm({ values, onChange }: { values: Record<string, string | number>; onChange: (key: string, value: string) => void }) {
  return (
    <div className="admin-settings-grid">
      {Object.entries(values).map(([key, value]) => (
        <label key={key}>
          {key}
          <input value={String(value)} onChange={(event) => onChange(key, event.target.value)} />
        </label>
      ))}
    </div>
  );
}

function flattenPosts(threads: Post[]): Post[] {
  return threads.flatMap((thread) => [thread, ...(thread.replies ?? [])]);
}

export default AdminPage;
