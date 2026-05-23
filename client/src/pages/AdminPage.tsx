import { CSSProperties, FormEvent, useEffect, useRef, useState } from 'react';
import { AdminUser, api, apiBase } from '../api';
import { Post } from '../types';
import SelectableThreadList from '../components/SelectableThreadList';

type Settings = {
  config: Record<string, SettingValue>;
  skin: Record<string, SettingValue>;
};

type SettingValue = string | number | boolean | string[];
type AdminTab = 'posts' | 'deleted' | 'maintenance' | 'users' | 'analytics' | 'settings' | 'design';
type AnalyticsMetric = 'postCount' | 'commentCount' | 'accessCount' | 'viewCount' | 'boardEejanaika' | 'boardOmigoto' | 'boardGoodjob' | 'xLikes' | 'xReposts' | 'xImpressions' | 'blueskyLikes' | 'blueskyReposts' | 'mastodonBoosts' | 'mastodonFavs' | 'misskeyReactions' | 'misskeyFire' | 'misskeyEyes' | 'misskeyCry' | 'misskeyThinking' | 'misskeyParty' | 'misskeyOther';
type AnalyticsUnit = 'dayTotal' | 'dayCumulative' | 'monthTotal' | 'monthAverage' | 'monthCumulative' | 'yearTotal' | 'yearAverage' | 'yearCumulative';

