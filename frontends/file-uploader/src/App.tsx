import { CSSProperties, FormEvent, useEffect, useMemo, useRef, useState } from 'react';

export type UploadRow = {
  id: number;
  filename: string;
  comment: string;
  sizeBytes: number;
  date: string;
  originalName: string;
  deleteKey: string;
  downloadUrl?: string;
};

type ApiUploaderFile = {
  id: number;
  filename: string;
  originalName: string;
  comment: string;
  sizeBytes: number;
  createdAt: string;
  downloadUrl: string;
};

export type UploaderSettings = {
  title: string;
  homePageUrl: string;
  allowedExtensions: string;
  maxUploadKb: number;
  design: UploaderDesign;
};

export type UploaderDesign = {
  pageBackgroundColor: string;
  pageTextColor: string;
  linkColor: string;
  shellBackgroundColor: string;
  contentBackgroundColor: string;
  formBackgroundColor: string;
  titleStartColor: string;
  titleEndColor: string;
  tableHeaderColor: string;
  borderColor: string;
  buttonBackgroundColor: string;
  buttonTextColor: string;
  activeTabColor: string;
  errorColor: string;
};

type AdminTab = 'bulk-delete' | 'deleted' | 'settings' | 'design' | 'analytics' | 'maintenance';

const defaultDesign: UploaderDesign = {
  pageBackgroundColor: '#eeeeee',
  pageTextColor: '#000000',
  linkColor: '#0000ff',
  shellBackgroundColor: '#2a2a2a',
  contentBackgroundColor: '#ffffff',
  formBackgroundColor: '#eeeeee',
  titleStartColor: '#f7f7f7',
  titleEndColor: '#d8d8d8',
  tableHeaderColor: '#eeeeee',
  borderColor: '#808080',
  buttonBackgroundColor: '#f5f5f5',
  buttonTextColor: '#000000',
  activeTabColor: '#8eb4e3',
  errorColor: '#9b1c1c',
};

const defaultSettings: UploaderSettings = {
  title: 'ファイルアップローダー',
  homePageUrl: '../',
  allowedExtensions: 'gif bmp png jpg jpeg zip txt avi swf',
  maxUploadKb: 20000,
  design: defaultDesign,
};

const initialRows: UploadRow[] = [
  { id: 1134, filename: 'file1134.png', comment: '', sizeBytes: 415, date: '25/12/09-00:25', originalName: 'hiyoko.png', deleteKey: '1134' },
  { id: 1133, filename: 'file1133.png', comment: 'コンパス', sizeBytes: 8192, date: '24/10/12-00:43', originalName: '1.png', deleteKey: '1133' },
  { id: 1132, filename: 'file1132.png', comment: '顔', sizeBytes: 23552, date: '24/09/20-23:30', originalName: 'crn+.png', deleteKey: '1132' },
  { id: 1131, filename: 'file1131.png', comment: '製作希望', sizeBytes: 1342464, date: '24/09/19-00:01', originalName: 'ブルードルフィン220089.png', deleteKey: '1131' },
  { id: 1130, filename: 'file1130.png', comment: '', sizeBytes: 0, date: '24/08/11-09:47', originalName: 'IMG_2839.png', deleteKey: '1130' },
];

function apiBase() {
  return import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8001/api.php';
}

function backendFileUrl(path: string) {
  return resolveBackendFileUrl(path, apiBase(), window.location.href);
}

export function resolveBackendFileUrl(path: string, apiUrl: string, pageUrl: string) {
  return new URL(path, new URL(apiUrl, pageUrl)).toString();
}

async function apiPost<T>(action: string, values: Record<string, string>): Promise<T> {
  const body = new URLSearchParams({ action, ...values });
  const response = await fetch(apiBase(), { method: 'POST', body });
  const data = await response.json();
  if (!response.ok || data.success === false) {
    throw new Error(data.message || 'API request failed.');
  }
  return data;
}

async function apiUpload<T>(file: File, comment: string, deleteKey: string): Promise<T> {
  const body = new FormData();
  body.set('action', 'uploadUploaderFile');
  body.set('file', file);
  body.set('comment', comment);
  body.set('delete_key', deleteKey);
  const response = await fetch(apiBase(), { method: 'POST', body });
  const data = await response.json();
  if (!response.ok || data.success === false) {
    throw new Error(data.message || 'Upload failed.');
  }
  return data;
}

async function apiGet<T>(action: string, values: Record<string, string> = {}): Promise<T> {
  const url = new URL(apiBase(), window.location.href);
  url.searchParams.set('action', action);
  Object.entries(values).forEach(([key, value]) => url.searchParams.set(key, value));
  const response = await fetch(url.toString());
  const data = await response.json();
  if (!response.ok || data.success === false) {
    throw new Error(data.message || 'API request failed.');
  }
  return data;
}

