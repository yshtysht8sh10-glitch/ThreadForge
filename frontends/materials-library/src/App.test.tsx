import { afterEach, describe, expect, it, vi } from 'vitest';
import { acceptExtensions, defaultTermAnswers, exportFullBackup, formatBytes, generateSharedSecret, importBackup, isPlainObject, materialManagementPasswordError, normalizeMaterialDesign, toBoolean } from './App';
import { api, defaultMaterialDesign, groupMaterials, homeHref, MaterialItem, packAuthorGroups } from './api';

describe('materials-library helpers', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('groups logged-in and guest authors separately even when names match', () => {
    const items = [
      item(1, 10, 'Same Name', 'tag:1'),
      item(2, null, 'Same Name', 'tag:1'),
    ];
    const groups = groupMaterials(items, 'tag');
    expect(groups).toHaveLength(1);
    expect(groups[0].groups).toHaveLength(2);
    expect(groups[0].groups.map((group) => group.key)).toEqual(['user:10', 'guest:Same Name']);
  });

  it('supports author-first grouping', () => {
    const groups = groupMaterials([item(1, 10, 'Author', 'tag:1')], 'author');
    expect(groups[0].label).toBe('Author');
    expect(groups[0].groups[0].label).toBe('Effects');
  });

  it('normalizes home links and upload accept values', () => {
    expect(homeHref('example.com')).toBe('https://example.com');
    expect(homeHref('../')).toBe('../');
    expect(acceptExtensions('zip 7z,rar')).toBe('.zip,.7z,.rar');
  });

  it('defaults new usage terms to yes and preserves saved user choices', () => {
    const terms = [
      { id: 1, label: 'Modify', description: '', sortOrder: 0 },
      { id: 2, label: 'Redistribute', description: '', sortOrder: 1 },
    ];
    expect(defaultTermAnswers(terms)).toEqual({ '1': true, '2': true });
    expect(defaultTermAnswers(terms, { '2': false })).toEqual({ '1': true, '2': false });
  });

  it('formats archive sizes', () => {
    expect(formatBytes(512)).toBe('512bytes');
    expect(formatBytes(2048)).toBe('2KB');
    expect(formatBytes(1572864)).toBe('1.5MB');
  });

  it('requires a post password before opening edit or running delete', () => {
    expect(materialManagementPasswordError('')).toBe('投稿パスワードを入力してください。');
    expect(materialManagementPasswordError('   ')).toBe('投稿パスワードを入力してください。');
    expect(materialManagementPasswordError('postkey')).toBeNull();
  });

  it('keeps the gray design as the reset default', () => {
    expect(defaultMaterialDesign).toMatchObject({
      pageBackgroundColor: '#050505',
      panelBackgroundColor: '#111820',
      panelBorderColor: '#6c7787',
      headingBackgroundColor: '#59636f',
      headingTextColor: '#ffffff',
      secondaryButtonBackgroundColor: '#303640',
      secondaryButtonTextColor: '#ffffff',
      inputBackgroundColor: '#282f38',
    });
  });

  it('normalizes imported design JSON without losing defaults', () => {
    expect(normalizeMaterialDesign({ pageBackgroundColor: '#123456' })).toEqual({
      ...defaultMaterialDesign,
      pageBackgroundColor: '#123456',
    });
    expect(isPlainObject({ format: 'threadforge-materials-library-design' })).toBe(true);
    expect(isPlainObject([])).toBe(false);
  });

  it('normalizes persisted SSO flags and creates URL-safe secrets', () => {
    expect(toBoolean(true)).toBe(true);
    expect(toBoolean('true')).toBe(true);
    expect(toBoolean('1')).toBe(true);
    expect(toBoolean(false)).toBe(false);
    expect(toBoolean('false')).toBe(false);
    expect(generateSharedSecret()).toMatch(/^[A-Za-z0-9_-]{48}$/);
  });

  it('packs small adjacent authors into rows without exceeding four cards', () => {
    const groups = [
      { label: 'A', items: [item(1, 1, 'A', 'tag:1'), item(2, 1, 'A', 'tag:1')] },
      { label: 'B', items: [item(3, 2, 'B', 'tag:1'), item(4, 2, 'B', 'tag:1')] },
      { label: 'C', items: [item(5, 3, 'C', 'tag:1')] },
      { label: 'D', items: [item(6, 4, 'D', 'tag:1'), item(7, 4, 'D', 'tag:1'), item(8, 4, 'D', 'tag:1')] },
    ];

    expect(packAuthorGroups(groups).map((row) => row.map((group) => group.label))).toEqual([
      ['A', 'B'],
      ['C'],
      ['D'],
    ]);
  });

  it('streams a full backup into the browser save-file picker', async () => {
    const chunks: Uint8Array[] = [];
    const writable = new WritableStream<Uint8Array>({
      write(chunk) { chunks.push(chunk); },
    });
    const createWritable = vi.fn(async () => writable);
    const showSaveFilePicker = vi.fn(async () => ({ createWritable }));
    vi.stubGlobal('window', {
      location: { href: 'http://127.0.0.1:4277/#/admin' },
      showSaveFilePicker,
    });
    const fetchMock = vi.fn(async (_url: string, options: RequestInit) => {
      expect(options.method).toBe('POST');
      const body = options.body as FormData;
      expect(body.get('action')).toBe('exportBackup');
      expect(body.get('admin_password')).toBe('admin-secret');
      return new Response(new Uint8Array([1, 2, 3]), {
        status: 200,
        headers: { 'content-type': 'application/zip' },
      });
    });
    vi.stubGlobal('fetch', fetchMock);
    const notice = vi.fn();
    const error = vi.fn();

    await exportFullBackup('admin-secret', notice, error);

    expect(showSaveFilePicker).toHaveBeenCalledOnce();
    expect(createWritable).toHaveBeenCalledOnce();
    expect(fetchMock).toHaveBeenCalledOnce();
    expect(Array.from(chunks[0])).toEqual([1, 2, 3]);
    expect(notice).toHaveBeenCalledWith('フルバックアップを保存しました。');
    expect(error).toHaveBeenCalledWith('');
  });

  it('sends imported settings as base64 to avoid hosting WAF JSON rejection', async () => {
    const fetchMock = vi.fn(async (_url: string, options: RequestInit) => {
      const body = options.body as FormData;
      expect(body.get('action')).toBe('updateSettings');
      expect(body.get('settings')).toBeNull();
      const encoded = String(body.get('settings_b64'));
      const decoded = new TextDecoder().decode(Uint8Array.from(atob(encoded), (character) => character.charCodeAt(0)));
      expect(JSON.parse(decoded)).toEqual({ config: { materialsTitle: '素材庫' }, skin: {} });
      return new Response(JSON.stringify({ success: true, message: '設定を保存しました。' }), {
        status: 200,
        headers: { 'content-type': 'application/json' },
      });
    });
    vi.stubGlobal('fetch', fetchMock);

    await expect(api.updateSettings('admin-secret', {
      config: { materialsTitle: '素材庫' },
      skin: {},
    })).resolves.toMatchObject({ message: '設定を保存しました。' });
  });

  it('rejects a downloaded material archive before backup restore', async () => {
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
    const restored = vi.fn();
    const notice = vi.fn();
    const error = vi.fn();

    await expect(importBackup(
      'admin-secret',
      new File(['archive'], 'material-133-archive.zip', { type: 'application/zip' }),
      restored,
      notice,
      error,
    )).resolves.toBe(false);

    expect(fetchMock).not.toHaveBeenCalled();
    expect(restored).not.toHaveBeenCalled();
    expect(error).toHaveBeenCalledWith(expect.stringContaining('フルバックアップ'));
  });

  it('turns an HTML restore response into an actionable error', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => new Response('<!DOCTYPE html><title>Server Error</title>', {
      status: 500,
      headers: { 'content-type': 'text/html' },
    })));
    const restored = vi.fn();
    const error = vi.fn();

    const progress = vi.fn();
    await expect(importBackup(
      'admin-secret',
      new File(['backup'], 'materials-library-full-backup.zip', { type: 'application/zip' }),
      restored,
      vi.fn(),
      error,
      progress,
    )).resolves.toBe(false);

    expect(restored).not.toHaveBeenCalled();
    expect(progress).toHaveBeenCalledWith(expect.stringContaining('サーバーで復元'));
    expect(progress).toHaveBeenCalledWith(expect.stringContaining('復元結果'));
    expect(error).toHaveBeenCalledWith(expect.stringContaining('PHPエラーログ'));
    expect(error.mock.calls[0][0]).not.toContain('<!DOCTYPE');
  });

  it('reports backup restore phases and completes successfully', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => new Response(JSON.stringify({
      success: true,
      message: 'バックアップをインポートしました。',
    }), {
      status: 200,
      headers: { 'content-type': 'application/json' },
    })));
    const restored = vi.fn();
    const notice = vi.fn();
    const error = vi.fn();
    const progress = vi.fn();

    await expect(importBackup(
      'admin-secret',
      new File(['backup'], 'materials-library-full-backup.zip', { type: 'application/zip' }),
      restored,
      notice,
      error,
      progress,
    )).resolves.toBe(true);

    expect(progress.mock.calls.map(([message]) => message)).toEqual([
      expect.stringContaining('アップロード'),
      expect.stringContaining('復元結果'),
    ]);
    expect(restored).toHaveBeenCalledOnce();
    expect(notice).toHaveBeenCalledWith(expect.stringContaining('管理パスワードで入り直して'));
    expect(error).toHaveBeenCalledWith('');
  });
});

function item(id: number, userId: number | null, authorName: string, _tag: string): MaterialItem {
  return {
    id, userId, authorName, authorKey: userId ? `user:${userId}` : `guest:${authorName}`,
    authorIcon: null, name: `Item ${id}`, notes: '', tagId: 1, tagName: 'Effects',
    archiveUrl: 'storage/item.zip', archiveOriginalName: 'item.zip', archiveSizeBytes: 1,
    imageUrl: null, imageOriginalName: null, createdAt: '', updatedAt: '', deletedAt: null, adminOnly: false, terms: [], media: [],
  };
}