const AdminPage = () => {
  const [adminPassword, setAdminPassword] = useState(() => {
    return window.localStorage.getItem('threadforgeAdminPassword') || '';
  });
  const [activeTab, setActiveTab] = useState<AdminTab>('posts');
  const [threads, setThreads] = useState<Post[]>([]);
  const [deletedPosts, setDeletedPosts] = useState<Post[]>([]);
  const [analyticsPosts, setAnalyticsPosts] = useState<Post[]>([]);
  const [adminUsers, setAdminUsers] = useState<AdminUser[]>([]);
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [bulkRangeStart, setBulkRangeStart] = useState('');
  const [bulkRangeEnd, setBulkRangeEnd] = useState('');
  const [deletedRangeStart, setDeletedRangeStart] = useState('');
  const [deletedRangeEnd, setDeletedRangeEnd] = useState('');
  const [bulkCompact, setBulkCompact] = useState(false);
  const [deletedCompact, setDeletedCompact] = useState(true);
  const [deletedPurgeEnabled, setDeletedPurgeEnabled] = useState(false);
  const [userDeleteEnabled, setUserDeleteEnabled] = useState(false);
  const [editingUser, setEditingUser] = useState<AdminUser | null>(null);
  const [editingUserPassword, setEditingUserPassword] = useState('');
  const [settings, setSettings] = useState<Settings | null>(null);
  const [savedSettings, setSavedSettings] = useState<Settings | null>(null);
  const [cronPath, setCronPath] = useState('');
  const [cronApiUrl, setCronApiUrl] = useState('');
  const [cronApiKey, setCronApiKey] = useState('');
  const [adminPasswordConfigured, setAdminPasswordConfigured] = useState<boolean | null>(null);
  const backupImportInputRef = useRef<HTMLInputElement | null>(null);
  const [newAdminPassword, setNewAdminPassword] = useState('');
  const [newAdminPasswordConfirm, setNewAdminPasswordConfirm] = useState('');
  const [initialAdminPassword, setInitialAdminPassword] = useState('');
  const [initialAdminPasswordConfirm, setInitialAdminPasswordConfirm] = useState('');
  const [analyticsMetric, setAnalyticsMetric] = useState<AnalyticsMetric>('postCount');
  const [analyticsUnit, setAnalyticsUnit] = useState<AnalyticsUnit>('monthTotal');
  const [status, setStatus] = useState<string | null>('管理データを読み込み中...');
  const [error, setError] = useState<string | null>(null);

  const loadAll = async (password = adminPassword) => {
    setStatus('管理データを読み込み中...');
    setError(null);
    try {
      const [loadedThreads, deleted, analytics, settingResponse, userResponse] = await Promise.all([
        api.listThreads(),
        api.listDeletedPosts(password),
        api.listAnalyticsPosts(password),
        api.getSettings(password),
        api.listAdminUsers(password),
      ]);
      setThreads(loadedThreads);
      setDeletedPosts(deleted);
      setAnalyticsPosts(analytics);
      setAdminUsers(userResponse.users);
      setSettings(settingResponse.settings);
      setSavedSettings(cloneSettings(settingResponse.settings));
      setCronPath(settingResponse.system?.cronPath ?? '');
      setCronApiUrl(settingResponse.system?.cronApiUrl ?? '');
      setCronApiKey(settingResponse.system?.cronApiKey ?? '');
      setAdminPasswordConfigured(settingResponse.system?.adminPasswordConfigured ?? null);
      setSelectedIds([]);
      setEditingUser(null);
      setDeletedPurgeEnabled(false);
      setUserDeleteEnabled(false);
      window.localStorage.setItem('threadforgeAdminPassword', password);
      setStatus('管理データを読み込みました。');
    } catch (err) {
      window.localStorage.removeItem('threadforgeAdminPassword');
      setAdminPassword('');
      setError((err as Error).message);
      setStatus(null);
    }
  };

  useEffect(() => {
    const boot = async () => {
      try {
        const response = await api.adminStatus();
        setAdminPasswordConfigured(response.adminPasswordConfigured);
        if (adminPassword !== '') {
          await loadAll(adminPassword);
          return;
        }
        setStatus(null);
      } catch (err) {
        setError((err as Error).message);
        setStatus(null);
      }
    };
    boot();
  }, []);

  const guarded = (callback: () => Promise<void>) => async (event?: FormEvent) => {
    event?.preventDefault();
    setError(null);
    await callback();
  };

  const submitAdminPassword = guarded(async () => {
    await loadAll(adminPassword);
  });

  const initializeAdminPassword = guarded(async () => {
    if (!initialAdminPassword.trim()) {
      setError('管理者パスワードを入力してください。');
      return;
    }
    if (initialAdminPassword !== initialAdminPasswordConfirm) {
      setError('確認用パスワードが一致しません。');
      return;
    }
    const response = await api.initializeAdminPassword(initialAdminPassword);
    setStatus(response.message);
    setAdminPassword(initialAdminPassword);
    setAdminPasswordConfigured(true);
    window.localStorage.setItem('threadforgeAdminPassword', initialAdminPassword);
    setInitialAdminPassword('');
    setInitialAdminPasswordConfirm('');
    await loadAll(initialAdminPassword);
  });

  const reloadThreads = async () => {
    setThreads(await api.listThreads());
  };

  const reloadDeletedPosts = async () => {
    setDeletedPosts(await api.listDeletedPosts(adminPassword));
  };

  const reloadAdminUsers = async () => {
    const response = await api.listAdminUsers(adminPassword);
    setAdminUsers(response.users);
  };

  const toggleSelected = (id: string) => {
    setSelectedIds((current) => {
      const thread = threads.find((item) => String(item.id) === id);
      if (thread) {
        const ids = [String(thread.id), ...(thread.replies ?? []).map((reply) => String(reply.id))];
        return current.includes(id)
          ? current.filter((item) => !ids.includes(item))
          : [...new Set([...current, ...ids])];
      }
      const parent = threads.find((item) => (item.replies ?? []).some((reply) => String(reply.id) === id));
      if (parent && current.includes(String(parent.id))) {
        return current;
      }
      return current.includes(id) ? current.filter((item) => item !== id) : [...current, id];
    });
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

  const addBulkRange = () => {
    if (isMixedParentAndReplyRange(bulkRangeStart, bulkRangeEnd)) {
      setError('親投稿指定と返信指定は混在できません。開始と終了は同じ種類で指定してください。');
      return;
    }
    const ids = idsFromDisplayRange(threads, bulkRangeStart, bulkRangeEnd);
    if (ids.length === 0) {
      setError('範囲に一致する投稿がありません。');
      return;
    }
    setSelectedIds((current) => [...new Set([...current, ...ids])]);
    setStatus(`${ids.length}件を選択しました。`);
    setError(null);
  };

  const restore = async (id: number) => {
    setError(null);
    setStatus('復元中...');
    const response = await api.restorePost(String(id), adminPassword);
    setStatus(response.message);
    await reloadThreads();
    await reloadDeletedPosts();
  };

  const purgeDeleted = async (id: number) => {
    if (!deletedPurgeEnabled) {
      setError('消去を有効にしてください。');
      return;
    }
    if (!window.confirm('画像やコメントなどのデータを消去します。表示番号は残ります。実行しますか？')) {
      return;
    }
    setError(null);
    setStatus('投稿データを消去中...');
    const response = await api.purgePostData(String(id), adminPassword);
    setStatus(response.message);
    await reloadThreads();
    await reloadDeletedPosts();
  };

  const destroyDeleted = async (id: number) => {
    if (!deletedPurgeEnabled) {
      setError('消去を有効にしてください。');
      return;
    }
    if (!window.confirm('投稿番号ごと完全削除します。表示番号が前詰めされます。実行しますか？')) {
      return;
    }
    setError(null);
    setStatus('投稿番号を消去中...');
    const response = await api.destroyPostNumber(String(id), adminPassword);
    setStatus(response.message);
    await reloadThreads();
    await reloadDeletedPosts();
  };

  const purgeDeletedRange = async (stage: 1 | 2) => {
    if (!deletedPurgeEnabled) {
      setError('消去を有効にしてください。');
      return;
    }
    if (isMixedParentAndReplyRange(deletedRangeStart, deletedRangeEnd)) {
      setError('親投稿指定と返信指定は混在できません。開始と終了は同じ種類で指定してください。');
      return;
    }
    const ids = idsFromDisplayRange(deletedPosts, deletedRangeStart, deletedRangeEnd);
    if (ids.length === 0) {
      setError('範囲に一致する削除済み投稿がありません。');
      return;
    }
    const message = stage === 1
      ? `${ids.length}件のデータを消去します。表示番号は残ります。`
      : `${ids.length}件を番号ごと完全削除します。表示番号が前詰めされます。`;
    if (!window.confirm(`${message} 実行しますか？`)) {
      return;
    }
    setError(null);
    setStatus(stage === 1 ? '投稿データを消去中...' : '投稿番号を消去中...');
    for (const id of ids) {
      if (stage === 1) {
        await api.purgePostData(id, adminPassword);
      } else {
        await api.destroyPostNumber(id, adminPassword);
      }
    }
    setStatus(stage === 1 ? `${ids.length}件の投稿データを消去しました。` : `${ids.length}件を番号ごと完全削除しました。`);
    await reloadThreads();
    await reloadDeletedPosts();
  };

  const exportBackup = guarded(async () => {
    setStatus('エクスポート中...');
    const response = await fetch(`${apiBase()}?action=exportBackup&admin_password=${encodeURIComponent(adminPassword)}`);
    if (!response.ok) {
      throw new Error(`${response.status} ${response.statusText}`);
    }
    await saveBlobWithPicker(await response.blob(), 'threadforge-full-backup.zip');
    setStatus('フルバックアップZIPをエクスポートしました。');
  });

  const importBackupFile = async (file: File | null) => {
    if (!file) {
      setError('インポートするフルバックアップZIPを選択してください。');
      return;
    }
    setStatus('インポート中...');
    const response = await api.importBackup(file, adminPassword);
    setStatus(response.message);
    await loadAll();
  };

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
    setSavedSettings(cloneSettings(settings));
    setStatus(response.message);
  });

  const resetDesignSettings = () => {
    if (!settings || !savedSettings) {
      return;
    }
    setSettings({
      config: { ...settings.config, ...pickKeys(savedSettings.config, designConfigKeys) },
      skin: { ...savedSettings.skin },
    });
    setStatus('掲示板デザインを保存済みの設定に戻しました。');
    setError(null);
  };

  const startEditUser = (user: AdminUser) => {
    if (!window.confirm(`ユーザー No.${user.id} ${user.display_name} の情報を表示しますか？`)) {
      return;
    }
    setEditingUser(user);
    setEditingUserPassword('');
  };

  const saveAdminUser = guarded(async () => {
    if (!editingUser) {
      return;
    }
    const response = await api.adminUpdateUser({ ...editingUser, login_password: editingUserPassword }, adminPassword);
    setStatus(response.message);
    setEditingUser(null);
    setEditingUserPassword('');
    await reloadAdminUsers();
    await reloadThreads();
  });

  const deleteAdminUser = async (user: AdminUser, stage: 1 | 2) => {
    if (!userDeleteEnabled) {
      setError('ユーザー情報の消去を有効にしてください。');
      return;
    }
    const message = stage === 1
      ? `ユーザー No.${user.id} の登録情報を消去します。ユーザー番号は残ります。`
      : `ユーザー No.${user.id} を番号ごと完全削除します。ユーザー番号が前詰めされます。`;
    if (!window.confirm(`${message} 実行しますか？`)) {
      return;
    }
    setError(null);
    const response = await api.adminDeleteUser(user.id, stage, adminPassword);
    setStatus(response.message);
    setEditingUser(null);
    await reloadAdminUsers();
    await reloadThreads();
  };

  const exitAdmin = () => {
    window.localStorage.removeItem('threadforgeAdminPassword');
    setAdminPassword('');
    setSettings(null);
    setThreads([]);
    setDeletedPosts([]);
    setAnalyticsPosts([]);
    setSelectedIds([]);
    setAdminPasswordConfigured(null);
    setActiveTab('posts');
    setStatus('管理画面から出ました。');
    setError(null);
  };

  const exportSettingsJson = async (section: keyof Settings) => {
    if (!settings) {
      setError('設定を読み込んでください。');
      return;
    }
    const blob = new Blob([JSON.stringify(settings[section], null, 2)], { type: 'application/json' });
    await saveBlobWithPicker(blob, section === 'skin' ? 'threadforge-design.json' : 'threadforge-settings.json');
  };

  const importSettingsJson = async (section: keyof Settings, file: File | null) => {
    if (!file) {
      return;
    }
    try {
      const parsed = JSON.parse(await file.text());
      if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
        throw new Error('JSONの形式が正しくありません。');
      }
      setSettings((current) => current ? {
        ...current,
        [section]: {
          ...current[section],
          ...parsed,
        },
      } : current);
      setStatus(`${section === 'skin' ? '掲示板デザイン' : '掲示板設定'}JSONを読み込みました。保存すると反映されます。`);
      setError(null);
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const changeAdminPassword = guarded(async () => {
    if (!newAdminPassword.trim()) {
      setError('新しい管理パスワードを入力してください。');
      return;
    }
    if (newAdminPassword !== newAdminPasswordConfirm) {
      setError('確認用パスワードが一致しません。');
      return;
    }
    const response = await api.changeAdminPassword(adminPassword, newAdminPassword);
    setStatus(response.message);
    setAdminPassword(newAdminPassword);
    setAdminPasswordConfigured(true);
    window.localStorage.setItem('threadforgeAdminPassword', newAdminPassword);
    setNewAdminPassword('');
    setNewAdminPasswordConfirm('');
  });

  const updateSetting = (section: keyof Settings, key: string, value: SettingValue) => {
    setSettings((current) => {
      if (!current) return current;
      const nextSection = {
        ...current[section],
        [key]: value,
      };
      if (section === 'config' && key === 'bbsTitle') {
        nextSection.manualTitle = value;
      }
      return {
        ...current,
        [section]: nextSection,
      };
    });
  };

  return (
    <div className="admin-page">
      <section className="card admin-system-card">
        <h1>管理</h1>
        {settings ? (
          <div className="admin-auth-form">
            <label>
              管理者パスワード
              <input type="password" value="********" readOnly />
            </label>
            <button type="button" className="secondary" onClick={exitAdmin}>管理画面から出る</button>
          </div>
        ) : adminPasswordConfigured === false ? (
          <form className="admin-auth-form admin-initial-setup-form" onSubmit={initializeAdminPassword}>
            <label>
              初期管理者パスワード
              <input
                type="password"
                value={initialAdminPassword}
                onChange={(event) => setInitialAdminPassword(event.target.value)}
                autoComplete="new-password"
              />
            </label>
            <label>
              確認
              <input
                type="password"
                value={initialAdminPasswordConfirm}
                onChange={(event) => setInitialAdminPasswordConfirm(event.target.value)}
                autoComplete="new-password"
              />
            </label>
            <button type="submit">管理者パスワードを設定</button>
          </form>
        ) : (
          <form className="admin-auth-form" onSubmit={submitAdminPassword}>
            <label>
              管理者パスワード
              <input
                type="password"
                value={adminPassword}
                onChange={(event) => setAdminPassword(event.target.value)}
                autoComplete="current-password"
              />
            </label>
            <button type="submit">管理画面に入る</button>
          </form>
        )}
        <div className="admin-system-message" aria-live="polite">
          {status && <p className="status">{status}</p>}
          {error && <p className="error">エラー: {error}</p>}
          {adminPasswordConfigured === false && !settings && (
            <p className="warning">
              初期設定が未完了です。ここで管理者パスワードを設定してください。
            </p>
          )}
        </div>
      </section>

      {settings && (
        <>
          <nav className="admin-tabs" aria-label="管理メニュー">
            {(activeTab === 'users' ? userAdminTabs : adminTabs).map((tab) => (
              <button
                key={tab.id}
                type="button"
                className={activeTab === tab.id ? 'active' : undefined}
                onClick={() => {
                  setActiveTab(tab.id);
                  if (tab.id !== 'users') {
                    setEditingUser(null);
                    setUserDeleteEnabled(false);
                  }
                }}
              >
                {tab.label}
              </button>
            ))}
          </nav>

          {activeTab === 'posts' && (
            <section className="card">
              <h2>一括削除</h2>
              <div className="admin-section-intro-row">
                <p>投稿と返信を複数選択して、管理者権限で一括削除できます。</p>
                <DisplayModeSwitch compact={bulkCompact} onChange={setBulkCompact} />
              </div>
              <div className="admin-range-actions">
                <label>
                  開始
                  <input value={bulkRangeStart} onChange={(event) => setBulkRangeStart(event.target.value)} placeholder="例: 90 / 99-3" />
                </label>
                <label>
                  終了
                  <input value={bulkRangeEnd} onChange={(event) => setBulkRangeEnd(event.target.value)} placeholder="例: 99 / 99-4" />
                </label>
                <button type="button" className="secondary" onClick={addBulkRange}>範囲を選択</button>
                <button type="button" className="secondary" onClick={() => setSelectedIds([])} disabled={selectedIds.length === 0}>選択をすべて解除</button>
              </div>
              <div className="button-row align-right admin-bulk-actions">
                <button type="button" className="danger" onClick={bulkDelete}>チェックした項目を一括削除</button>
              </div>
              <SelectableThreadList threads={threads} selectedIds={selectedIds} onToggle={toggleSelected} multiple compact={bulkCompact} />
            </section>
          )}

          {activeTab === 'maintenance' && (
            <section className="card admin-maintenance-card">
              <h2>保守</h2>
              <p>運用中のデータ保全、復元、外部SNS情報の更新、管理者パスワード変更を行います。</p>

              <section className="admin-maintenance-section">
                <h3>管理者パスワード変更</h3>
                <div className="admin-maintenance-section-body">
                  <p>管理画面へ入るためのパスワードを変更します。変更後は新しいパスワードで入り直してください。</p>
                  <form onSubmit={changeAdminPassword} className="admin-maintenance-form admin-password-change-form">
                    <label>
                      新しい管理者パスワード
                      <input type="password" value={newAdminPassword} onChange={(event) => setNewAdminPassword(event.target.value)} />
                    </label>
                    <label>
                      確認
                      <input type="password" value={newAdminPasswordConfirm} onChange={(event) => setNewAdminPasswordConfirm(event.target.value)} />
                    </label>
                    <div className="button-row align-right">
                      <button type="submit">変更</button>
                    </div>
                  </form>
                </div>
              </section>

              <section className="admin-maintenance-section">
                <h3>SNSリアクション更新</h3>
                <div className="admin-maintenance-section-body">
                  <p>SNS側のリアクション数を手動で取得し直します。cronを待たずに最新化したい場合に使います。</p>
                  <div className="button-row align-right">
                    <button type="button" onClick={refreshSocialReactions}>更新</button>
                  </div>
                </div>
              </section>

              <section className="admin-maintenance-section">
                <h3>ユーザー登録情報</h3>
                <div className="admin-maintenance-section-body">
                  <p>登録済みユーザーの情報編集や消去は、専用画面で行います。</p>
                  <div className="button-row align-right">
                    <button type="button" className="secondary" onClick={() => setActiveTab('users')}>編集</button>
                  </div>
                </div>
              </section>

              <section className="admin-maintenance-section">
                <h3>フルバックアップ インポート/エクスポート</h3>
                <div className="admin-maintenance-section-body">
                  <p>投稿、返信、画像、ユーザー、作品登録、アクセス履歴、設定をフルバックアップZIPとして保存、またはフルバックアップZIPから復元します。ログインセッションは含めません。</p>
                  <div className="admin-import-export-actions">
                    <button type="button" className="secondary" onClick={exportBackup}>エクスポート</button>
                    <button type="button" className="secondary" onClick={() => backupImportInputRef.current?.click()}>インポート</button>
                    <input
                      ref={backupImportInputRef}
                      type="file"
                      aria-label="フルバックアップZIPファイル"
                      accept="application/zip,.zip,application/json,.json"
                      className="visually-hidden-file"
                      onChange={(event) => {
                        void guarded(() => importBackupFile(event.currentTarget.files?.[0] ?? null))();
                        event.currentTarget.value = '';
                      }}
                    />
                  </div>
                </div>
              </section>
            </section>
          )}
          {activeTab === 'users' && (
            <section className="card admin-maintenance-card">
              <h2>ユーザー登録情報</h2>
              <div className="admin-section-intro-row">
                <p>登録済みユーザーの情報編集、登録情報の消去、ユーザー番号ごとの完全削除を行います。</p>
                <label className="admin-danger-enable">
                  <input type="checkbox" checked={userDeleteEnabled} onChange={(event) => setUserDeleteEnabled(event.target.checked)} />
                  消去を有効にする
                </label>
              </div>
              <div className="button-row align-right admin-bulk-actions">
                <button type="button" className="secondary" onClick={() => {
                  setActiveTab('maintenance');
                  setEditingUser(null);
                  setUserDeleteEnabled(false);
                }}>保守に戻る</button>
              </div>
              <div className="admin-user-list admin-user-maintenance-list">
                {adminUsers.length === 0 && <p>登録ユーザーはありません。</p>}
                {adminUsers.map((user) => (
                  <article className="admin-user-row" key={user.id}>
                    <div>
                      <strong>No.{user.id} {user.display_name}</strong>
                      <span>ID: {user.login_id} / 投稿: {user.post_count} / 自分の作品: {user.claim_count}</span>
                    </div>
                    <div className="button-row align-right">
                      <button type="button" className="admin-fixed-action-button" onClick={() => startEditUser(user)}>編集</button>
                      <button type="button" className="danger admin-fixed-action-button" disabled={!userDeleteEnabled} onClick={() => void deleteAdminUser(user, 1)}>情報消去</button>
                      <button type="button" className="danger admin-fixed-action-button" disabled={!userDeleteEnabled} onClick={() => void deleteAdminUser(user, 2)}>番号ごと消去</button>
                    </div>
                  </article>
                ))}
              </div>
              {editingUser && (
                <form className="admin-user-edit-form" onSubmit={saveAdminUser}>
                  <h4>ユーザー No.{editingUser.id} を編集</h4>
                  <label>
                    ID
                    <input value={editingUser.login_id} onChange={(event) => setEditingUser({ ...editingUser, login_id: event.target.value })} />
                  </label>
                  <label>
                    名前
                    <input value={editingUser.display_name} maxLength={30} onChange={(event) => setEditingUser({ ...editingUser, display_name: event.target.value })} />
                  </label>
                  <label>
                    投稿パスワード
                    <input value={editingUser.post_password} maxLength={8} onChange={(event) => setEditingUser({ ...editingUser, post_password: event.target.value })} />
                  </label>
                  <label>
                    URL / HOME
                    <input value={editingUser.home_url ?? ''} onChange={(event) => setEditingUser({ ...editingUser, home_url: event.target.value })} />
                  </label>
                  <label>
                    ログインパスワード再設定（任意）
                    <input type="password" value={editingUserPassword} onChange={(event) => setEditingUserPassword(event.target.value)} />
                  </label>
                  <div className="button-row align-right">
                    <button type="button" className="secondary" onClick={() => setEditingUser(null)}>閉じる</button>
                    <button type="submit">保存</button>
                  </div>
                </form>
              )}
            </section>
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
            <>
            <section className="card">
              <h2>掲示板設定</h2>
              <div className="admin-settings-intro">
                <p>HOMEリンク先、取説、投稿機能、SNS連携、cron設定をここで設定できます。</p>
                <button type="button" onClick={saveSettings}>設定を保存</button>
              </div>
              <div className="form-card">
                <h3>cron設定</h3>
                <p>SNSリアクションはcronまたは外部サービスから自動更新できます。</p>
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
              <SettingsForm
                values={settings.config}
                groups={configSettingGroups}
                onChange={(key, value) => updateSetting('config', key, value)}
              />
            </section>
            <section className="card admin-json-tools-card">
              <SettingsJsonTools
                label="掲示板設定"
                onExport={() => exportSettingsJson('config')}
                onImport={(file) => importSettingsJson('config', file)}
              />
            </section>
            </>
          )}

          {activeTab === 'design' && (
            <>
            <section className="card">
              <h2>掲示板デザイン</h2>
              <div className="admin-settings-intro">
                <p>投稿枠や背景など、見た目に関する設定をここで調整できます。</p>
                <div className="button-row align-right">
                  <button type="button" className="secondary" onClick={resetDesignSettings}>編集前に戻す</button>
                  <button type="button" onClick={saveSettings}>設定を保存</button>
                </div>
              </div>
              <SettingsForm
                values={{ ...settings.skin, ...settings.config }}
                groups={designSettingGroups}
                onChange={(key, value) => updateSetting(key in settings.skin ? 'skin' : 'config', key, value)}
              />
              <DesignPreview settings={settings} />
            </section>
            <section className="card admin-json-tools-card">
              <SettingsJsonTools
                label="掲示板デザイン"
                onExport={() => exportSettingsJson('skin')}
                onImport={(file) => importSettingsJson('skin', file)}
              />
            </section>
            </>
          )}
          {activeTab === 'deleted' && (
            <section className="card">
              <h2>復元/消去</h2>
              <div className="admin-section-intro-row">
                <p>削除済み投稿の復元、データ消去、番号ごとの完全削除を行います。</p>
                <div className="admin-safety-controls">
                  <DisplayModeSwitch compact={deletedCompact} onChange={setDeletedCompact} />
                  <label className="admin-danger-enable">
                    <input type="checkbox" checked={deletedPurgeEnabled} onChange={(event) => setDeletedPurgeEnabled(event.target.checked)} />
                    消去を有効にする
                  </label>
                </div>
              </div>
              <div className="admin-range-actions">
                <label>
                  開始
                  <input value={deletedRangeStart} onChange={(event) => setDeletedRangeStart(event.target.value)} placeholder="例: 90 / 90-1" />
                </label>
                <label>
                  終了
                  <input value={deletedRangeEnd} onChange={(event) => setDeletedRangeEnd(event.target.value)} placeholder="例: 99 / 90-6" />
                </label>
                <button type="button" className="danger admin-fixed-action-button" disabled={!deletedPurgeEnabled} onClick={() => void purgeDeletedRange(1)}>範囲を消去</button>
                <button type="button" className="danger admin-fixed-action-button" disabled={!deletedPurgeEnabled} onClick={() => void purgeDeletedRange(2)}>範囲を番号ごと消去</button>
              </div>
              {deletedPosts.length === 0 && <p>削除済み投稿はありません。</p>}
              {deletedPosts.map((post) => (
                <div className={deletedCompact ? 'admin-deleted-row admin-deleted-row-compact' : 'admin-deleted-row'} key={post.id}>
                  <strong>No.{adminDisplayNo(post)} {post.title || '無題'}</strong>
                  <span>{post.name} / 削除日時: {post.deleted_at}</span>
                  {!deletedCompact && <p>{post.message}</p>}
                  <button type="button" className="admin-fixed-action-button" onClick={() => restore(post.id)}>復元</button>
                  <button type="button" className="danger admin-fixed-action-button" disabled={!deletedPurgeEnabled} onClick={() => void purgeDeleted(post.id)}>消去</button>
                  <button type="button" className="danger admin-fixed-action-button" disabled={!deletedPurgeEnabled} onClick={() => void destroyDeleted(post.id)}>番号ごと消去</button>
                </div>
              ))}
            </section>
          )}
        </>
      )}
    </div>
  );
};