function App() {
  const [rows, setRows] = useState<UploadRow[]>(() => loadJson('threadforgeUploaderRows', initialRows));
  const [deletedRows, setDeletedRows] = useState<UploadRow[]>([]);
  const [settings, setSettings] = useState<UploaderSettings>(() => normalizeSettings(loadJson('threadforgeUploaderSettings', defaultSettings)));
  const [comment, setComment] = useState('');
  const [deleteKey, setDeleteKey] = useState('');
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [uploadError, setUploadError] = useState('');
  const [uploadMessage, setUploadMessage] = useState('');
  const [uploading, setUploading] = useState(false);
  const [fileActionMessage, setFileActionMessage] = useState('');
  const [fileActionError, setFileActionError] = useState('');
  const [showAdmin, setShowAdmin] = useState(false);
  const [adminPassword, setAdminPassword] = useState(() => window.localStorage.getItem('threadforgeUploaderAdminPassword') || '');
  const [adminPasswordConfigured, setAdminPasswordConfigured] = useState<boolean | null>(null);
  const [adminAuthenticated, setAdminAuthenticated] = useState(false);
  const [adminTab, setAdminTab] = useState<AdminTab>('bulk-delete');
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [deletedSelectedIds, setDeletedSelectedIds] = useState<number[]>([]);
  const [adminMessage, setAdminMessage] = useState('');
  const [adminError, setAdminError] = useState('');
  const [settingsDraft, setSettingsDraft] = useState(settings);
  const [newAdminPassword, setNewAdminPassword] = useState('');
  const [newAdminPasswordConfirm, setNewAdminPasswordConfirm] = useState('');
  const [initialAdminPassword, setInitialAdminPassword] = useState('');
  const [initialAdminPasswordConfirm, setInitialAdminPasswordConfirm] = useState('');
  const settingsImportRef = useRef<HTMLInputElement | null>(null);
  const designImportRef = useRef<HTMLInputElement | null>(null);
  const backupImportRef = useRef<HTMLInputElement | null>(null);

  useEffect(() => {
    window.localStorage.setItem('threadforgeUploaderRows', JSON.stringify(rows));
  }, [rows]);

  useEffect(() => {
    window.localStorage.setItem('threadforgeUploaderSettings', JSON.stringify(settings));
    setSettingsDraft(settings);
  }, [settings]);

  useEffect(() => {
    Promise.all([
      apiGet<{ settings: UploaderSettings }>('uploaderSettings'),
      apiGet<{ files: ApiUploaderFile[] }>('listUploaderFiles'),
    ]).then(([settingResponse, fileResponse]) => {
      setSettings(normalizeSettings(settingResponse.settings));
      setRows(mapUploaderFiles(fileResponse.files));
    }).catch(() => {
      // Keep the local preview data available while the backend is offline.
    });
  }, []);

  useEffect(() => {
    if (!showAdmin) return;
    apiGet<{ adminPasswordConfigured: boolean }>('adminStatus')
      .then((data) => setAdminPasswordConfigured(data.adminPasswordConfigured))
      .catch((error) => {
        setAdminPasswordConfigured(true);
        setAdminError(error.message);
      });
  }, [showAdmin]);

  useEffect(() => {
    if (!adminAuthenticated || adminTab !== 'deleted') return;
    reloadDeletedUploaderFiles();
  }, [adminAuthenticated, adminTab]);

  const totalText = useMemo(() => `${rows.length}件（1/11）`, [rows.length]);
  const uploadCount = rows.length + deletedRows.length;
  const uploadSize = [...rows, ...deletedRows].reduce((total, row) => total + row.sizeBytes, 0);
  const monthlyAnalytics = useMemo(() => buildMonthlyAnalytics([...rows, ...deletedRows]), [rows, deletedRows]);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setUploadError('');
    setUploadMessage('');
    if (!selectedFile) {
      setUploadError('アップロードするファイルを選択してください。');
      return;
    }
    const validationError = validateUploadFile(selectedFile, settings);
    if (validationError) {
      setUploadError(validationError);
      return;
    }
    if (!deleteKey.trim()) {
      setUploadError('Delkeyを入力してください。');
      return;
    }
    setUploading(true);
    try {
      await apiUpload<{ success: boolean; message: string }>(selectedFile, comment, deleteKey);
      await reloadUploaderFiles();
      setUploadMessage('アップロードしました。');
    } catch (error) {
      setUploadError((error as Error).message);
      setUploading(false);
      return;
    }
    setComment('');
    setDeleteKey('');
    setSelectedFile(null);
    setUploading(false);
    event.currentTarget.reset();
  }

  function selectFile(file: File | null, input: HTMLInputElement) {
    setUploadError('');
    setUploadMessage('');
    if (!file) {
      setSelectedFile(null);
      return;
    }
    const error = validateUploadFile(file, settings);
    if (error) {
      setSelectedFile(null);
      setUploadError(error);
      input.value = '';
      return;
    }
    setSelectedFile(file);
  }

  async function deleteUploadedFile(row: UploadRow) {
    const key = window.prompt(`${row.filename} のDelkeyを入力してください。`);
    if (key === null) return;
    setFileActionMessage('');
    setFileActionError('');
    try {
      const response = await apiPost<{ success: boolean; message: string }>('deleteUploaderFile', {
        id: String(row.id),
        delete_key: key,
      });
      setRows((currentRows) => currentRows.filter((item) => item.id !== row.id));
      if (adminAuthenticated) await reloadDeletedUploaderFiles();
      setFileActionMessage(response.message);
    } catch (error) {
      setFileActionError((error as Error).message);
    }
  }

  async function copyUploadedFileUrl(row: UploadRow) {
    if (!row.downloadUrl) return;
    setFileActionMessage('');
    setFileActionError('');
    try {
      await copyText(row.downloadUrl);
      setFileActionMessage(`${row.filename} のURLをコピーしました。`);
    } catch {
      setFileActionError('URLをコピーできませんでした。');
    }
  }

  async function submitAdminPassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setAdminError('');
    setAdminMessage('');
    try {
      await apiPost('getSettings', { admin_password: adminPassword });
      setAdminAuthenticated(true);
      window.localStorage.setItem('threadforgeUploaderAdminPassword', adminPassword);
      await reloadDeletedUploaderFiles();
      setAdminMessage('管理画面を開きました。');
    } catch (error) {
      setAdminAuthenticated(false);
      setAdminError((error as Error).message);
    }
  }

  async function initializeAdminPassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setAdminError('');
    if (initialAdminPassword !== initialAdminPasswordConfirm) {
      setAdminError('確認用パスワードが一致しません。');
      return;
    }
    try {
      await apiPost('initializeAdminPassword', {
        new_admin_password: initialAdminPassword,
        new_admin_password_confirm: initialAdminPasswordConfirm,
      });
      setAdminPassword(initialAdminPassword);
      setAdminAuthenticated(true);
      setAdminPasswordConfigured(true);
      window.localStorage.setItem('threadforgeUploaderAdminPassword', initialAdminPassword);
      setInitialAdminPassword('');
      setInitialAdminPasswordConfirm('');
      setAdminMessage('管理パスワードを設定しました。');
    } catch (error) {
      setAdminError((error as Error).message);
    }
  }

  async function reloadUploaderFiles() {
    const response = await apiGet<{ files: ApiUploaderFile[] }>('listUploaderFiles');
    setRows(mapUploaderFiles(response.files));
  }

  async function reloadDeletedUploaderFiles() {
    const response = await apiGet<{ files: ApiUploaderFile[] }>('listDeletedUploaderFiles', {
      admin_password: adminPassword,
    });
    setDeletedRows(mapUploaderFiles(response.files));
  }

  async function bulkDeleteSelected() {
    if (selectedIds.length === 0) return;
    setAdminError('');
    try {
      const response = await apiPost<{ success: boolean; message: string }>('adminDeleteUploaderFiles', {
        admin_password: adminPassword,
        ids: selectedIds.join(','),
      });
      await Promise.all([reloadUploaderFiles(), reloadDeletedUploaderFiles()]);
      setSelectedIds([]);
      setAdminMessage(response.message);
    } catch (error) {
      setAdminError((error as Error).message);
    }
  }

  async function restoreSelected() {
    if (deletedSelectedIds.length === 0) return;
    setAdminError('');
    try {
      const response = await apiPost<{ success: boolean; message: string }>('restoreUploaderFiles', {
        admin_password: adminPassword,
        ids: deletedSelectedIds.join(','),
      });
      await Promise.all([reloadUploaderFiles(), reloadDeletedUploaderFiles()]);
      setDeletedSelectedIds([]);
      setAdminMessage(response.message);
    } catch (error) {
      setAdminError((error as Error).message);
    }
  }

  async function purgeSelected() {
    if (deletedSelectedIds.length === 0) return;
    if (!window.confirm('選択したファイルを完全削除します。元に戻せません。')) return;
    setAdminError('');
    try {
      const response = await apiPost<{ success: boolean; message: string }>('purgeUploaderFiles', {
        admin_password: adminPassword,
        ids: deletedSelectedIds.join(','),
      });
      await reloadDeletedUploaderFiles();
      setDeletedSelectedIds([]);
      setAdminMessage(response.message);
    } catch (error) {
      setAdminError((error as Error).message);
    }
  }

  async function saveSettings(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setAdminError('');
    setSettings(settingsDraft);
    try {
      await apiPost('updateSettings', {
        admin_password: adminPassword,
        settings: JSON.stringify({
          config: {
            uploaderTitle: settingsDraft.title,
            uploaderHomePageUrl: settingsDraft.homePageUrl,
            uploaderAllowedExtensions: settingsDraft.allowedExtensions,
            uploaderMaxUploadKb: settingsDraft.maxUploadKb,
          },
          skin: uploaderSkinPayload(settingsDraft.design),
        }),
      });
      setAdminMessage('設定を保存しました。');
    } catch (error) {
      setAdminError((error as Error).message);
    }
  }

  async function changeAdminPassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setAdminError('');
    try {
      await apiPost('changeAdminPassword', {
        admin_password: adminPassword,
        new_admin_password: newAdminPassword,
        new_admin_password_confirm: newAdminPasswordConfirm,
      });
      setAdminPassword(newAdminPassword);
      window.localStorage.setItem('threadforgeUploaderAdminPassword', newAdminPassword);
      setNewAdminPassword('');
      setNewAdminPasswordConfirm('');
      setAdminMessage('管理パスワードを変更しました。');
    } catch (error) {
      setAdminError((error as Error).message);
    }
  }

  async function exportSettingsJson(section: 'settings' | 'design') {
    const payload = section === 'design'
      ? { format: 'threadforge-file-uploader-design', version: 1, design: settingsDraft.design }
      : {
        format: 'threadforge-file-uploader-settings',
        version: 1,
        settings: {
          title: settingsDraft.title,
          homePageUrl: settingsDraft.homePageUrl,
          allowedExtensions: settingsDraft.allowedExtensions,
          maxUploadKb: settingsDraft.maxUploadKb,
        },
      };
    try {
      await saveBlobWithPicker(
        new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' }),
        section === 'design' ? 'file-uploader-design.json' : 'file-uploader-settings.json',
      );
      setAdminMessage(`${section === 'design' ? 'デザイン' : '設定'}をエクスポートしました。`);
      setAdminError('');
    } catch (error) {
      if (!isAbortError(error)) setAdminError((error as Error).message);
    }
  }

  async function importSettingsJson(section: 'settings' | 'design', file: File | null) {
    if (!file) return;
    setAdminError('');
    try {
      const payload = JSON.parse(await file.text()) as Record<string, unknown>;
      if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
        throw new Error('JSONの形式が正しくありません。');
      }
      if (section === 'design') {
        if (payload.format !== 'threadforge-file-uploader-design' || !isPlainObject(payload.design)) {
          throw new Error('file-uploaderのデザインJSONではありません。');
        }
        const next = normalizeSettings({
          ...settingsDraft,
          design: { ...settingsDraft.design, ...payload.design },
        });
        setSettingsDraft(next);
        setSettings(next);
      } else {
        if (payload.format !== 'threadforge-file-uploader-settings' || !isPlainObject(payload.settings)) {
          throw new Error('file-uploaderの設定JSONではありません。');
        }
        const imported = payload.settings;
        const next = normalizeSettings({
          ...settingsDraft,
          title: imported.title,
          homePageUrl: imported.homePageUrl,
          allowedExtensions: imported.allowedExtensions,
          maxUploadKb: imported.maxUploadKb,
        });
        setSettingsDraft(next);
      }
      setAdminMessage(`${section === 'design' ? 'デザイン' : '設定'}を読み込みました。保存すると確定します。`);
    } catch (error) {
      if (!isAbortError(error)) setAdminError((error as Error).message);
    }
  }

  async function exportFullBackup() {
    setAdminError('');
    setAdminMessage('フルバックアップを作成しています...');
    try {
      const filename = `file-uploader-full-backup-${timestampForFilename()}.zip`;
      const fileHandle = await chooseSaveFile(filename, 'application/zip');
      const url = new URL(apiBase(), window.location.href);
      url.searchParams.set('action', 'exportBackup');
      url.searchParams.set('admin_password', adminPassword);
      const response = await fetch(url);
      if (!response.ok) throw new Error(await responseError(response));
      await saveBlobWithPicker(await response.blob(), filename, fileHandle);
      setAdminMessage('フルバックアップZIPをエクスポートしました。');
    } catch (error) {
      setAdminError((error as Error).message);
    }
  }

  async function importFullBackup(file: File | null) {
    if (!file) return;
    if (!window.confirm('現在のDBとアップロードファイルをバックアップ内容で置き換えます。実行しますか？')) return;
    setAdminError('');
    setAdminMessage('フルバックアップをインポートしています...');
    try {
      const body = new FormData();
      body.set('action', 'importBackup');
      body.set('admin_password', adminPassword);
      body.set('backup', file);
      const response = await fetch(apiBase(), { method: 'POST', body });
      const data = await response.json();
      if (!response.ok || data.success === false) throw new Error(data.message || 'インポートに失敗しました。');
      const [settingResponse] = await Promise.all([
        apiGet<{ settings: UploaderSettings }>('uploaderSettings'),
        reloadUploaderFiles(),
      ]);
      setSettings(normalizeSettings(settingResponse.settings));
      setAdminAuthenticated(false);
      setAdminPassword('');
      window.localStorage.removeItem('threadforgeUploaderAdminPassword');
      setAdminMessage(`${data.message} 復元後の管理パスワードで再ログインしてください。`);
    } catch (error) {
      setAdminError((error as Error).message);
    }
  }

  return (
    <div className="uploader-page" id="top" style={designStyle(settings.design)}>
      <section className="uploader-shell">
        <div className="uploader-title">
          <span className="uploader-title-text">{renderTitle(settings.title, () => setShowAdmin(true))}</span>
          <a className="uploader-home-link" href={homeHref(settings.homePageUrl)}>HOME</a>
        </div>
        <div className="uploader-content">
          <form className="upload-form" id="post" onSubmit={submit}>
            <label>
              <span>ファイル</span>
              <input
                type="file"
                accept={acceptValue(settings.allowedExtensions)}
                onChange={(event) => selectFile(event.currentTarget.files?.[0] ?? null, event.currentTarget)}
              />
            </label>
            <label>
              <span>コメント</span>
              <input value={comment} onChange={(event) => setComment(event.currentTarget.value)} />
            </label>
            <label>
              <span>Delkey</span>
              <input type="password" value={deleteKey} onChange={(event) => setDeleteKey(event.currentTarget.value)} />
            </label>
            <div className="upload-form__actions">
              <button type="submit" disabled={uploading}>{uploading ? 'UPLOADING...' : 'SUBMIT'}</button>
            </div>
            <p className="upload-note">
              サイズ：{settings.maxUploadKb.toLocaleString()}KBまで<br />
              up可：{settings.allowedExtensions}
              <span>count:80747</span>
            </p>
            {(uploadError || uploadMessage) && (
              <p className={uploadError ? 'upload-status error' : 'upload-status'}>{uploadError || uploadMessage}</p>
            )}
          </form>

          <p className="file-count">ファイル数：{totalText}</p>
          {(fileActionError || fileActionMessage) && (
            <p className={fileActionError ? 'upload-status error' : 'upload-status'}>
              {fileActionError || fileActionMessage}
            </p>
          )}
          <table className="upload-table" id="list">
            <thead>
              <tr>
                <th>DL</th>
                <th>コメント</th>
                <th>サイズ</th>
                <th>日付</th>
                <th>元ファイル名</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id}>
                  <td data-label="DL"><a href={row.downloadUrl || '#download'} download={row.originalName}>[{row.filename}]</a></td>
                  <td data-label="コメント" className="upload-comment">{row.comment}</td>
                  <td data-label="サイズ">{formatSize(row.sizeBytes)}</td>
                  <td data-label="日付">{row.date}</td>
                  <td data-label="元ファイル名" className="upload-original-name">{row.originalName}</td>
                  <td data-label="操作">
                    <div className="file-actions">
                      <button type="button" onClick={() => copyUploadedFileUrl(row)}>URLコピー</button>
                      <button type="button" className="file-delete-button" onClick={() => deleteUploadedFile(row)}>[DEL]</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      {showAdmin && (
        <div className="admin-overlay" role="dialog" aria-modal="true" aria-label="管理画面">
          <section className="uploader-admin">
            <div className="uploader-admin__header">
              <h2>管理画面</h2>
              <button type="button" onClick={() => setShowAdmin(false)}>閉じる</button>
            </div>

            {!adminAuthenticated ? (
              adminPasswordConfigured === false ? (
                <form className="admin-auth-form" onSubmit={initializeAdminPassword}>
                  <p>初回起動のため、管理パスワードを設定してください。</p>
                  <label>管理パスワード<input type="password" value={initialAdminPassword} onChange={(event) => setInitialAdminPassword(event.target.value)} /></label>
                  <label>確認<input type="password" value={initialAdminPasswordConfirm} onChange={(event) => setInitialAdminPasswordConfirm(event.target.value)} /></label>
                  <div className="admin-actions"><button type="submit">設定して開く</button></div>
                </form>
              ) : (
                <form className="admin-auth-form" onSubmit={submitAdminPassword}>
                  <p>管理パスワードを入力してください。</p>
                  <label>管理パスワード<input type="password" value={adminPassword} onChange={(event) => setAdminPassword(event.target.value)} /></label>
                  <div className="admin-actions"><button type="submit">管理画面を開く</button></div>
                </form>
              )
            ) : (
              <>
                <nav className="admin-tabs" aria-label="管理メニュー">
                  {[
                    ['bulk-delete', '一括削除'],
                    ['deleted', '復元/消去'],
                    ['maintenance', '保守'],
                    ['analytics', 'アナリティクス'],
                    ['settings', '設定'],
                    ['design', 'デザイン'],
                  ].map(([id, label]) => (
                    <button key={id} type="button" className={adminTab === id ? 'active' : ''} onClick={() => setAdminTab(id as AdminTab)}>{label}</button>
                  ))}
                </nav>

                {adminTab === 'bulk-delete' && (
                  <section className="admin-panel">
                    <h3>一括削除</h3>
                    <AdminTable rows={rows} selectedIds={selectedIds} onToggle={(id) => setSelectedIds(toggleId(selectedIds, id))} />
                    <div className="admin-actions"><button type="button" onClick={bulkDeleteSelected}>チェックした項目を削除</button></div>
                  </section>
                )}

                {adminTab === 'deleted' && (
                  <section className="admin-panel">
                    <h3>復原・完全削除</h3>
                    <AdminTable rows={deletedRows} selectedIds={deletedSelectedIds} onToggle={(id) => setDeletedSelectedIds(toggleId(deletedSelectedIds, id))} />
                    <div className="admin-actions">
                      <button type="button" onClick={restoreSelected}>復原</button>
                      <button type="button" className="danger" onClick={purgeSelected}>完全削除</button>
                    </div>
                  </section>
                )}

                {adminTab === 'settings' && (
                  <form className="admin-panel admin-settings-form" onSubmit={saveSettings}>
                    <h3>設定</h3>
                    <label>アップローダータイトル名<input value={settingsDraft.title} onChange={(event) => setSettingsDraft({ ...settingsDraft, title: event.target.value })} /></label>
                    <label>HOMEリンク先<input value={settingsDraft.homePageUrl} placeholder="../ または https://example.com/" onChange={(event) => setSettingsDraft({ ...settingsDraft, homePageUrl: event.target.value })} /></label>
                    <label>許可する拡張子<input value={settingsDraft.allowedExtensions} onChange={(event) => setSettingsDraft({ ...settingsDraft, allowedExtensions: event.target.value })} /></label>
                    <label>許可するファイルサイズ（KB）<input type="number" min="1" value={settingsDraft.maxUploadKb} onChange={(event) => setSettingsDraft({ ...settingsDraft, maxUploadKb: Number(event.target.value) || 1 })} /></label>
                    <div className="admin-import-export">
                      <strong>設定JSON</strong>
                      <div className="admin-actions">
                        <button type="button" onClick={() => void exportSettingsJson('settings')}>エクスポート</button>
                        <button type="button" onClick={() => settingsImportRef.current?.click()}>インポート</button>
                        <input
                          ref={settingsImportRef}
                          className="visually-hidden-file"
                          type="file"
                          accept="application/json,.json"
                          onChange={(event) => {
                            void importSettingsJson('settings', event.currentTarget.files?.[0] ?? null);
                            event.currentTarget.value = '';
                          }}
                        />
                        <button type="submit">保存</button>
                      </div>
                    </div>
                  </form>
                )}

                {adminTab === 'design' && (
                  <section className="admin-panel">
                    <h3>デザイン</h3>
                    <p>変更内容はこの画面と背後のアップローダーへ即時反映されます。</p>
                    <div className="design-grid">
                      {designFields.map(([key, label]) => (
                        <label className="design-color-row" key={key}>
                          <span>{label}</span>
                          <input
                            type="color"
                            value={settingsDraft.design[key]}
                            onChange={(event) => {
                              const next = {
                                ...settingsDraft,
                                design: { ...settingsDraft.design, [key]: event.target.value },
                              };
                              setSettingsDraft(next);
                              setSettings(next);
                            }}
                          />
                          <input
                            value={settingsDraft.design[key]}
                            onChange={(event) => {
                              const next = {
                                ...settingsDraft,
                                design: { ...settingsDraft.design, [key]: event.target.value },
                              };
                              setSettingsDraft(next);
                              setSettings(next);
                            }}
                          />
                        </label>
                      ))}
                    </div>
                    <div className="design-preview" style={designStyle(settingsDraft.design)}>
                      <div className="uploader-title">タイトル見本</div>
                      <div className="upload-form">
                        <label><span>ラベル</span><input value="入力欄の見本" readOnly /></label>
                        <div className="upload-form__actions"><button type="button">ボタン</button></div>
                      </div>
                      <table className="upload-table"><thead><tr><th>表見出し</th><th>リンク</th></tr></thead><tbody><tr><td>内容</td><td><a href="#preview">[file.png]</a></td></tr></tbody></table>
                    </div>
                    <div className="admin-import-export">
                      <strong>デザインJSON</strong>
                      <div className="admin-actions">
                        <button type="button" onClick={() => void exportSettingsJson('design')}>エクスポート</button>
                        <button type="button" onClick={() => designImportRef.current?.click()}>インポート</button>
                        <input
                          ref={designImportRef}
                          className="visually-hidden-file"
                          type="file"
                          accept="application/json,.json"
                          onChange={(event) => {
                            void importSettingsJson('design', event.currentTarget.files?.[0] ?? null);
                            event.currentTarget.value = '';
                          }}
                        />
                      </div>
                    </div>
                    <div className="admin-actions">
                      <button type="button" onClick={() => {
                        const next = { ...settingsDraft, design: defaultDesign };
                        setSettingsDraft(next);
                        setSettings(next);
                      }}>初期色へ戻す</button>
                      <button type="button" onClick={() => saveDesignSettings(settingsDraft, adminPassword, setAdminMessage, setAdminError)}>保存</button>
                    </div>
                  </section>
                )}

                {adminTab === 'analytics' && (
                  <section className="admin-panel">
                    <h3>アナリティクス</h3>
                    <dl className="analytics-summary">
                      <div><dt>総アップロード数</dt><dd>{uploadCount.toLocaleString()}件</dd></div>
                      <div><dt>総サイズ</dt><dd>{formatSize(uploadSize)}</dd></div>
                      <div><dt>アクセス数</dt><dd>共通アクセス計測API接続後に表示</dd></div>
                    </dl>
                    <table className="upload-table">
                      <thead><tr><th>月</th><th>アップロード数</th><th>サイズ</th></tr></thead>
                      <tbody>
                        {monthlyAnalytics.map((item) => (
                          <tr key={item.month}><td>{item.month}</td><td>{item.count}</td><td>{formatSize(item.sizeBytes)}</td></tr>
                        ))}
                      </tbody>
                    </table>
                  </section>
                )}

                {adminTab === 'maintenance' && (
                  <section className="admin-panel maintenance-panel">
                    <h3>保守</h3>
                    <section className="maintenance-section">
                      <h4>データのフルバックアップ</h4>
                      <p>DB、アップロードファイル、設定、デザインをZIPへ保存、またはZIPから復元します。</p>
                      <div className="admin-actions">
                        <button type="button" onClick={exportFullBackup}>エクスポート</button>
                        <button type="button" onClick={() => backupImportRef.current?.click()}>インポート</button>
                        <input
                          ref={backupImportRef}
                          className="visually-hidden-file"
                          type="file"
                          accept="application/zip,.zip"
                          onChange={(event) => {
                            void importFullBackup(event.currentTarget.files?.[0] ?? null);
                            event.currentTarget.value = '';
                          }}
                        />
                      </div>
                    </section>
                    <form className="maintenance-section admin-settings-form" onSubmit={changeAdminPassword}>
                      <h4>管理パスワード変更</h4>
                      <label>新しい管理パスワード<input type="password" value={newAdminPassword} onChange={(event) => setNewAdminPassword(event.target.value)} /></label>
                      <label>確認<input type="password" value={newAdminPasswordConfirm} onChange={(event) => setNewAdminPasswordConfirm(event.target.value)} /></label>
                      <div className="admin-actions"><button type="submit">変更</button></div>
                    </form>
                  </section>
                )}
              </>
            )}

            {(adminMessage || adminError) && (
              <p className={adminError ? 'admin-message error' : 'admin-message'}>{adminError || adminMessage}</p>
            )}
          </section>
        </div>
      )}
    </div>
  );
}