function DisplayModeSwitch({ compact, onChange }: { compact: boolean; onChange: (value: boolean) => void }) {
  return (
    <label className="admin-display-switch">
      <span>フル表示</span>
      <input
        type="checkbox"
        role="switch"
        checked={compact}
        onChange={(event) => onChange(event.target.checked)}
        aria-label="簡易表示に切り替え"
      />
      <span className="admin-display-switch-track" aria-hidden="true">
        <span className="admin-display-switch-thumb" />
      </span>
      <span>簡易表示</span>
    </label>
  );
}

function DesignPreview({ settings }: { settings: Settings }) {
  const skin = settings.skin;
  const config = settings.config;
  return (
    <section className="admin-design-preview" style={designPreviewStyle(skin)}>
      <h3>サンプル画面</h3>
      <div className="design-preview-nav">HOME | 一覧 | 投稿 | 検索 | 順位</div>
      <article className="design-preview-thread normal">
        <header>[No.1] 通常投稿のサンプル</header>
        <p>NAME: Blank　投稿日時: 2026/05/23 12:00</p>
        <div>設定変更はこのプレビューへ即時反映されます。</div>
        <div className="design-preview-reply">返信コメントの表示サンプル</div>
      </article>
      <article className="design-preview-thread special">
        <header>[No.2] {String(config.gdgdLabel ?? '特殊投稿')}のサンプル</header>
        <div className="design-preview-actions">
          <button type="button">コメント</button>
          <button type="button" style={{ color: String(config.eejanaikaOmigotoColor ?? '#ff72ff') }}>{shortReactionLabel(config.eejanaikaOmigotoText)}</button>
          <button type="button" style={{ color: String(config.eejanaikaGoodjobColor ?? '#27a8ff') }}>{shortReactionLabel(config.eejanaikaGoodjobText)}</button>
          <button type="button" style={{ color: String(config.eejanaikaEejanaikaColor ?? '#fff200') }}>{shortReactionLabel(config.eejanaikaEejanaikaText)}</button>
        </div>
      </article>
    </section>
  );
}

function SettingsForm({
  values,
  groups,
  onChange,
}: {
  values: Record<string, SettingValue>;
  groups: SettingGroup[];
  onChange: (key: string, value: SettingValue) => void;
}) {
  return (
    <>
      {groups.map((group) => {
        const keys = group.keys.filter((key) => key in values);
        if (keys.length === 0) {
          return null;
        }
        return (
          <div className={settingsGroupClassName(group)} key={group.title}>
            {group.title && <h3 className="admin-settings-heading">{group.title}</h3>}
            {group.title === 'X' && (
              <p className="admin-setting-note">X連携は未デバッグです。現時点では動作を保証しません。</p>
            )}
            {keys.map((key) => {
              const value = values[key];
              const label = settingLabels[key] ?? key;
              const stringValue = settingDisplayValue(key, value);
              const disabled = isDisabledPlatformSetting(values, key);
              if (key === 'allowedImageTypes') {
                const selected = Array.isArray(value) ? value.map(String) : String(value).split(',').map((item) => item.trim()).filter(Boolean);
                return (
                  <fieldset key={key} className="admin-setting-wide admin-checkbox-group">
                    <legend>{label}</legend>
                    {allowedImageTypeOptions.map((extension) => (
                      <label key={extension}>
                        <span>{extension}</span>
                        <input
                          type="checkbox"
                          checked={selected.includes(extension)}
                          onChange={(event) => {
                            const next = event.target.checked
                              ? [...new Set([...selected, extension])]
                              : selected.filter((item) => item !== extension);
                            onChange(key, next);
                          }}
                        />
                      </label>
                    ))}
                  </fieldset>
                );
              }
              if (isReactionTextKey(key)) {
                const colorKey = reactionColorKeyForTextKey(key);
                return (
                  <label key={key} className={['admin-setting-inline-row admin-setting-wide', disabled ? 'admin-setting-disabled' : ''].filter(Boolean).join(' ')}>
                    <span>{label}</span>
                    <input value={stringValue} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
                    {colorKey && (
                      <>
                        <input type="color" value={String(values[colorKey] ?? '#ffffff')} onChange={(event) => onChange(colorKey, event.target.value)} />
                        <input value={String(values[colorKey] ?? '')} onChange={(event) => onChange(colorKey, event.target.value)} />
                      </>
                    )}
                  </label>
                );
              }
              if (isReactionColorKey(key)) {
                return null;
              }
              const labelClassName = [
                key === 'manualBody' ? 'admin-setting-wide' : '',
                key === 'socialHashtags' ? 'admin-setting-wide' : '',
                key.endsWith('Color') ? 'admin-color-row' : '',
                disabled ? 'admin-setting-disabled' : '',
              ].filter(Boolean).join(' ') || undefined;
              return (
                <label key={key} className={labelClassName}>
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
                    <>
                      <input type="color" value={toColorInputValue(stringValue)} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
                      <input value={stringValue} onChange={(event) => onChange(key, event.target.value)} disabled={disabled} />
                    </>
                  ) : (
                    <input value={stringValue} onChange={(event) => onChange(key, settingStoredValue(key, event.target.value))} disabled={disabled} />
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

function isDisabledPlatformSetting(values: Record<string, SettingValue>, key: string): boolean {
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

type SettingGroup = { title: string; keys: string[] };

const allowedImageTypeOptions = ['gif', 'png', 'jpeg', 'jpg', 'bmp'];

function settingsGroupClassName(group: SettingGroup): string {
  return [
    'admin-settings-grid',
    group.title === '色設定' ? 'admin-settings-grid-stacked' : '',
  ].filter(Boolean).join(' ');
}

const reactionRows = [
  { textKey: 'eejanaikaOmigotoText', colorKey: 'eejanaikaOmigotoColor' },
  { textKey: 'eejanaikaGoodjobText', colorKey: 'eejanaikaGoodjobColor' },
  { textKey: 'eejanaikaEejanaikaText', colorKey: 'eejanaikaEejanaikaColor' },
];

function isReactionTextKey(key: string): boolean {
  return reactionRows.some((row) => row.textKey === key);
}

function isReactionColorKey(key: string): boolean {
  return reactionRows.some((row) => row.colorKey === key);
}

function reactionColorKeyForTextKey(key: string): string | undefined {
  return reactionRows.find((row) => row.textKey === key)?.colorKey;
}

function toColorInputValue(value: string): string {
  return /^#[0-9a-fA-F]{6}$/.test(value) ? value : '#ffffff';
}

function settingDisplayValue(key: string, value: SettingValue): string {
  if (key === 'maxUploadBytes') {
    const bytes = Number(value);
    return Number.isFinite(bytes) && bytes > 0 ? String(Math.round(bytes / 1000)) : '';
  }
  return String(value);
}

function settingStoredValue(key: string, value: string): SettingValue {
  if (key === 'maxUploadBytes') {
    const kb = Number(value);
    return Number.isFinite(kb) && kb > 0 ? Math.round(kb * 1000) : '';
  }
  return value;
}

function cloneSettings(settings: Settings): Settings {
  return {
    config: { ...settings.config },
    skin: { ...settings.skin },
  };
}

function pickKeys(values: Record<string, SettingValue>, keys: string[]): Record<string, SettingValue> {
  return Object.fromEntries(keys.filter((key) => key in values).map((key) => [key, values[key]]));
}

function shortReactionLabel(value: SettingValue | undefined): string {
  const text = String(value ?? '');
  return text.length > 4 ? `${text.slice(0, 4)}..` : text;
}

function designPreviewStyle(skin: Record<string, SettingValue>): CSSProperties {
  return {
    '--preview-bg': String(skin.backgroundColor ?? '#000000'),
    '--preview-text': String(skin.pageTextColor ?? '#ffffff'),
    '--preview-link': String(skin.linkColor ?? '#58a6ff'),
    '--preview-panel-bg': String(skin.panelBackgroundColor ?? '#101821'),
    '--preview-panel-title': String(skin.panelTitleBackgroundColor ?? '#5b6572'),
    '--preview-panel-border': String(skin.panelBorderColor ?? '#738196'),
    '--preview-button-bg': String(skin.buttonBackgroundColor ?? '#3f74ff'),
    '--preview-button-text': String(skin.buttonTextColor ?? '#ffffff'),
    '--preview-button-border': String(skin.buttonBorderColor ?? '#8fb0ff'),
    '--preview-normal-frame': String(skin.normalFrameColor ?? '#a23dff'),
    '--preview-normal-header': String(skin.normalHeaderColor ?? '#39988a'),
    '--preview-normal-text': String(skin.normalTextColor ?? '#ffffff'),
    '--preview-special-frame': String(skin.gdgdFrameColor ?? '#6dffc0'),
    '--preview-special-header': String(skin.gdgdHeaderColor ?? '#7f00a8'),
    '--preview-special-text': String(skin.gdgdTextColor ?? '#ffffff'),
    '--preview-reply-border': String(skin.replyBorderColor ?? '#7a8495'),
  } as CSSProperties;
}

const configSettingGroups: SettingGroup[] = [
  { title: '基本', keys: ['bbsTitle', 'homePageUrl', 'manualBody', 'gdgdEnabled', 'gdgdLabel', 'logView', 'maxUploadBytes', 'maxImageWidth', 'maxImageHeight', 'allowedImageTypes'] },
  { title: 'SNS共通', keys: ['socialHashtags'] },
  { title: 'X', keys: ['tweetEnabled', 'tweetBaseUrl', 'tweetConsumerKey', 'tweetConsumerSecret', 'tweetAccessToken', 'tweetAccessTokenSecret'] },
  { title: 'Bluesky', keys: ['blueskyEnabled', 'blueskyServiceUrl', 'blueskyPublicApiUrl', 'blueskyHandle', 'blueskyAppPassword'] },
  { title: 'Mastodon', keys: ['mastodonEnabled', 'mastodonInstanceUrl', 'mastodonAccessToken', 'mastodonVisibility'] },
  { title: 'Misskey', keys: ['misskeyEnabled', 'misskeyInstanceUrl', 'misskeyAccessToken'] },
];

const designSettingGroups: SettingGroup[] = [
  { title: '簡単リアクション', keys: ['eejanaikaOmigotoText', 'eejanaikaOmigotoColor', 'eejanaikaGoodjobText', 'eejanaikaGoodjobColor', 'eejanaikaEejanaikaText', 'eejanaikaEejanaikaColor'] },
  { title: '色設定', keys: ['backgroundColor', 'pageTextColor', 'linkColor', 'panelBackgroundColor', 'panelTitleBackgroundColor', 'panelBorderColor', 'labelColor', 'inputBackgroundColor', 'inputTextColor', 'buttonBackgroundColor', 'buttonTextColor', 'buttonBorderColor', 'secondaryButtonBackgroundColor', 'secondaryButtonTextColor', 'secondaryButtonBorderColor', 'normalFrameColor', 'normalHeaderColor', 'normalTextColor', 'gdgdFrameColor', 'gdgdHeaderColor', 'gdgdTextColor', 'replyBorderColor', 'dangerColor', 'successColor'] },
];

const designConfigKeys = ['eejanaikaOmigotoText', 'eejanaikaOmigotoColor', 'eejanaikaGoodjobText', 'eejanaikaGoodjobColor', 'eejanaikaEejanaikaText', 'eejanaikaEejanaikaColor'];

const adminTabs: Array<{ id: AdminTab; label: string }> = [
  { id: 'posts', label: '一括削除' },
  { id: 'deleted', label: '復元/消去' },
  { id: 'maintenance', label: '保守' },
  { id: 'analytics', label: 'アナリティクス' },
  { id: 'settings', label: '掲示板設定' },
  { id: 'design', label: '掲示板デザイン' },
];

const userAdminTabs: Array<{ id: AdminTab; label: string }> = adminTabs.map((tab) => (
  tab.id === 'maintenance' ? { id: 'users', label: 'ユーザー登録情報' } : tab
));

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

function SettingsJsonTools({
  label,
  onExport,
  onImport,
}: {
  label: string;
  onExport: () => void | Promise<void>;
  onImport: (file: File | null) => void;
}) {
  const inputRef = useRef<HTMLInputElement | null>(null);
  return (
    <div className="admin-json-tools form-card">
      <h3>{label} インポート/エクスポート</h3>
      <div className="admin-import-export-actions">
        <button type="button" className="secondary" onClick={() => { void onExport(); }}>エクスポート</button>
        <button type="button" className="secondary" onClick={() => inputRef.current?.click()}>インポート</button>
        <input
          ref={inputRef}
          type="file"
          aria-label={`${label}JSONファイル`}
          accept="application/json,.json"
          className="visually-hidden-file"
          onChange={(event) => {
            onImport(event.target.files?.[0] ?? null);
            event.currentTarget.value = '';
          }}
        />
      </div>
    </div>
  );
}

type WindowWithFilePicker = Window & {
  showSaveFilePicker?: (options: {
    suggestedName?: string;
    types?: Array<{ description: string; accept: Record<string, string[]> }>;
  }) => Promise<{
    createWritable: () => Promise<{
      write: (blob: Blob) => Promise<void>;
      close: () => Promise<void>;
    }>;
  }>;
};

async function saveBlobWithPicker(blob: Blob, suggestedName: string): Promise<void> {
  const picker = (window as WindowWithFilePicker).showSaveFilePicker;
  if (picker) {
    const handle = await picker({
      suggestedName,
      types: [{ description: 'JSON', accept: { 'application/json': ['.json'] } }],
    });
    const writable = await handle.createWritable();
    await writable.write(blob);
    await writable.close();
    return;
  }

  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = suggestedName;
  anchor.click();
  URL.revokeObjectURL(url);
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
  bbsTitle: '掲示板/取説タイトル',
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
  eejanaikaOmigotoText: 'リアクション1',
  eejanaikaOmigotoColor: 'リアクション1の文字色',
  eejanaikaGoodjobText: 'リアクション2',
  eejanaikaGoodjobColor: 'リアクション2の文字色',
  eejanaikaEejanaikaText: 'リアクション3',
  eejanaikaEejanaikaColor: 'リアクション3の文字色',
  socialHashtags: 'SNS投稿ハッシュタグ',
  gdgdEnabled: '特殊投稿機能',
  gdgdLabel: '特殊投稿の表示名',
  logView: '一覧表示件数',
  maxUploadBytes: '最大アップロードサイズ(KB)',
  maxImageWidth: '最大画像幅(px)',
  maxImageHeight: '最大画像高さ(px)',
  allowedImageTypes: 'アップロード可能な画像形式',
  normalFrameColor: '通常投稿の枠色',
  normalHeaderColor: '通常投稿の見出し色',
  normalTextColor: '通常投稿の文字色',
  gdgdFrameColor: '特殊投稿の枠色',
  gdgdHeaderColor: '特殊投稿の見出し色',
  gdgdTextColor: '特殊投稿の文字色',
  backgroundColor: '背景色',
  pageTextColor: 'ページ文字色',
  linkColor: 'リンク色',
  panelBackgroundColor: 'パネル背景色',
  panelTitleBackgroundColor: 'パネル見出し背景色',
  panelBorderColor: 'パネル枠色',
  labelColor: 'ラベル色',
  inputBackgroundColor: '入力欄背景色',
  inputTextColor: '入力欄文字色',
  buttonBackgroundColor: 'ボタン背景色',
  buttonTextColor: 'ボタン文字色',
  buttonBorderColor: 'ボタン枠色',
  secondaryButtonBackgroundColor: 'グレーボタン背景色',
  secondaryButtonTextColor: 'グレーボタン文字色',
  secondaryButtonBorderColor: 'グレーボタン枠色',
  replyBorderColor: '返信区切り線色',
  dangerColor: '警告色',
  successColor: '成功色',
};

function adminDisplayNo(post: Post): string {
  if (post.parent_id === 0) {
    return String(post.display_no ?? post.id);
  }
  return `${post.display_no ?? post.thread_id}-${post.reply_no ?? post.id}`;
}

function idsFromDisplayRange(posts: Post[], startText: string, endText: string): string[] {
  const refs = displayRefs(posts);
  const selected = new Set<string>();
  const start = parseDisplayRef(startText);
  const end = parseDisplayRef(endText);
  if (!start) {
    return [];
  }

  if (!end) {
    matchingRefsForSingle(refs, start).forEach((ref) => addRefWithChildren(selected, ref, refs));
    return [...selected];
  }

  const startKey = displayRefKey(start);
  const endKey = displayRefKey(end);
  const min = Math.min(startKey, endKey);
  const max = Math.max(startKey, endKey);
  let rangeRefs = refs
    .filter((ref) => {
      const key = displayRefKey(ref);
      if (start.replyNo === null && end.replyNo === null) {
        return ref.replyNo === null && key >= min && key <= max;
      }
      return key >= min && key <= max;
    });
  if (rangeRefs.length === 0 && start.replyNo === null && end.replyNo === null) {
    rangeRefs = refs.filter((ref) => {
      const key = displayRefKey(ref);
      return key >= min && key <= max;
    });
  }
  rangeRefs.forEach((ref) => addRefWithChildren(selected, ref, refs));

  return [...selected];
}

function isMixedParentAndReplyRange(startText: string, endText: string): boolean {
  const start = parseDisplayRef(startText);
  const end = parseDisplayRef(endText);
  if (!start || !end) {
    return false;
  }
  return (start.replyNo === null) !== (end.replyNo === null);
}

function matchingRefsForSingle(refs: DisplayRef[], target: DisplayRefKey): DisplayRef[] {
  if (target.replyNo !== null) {
    return refs.filter((ref) => ref.displayNo === target.displayNo && ref.replyNo === target.replyNo);
  }
  const topLevelRefs = refs.filter((ref) => ref.replyNo === null && ref.displayNo === target.displayNo);
  if (topLevelRefs.length > 0) {
    return topLevelRefs;
  }
  return refs.filter((ref) => ref.displayNo === target.displayNo);
}

function addRefWithChildren(selected: Set<string>, ref: DisplayRef, refs: DisplayRef[]): void {
  selected.add(ref.id);
  if (ref.replyNo === null) {
    refs
      .filter((candidate) => candidate.replyNo !== null && candidate.displayNo === ref.displayNo)
      .forEach((candidate) => selected.add(candidate.id));
  }
}

type DisplayRefKey = { displayNo: number; replyNo: number | null };
type DisplayRef = DisplayRefKey & { id: string };

function parseDisplayRef(value: string): DisplayRefKey | null {
  const trimmed = value.trim();
  if (trimmed === '') {
    return null;
  }
  const reply = trimmed.match(/^(\d+)\s*-\s*(\d+)$/);
  if (reply) {
    return { displayNo: Number(reply[1]), replyNo: Number(reply[2]) };
  }
  const displayNo = Number(trimmed);
  if (!Number.isInteger(displayNo) || displayNo < 1) {
    return null;
  }
  return { displayNo, replyNo: null };
}

function displayRefKey(ref: DisplayRefKey): number {
  return ref.displayNo * 10000 + (ref.replyNo ?? 0);
}

function displayRefs(posts: Post[]): DisplayRef[] {
  return posts.flatMap((post) => {
    const displayNo = Number(post.display_no ?? post.id);
    if (post.parent_id !== 0) {
      return [{
        id: String(post.id),
        displayNo,
        replyNo: Number(post.reply_no ?? post.id),
      }];
    }
    const refs = [{
      id: String(post.id),
      displayNo,
      replyNo: null,
    }];
    return refs.concat((post.replies ?? []).map((reply) => ({
      id: String(reply.id),
      displayNo,
      replyNo: Number(reply.reply_no ?? reply.id),
    })));
  });
}

export default AdminPage;