function AdminTable({ rows, selectedIds, onToggle }: { rows: UploadRow[]; selectedIds: number[]; onToggle: (id: number) => void }) {
  if (rows.length === 0) {
    return <p>対象ファイルはありません。</p>;
  }
  return (
    <table className="upload-table admin-file-table">
      <thead><tr><th>選択</th><th>DL</th><th>コメント</th><th>サイズ</th><th>日付</th><th>元ファイル名</th></tr></thead>
      <tbody>
        {rows.map((row) => (
          <tr key={row.id}>
            <td><input type="checkbox" checked={selectedIds.includes(row.id)} onChange={() => onToggle(row.id)} /></td>
            <td>[{row.filename}]</td>
            <td>{row.comment}</td>
            <td>{formatSize(row.sizeBytes)}</td>
            <td>{row.date}</td>
            <td>{row.originalName}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function renderTitle(title: string, openAdmin: () => void) {
  const index = title.indexOf('ロ');
  if (index === -1) {
    return <>{title}<button className="title-admin-button" type="button" onClick={openAdmin}>管理</button></>;
  }
  return (
    <>
      {title.slice(0, index)}
      <button className="title-admin-letter" type="button" onClick={openAdmin} aria-label="管理画面を開く">ロ</button>
      {title.slice(index + 1)}
    </>
  );
}

export function toggleId(ids: number[], id: number) {
  return ids.includes(id) ? ids.filter((current) => current !== id) : [...ids, id];
}

function loadJson<T>(key: string, fallback: T): T {
  try {
    const raw = window.localStorage.getItem(key);
    return raw ? JSON.parse(raw) as T : fallback;
  } catch {
    return fallback;
  }
}

function formatUploaderDate(date: Date) {
  return `${String(date.getFullYear()).slice(2)}/${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}-${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

export function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes}bytes`;
  return `${Math.round(bytes / 1024)}KB`;
}

export function buildMonthlyAnalytics(rows: UploadRow[]) {
  const map = new Map<string, { month: string; count: number; sizeBytes: number }>();
  rows.forEach((row) => {
    const month = row.date.slice(0, 5);
    const current = map.get(month) || { month, count: 0, sizeBytes: 0 };
    current.count += 1;
    current.sizeBytes += row.sizeBytes;
    map.set(month, current);
  });
  return Array.from(map.values()).sort((a, b) => b.month.localeCompare(a.month));
}

const designFields: Array<[keyof UploaderDesign, string]> = [
  ['pageBackgroundColor', 'ページ背景色'],
  ['pageTextColor', 'ページ文字色'],
  ['linkColor', 'リンク色'],
  ['shellBackgroundColor', '外枠背景色'],
  ['contentBackgroundColor', '一覧背景色'],
  ['formBackgroundColor', '投稿フォーム背景色'],
  ['titleStartColor', 'タイトル上側色'],
  ['titleEndColor', 'タイトル下側色'],
  ['tableHeaderColor', '表見出し背景色'],
  ['borderColor', '枠線色'],
  ['buttonBackgroundColor', 'ボタン背景色'],
  ['buttonTextColor', 'ボタン文字色'],
  ['activeTabColor', '選択中タブ色'],
  ['errorColor', 'エラー/完全削除色'],
];

export function normalizeSettings(value: Partial<UploaderSettings>): UploaderSettings {
  return {
    title: value.title || defaultSettings.title,
    homePageUrl: typeof value.homePageUrl === 'string' && value.homePageUrl.trim() !== ''
      ? value.homePageUrl.trim()
      : defaultSettings.homePageUrl,
    allowedExtensions: typeof value.allowedExtensions === 'string'
      ? value.allowedExtensions
      : defaultSettings.allowedExtensions,
    maxUploadKb: Number(value.maxUploadKb) > 0 ? Number(value.maxUploadKb) : defaultSettings.maxUploadKb,
    design: { ...defaultDesign, ...(value.design || {}) },
  };
}

export function homeHref(value?: string): string {
  const raw = (value ?? '').trim();
  if (raw === '') return '../';
  if (/^https?:\/\//i.test(raw) || raw.startsWith('/') || raw.startsWith('./') || raw.startsWith('../')) {
    return raw;
  }
  return `https://${raw}`;
}

export function allowedExtensionList(value: string) {
  return Array.from(new Set(
    value
      .toLowerCase()
      .split(/[\s,;]+/)
      .map((extension) => extension.replace(/^\./, '').trim())
      .filter((extension) => /^[a-z0-9]+$/.test(extension)),
  ));
}

export function acceptValue(value: string) {
  return allowedExtensionList(value).map((extension) => `.${extension}`).join(',');
}

export function validateUploadFile(file: File, currentSettings: UploaderSettings) {
  const extension = file.name.split('.').pop()?.toLowerCase() || '';
  const allowed = allowedExtensionList(currentSettings.allowedExtensions);
  if (!extension || !allowed.includes(extension)) {
    return `許可されていない拡張子です。許可: ${allowed.join(' ')}`;
  }
  if (file.size > currentSettings.maxUploadKb * 1024) {
    return `ファイルサイズが上限を超えています。上限: ${currentSettings.maxUploadKb.toLocaleString()}KB`;
  }
  return '';
}

function designStyle(design: UploaderDesign): CSSProperties {
  return {
    '--uploader-page-bg': design.pageBackgroundColor,
    '--uploader-page-text': design.pageTextColor,
    '--uploader-link': design.linkColor,
    '--uploader-shell-bg': design.shellBackgroundColor,
    '--uploader-content-bg': design.contentBackgroundColor,
    '--uploader-form-bg': design.formBackgroundColor,
    '--uploader-title-start': design.titleStartColor,
    '--uploader-title-end': design.titleEndColor,
    '--uploader-table-header': design.tableHeaderColor,
    '--uploader-border': design.borderColor,
    '--uploader-button-bg': design.buttonBackgroundColor,
    '--uploader-button-text': design.buttonTextColor,
    '--uploader-active-tab': design.activeTabColor,
    '--uploader-error': design.errorColor,
  } as CSSProperties;
}

function uploaderSkinPayload(design: UploaderDesign) {
  return Object.fromEntries(
    Object.entries(design).map(([key, value]) => [`uploader${key.charAt(0).toUpperCase()}${key.slice(1)}`, value]),
  );
}

async function saveDesignSettings(
  currentSettings: UploaderSettings,
  adminPassword: string,
  setMessage: (message: string) => void,
  setError: (message: string) => void,
) {
  setError('');
  try {
    await apiPost('updateSettings', {
      admin_password: adminPassword,
      settings: JSON.stringify({
        config: {
          uploaderTitle: currentSettings.title,
          uploaderHomePageUrl: currentSettings.homePageUrl,
          uploaderAllowedExtensions: currentSettings.allowedExtensions,
          uploaderMaxUploadKb: currentSettings.maxUploadKb,
        },
        skin: uploaderSkinPayload(currentSettings.design),
      }),
    });
    setMessage('デザインを保存しました。');
  } catch (error) {
    setError((error as Error).message);
  }
}

function formatApiDate(value: string) {
  const date = new Date(value.replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? value : formatUploaderDate(date);
}

function mapUploaderFiles(files: ApiUploaderFile[]): UploadRow[] {
  return files.map((file) => ({
    id: file.id,
    filename: file.filename,
    originalName: file.originalName,
    comment: file.comment,
    sizeBytes: file.sizeBytes,
    date: formatApiDate(file.createdAt),
    deleteKey: '',
    downloadUrl: backendFileUrl(file.downloadUrl),
  }));
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}

async function chooseSaveFile(suggestedName: string, mimeType: string): Promise<any | null> {
  const picker = (window as any).showSaveFilePicker;
  if (typeof picker !== 'function') return null;
  const extension = suggestedName.includes('.') ? `.${suggestedName.split('.').pop()}` : '';
  try {
    return await picker({
      suggestedName,
      types: extension ? [{
        description: suggestedName,
        accept: { [mimeType]: [extension] },
      }] : undefined,
    });
  } catch (error) {
    if ((error as Error).name === 'AbortError') throw error;
    return null;
  }
}

function isAbortError(error: unknown) {
  return error instanceof Error && error.name === 'AbortError';
}

async function saveBlobWithPicker(blob: Blob, filename: string, fileHandle: any | null = null) {
  const handle = fileHandle ?? await chooseSaveFile(filename, blob.type || 'application/octet-stream');
  if (handle) {
    const writable = await handle.createWritable();
    await writable.write(blob);
    await writable.close();
    return;
  }
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  URL.revokeObjectURL(url);
}

function timestampForFilename() {
  const now = new Date();
  const pad = (value: number) => String(value).padStart(2, '0');
  return `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
}

async function responseError(response: Response) {
  try {
    const data = await response.json();
    return data.message || `${response.status} ${response.statusText}`;
  } catch {
    return `${response.status} ${response.statusText}`;
  }
}

async function copyText(value: string) {
  if (navigator.clipboard && window.isSecureContext) {
    await navigator.clipboard.writeText(value);
    return;
  }
  const textarea = document.createElement('textarea');
  textarea.value = value;
  textarea.style.position = 'fixed';
  textarea.style.opacity = '0';
  document.body.appendChild(textarea);
  textarea.select();
  const copied = document.execCommand('copy');
  textarea.remove();
  if (!copied) throw new Error('Copy failed.');
}

export default App;
