// Vitest のテストユーティリティをインポート
// - describe: テストスイートをグループ化
// - it: 個別のテストケース定義
// - expect: アサーション関数
// - beforeEach / afterEach: テスト前後のリセット処理
// - vi: グローバルオブジェクトのスタブ/モック
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// テスト対象の API モジュール
import { api, fetchJson, isMockMode, mediaUrl } from './api';

/**
 * API モジュールのテストスイート
 *
 * 各 API 関数ごとに describe ブロックを用意し、
 * 関数の存在確認と基本的な動作確認を行います。
 */
describe('API Module', () => {
  beforeEach(() => {
    import.meta.env.VITE_USE_MOCK = 'true';
  });

  afterEach(() => {
    vi.restoreAllMocks();
    import.meta.env.VITE_USE_MOCK = 'true';
  });

  describe('fetchJson', () => {
    it('should parse JSON from an OK server response', async () => {
      import.meta.env.VITE_USE_MOCK = 'false';
      const payload = { ok: true, data: ['thread'] };
      vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(new Response(JSON.stringify(payload), { status: 200 }))));

      expect(isMockMode()).toBe(false);
      const result = await fetchJson<typeof payload>('/api.php?action=listThreads');

      expect(result).toEqual(payload);
      expect(fetch).toHaveBeenCalledOnce();
    });

    it('should throw an error when the server response is not OK', async () => {
      import.meta.env.VITE_USE_MOCK = 'false';
      vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(new Response('Not found', { status: 404, statusText: 'Not Found' }))));

      await expect(fetchJson('/api.php?action=listThreads')).rejects.toThrow('404 Not Found');
      expect(fetch).toHaveBeenCalledOnce();
    });

    it('should include a JSON error message from the server when available', async () => {
      import.meta.env.VITE_USE_MOCK = 'false';
      vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(new Response(
        JSON.stringify({ success: false, message: 'アクションが無効です。' }),
        { status: 400, statusText: 'Bad Request', headers: { 'Content-Type': 'application/json' } },
      ))));

      await expect(fetchJson('/api.php')).rejects.toThrow('400 Bad Request: アクションが無効です。');
    });
  });

  // スレッド一覧を取得する API の動作確認
  describe('listThreads', () => {
    it('should be exported as a function', () => {
      expect(api.listThreads).toBeDefined();
      expect(typeof api.listThreads).toBe('function');
    });

    it('should return an array of threads from mock API', async () => {
      const threads = await api.listThreads();
      expect(Array.isArray(threads)).toBe(true);
      expect(threads.length).toBeGreaterThan(0);
      expect(threads[0]).toHaveProperty('id');
      expect(threads[0]).toHaveProperty('title');
      expect(threads[0]).toHaveProperty('message');
    });
  });

  // 指定スレッドの詳細と返信を取得する API の動作確認
  describe('getThread', () => {
    it('should be exported as a function', () => {
      expect(api.getThread).toBeDefined();
      expect(typeof api.getThread).toBe('function');
    });

    it('should return thread details and replies from mock API', async () => {
      const thread = await api.getThread('1');
      expect(thread).toHaveProperty('thread');
      expect(thread.thread).not.toBeNull();
      expect(thread.thread).toHaveProperty('id', 1);
      expect(Array.isArray(thread.replies)).toBe(true);
    });
  });

  // 投稿を検索する API の動作確認
  describe('search', () => {
    it('should be exported as a function', () => {
      expect(api.search).toBeDefined();
      expect(typeof api.search).toBe('function');
    });

    it('should return search results from mock API', async () => {
      const results = await api.search('テスト');
      expect(Array.isArray(results)).toBe(true);
      expect(results.length).toBeGreaterThanOrEqual(1);
      expect(results[0]).toHaveProperty('title');
    });
  });

  // 新規投稿または返信投稿を行う API の動作確認
  describe('createPost', () => {
    it('should be exported as a function', () => {
      expect(api.createPost).toBeDefined();
      expect(typeof api.createPost).toBe('function');
    });

    it('should return a success result from mock API', async () => {
      const result = await api.createPost({
        name: 'テスト',
        title: '新規投稿',
        message: 'テストメッセージ',
        password: 'pass123',
        gdgd: true,
        tweet_off: true,
      });
      expect(result).toEqual({ success: true, message: '操作が完了しました（モック）' });
    });

    it('should send FormData to the server when mock mode is disabled', async () => {
      import.meta.env.VITE_USE_MOCK = 'false';
      const serverResult = { success: true, message: '保存しました' };
      vi.stubGlobal('fetch', vi.fn(async (_input, init) => {
        expect(init?.method).toBe('POST');
        expect(init?.body).toBeInstanceOf(FormData);
        expect((init?.body as FormData).get('action')).toBe('createPost');
        expect((init?.body as FormData).get('gdgd')).toBe('true');
        expect((init?.body as FormData).get('tweet_off')).toBe('true');
        return new Response(JSON.stringify(serverResult), { status: 200 });
      }));

      const result = await api.createPost({
        name: 'テスト',
        title: 'サーバーテスト',
        message: 'サーバー応答を確認します',
        password: 'pass123',
        gdgd: true,
        tweet_off: true,
      });

      expect(result).toEqual(serverResult);
      expect(fetch).toHaveBeenCalledOnce();
    });
  });

  // 投稿を編集する API のインターフェース確認
  describe('updatePost', () => {
    it('should be exported as a function', () => {
      expect(api.updatePost).toBeDefined();
      expect(typeof api.updatePost).toBe('function');
    });
  });

  // 投稿を削除する API のインターフェース確認
  describe('deletePost', () => {
    it('should be exported as a function', () => {
      expect(api.deletePost).toBeDefined();
      expect(typeof api.deletePost).toBe('function');
    });
  });

  describe('mediaUrl', () => {
    it('should resolve server-relative media paths against the API origin', () => {
      import.meta.env.VITE_API_BASE_URL = 'http://127.0.0.1:8000/api.php';
      expect(mediaUrl('/storage/data/4.png')).toBe('http://127.0.0.1:8000/storage/data/4.png');
    });

    it('should keep absolute media URLs unchanged', () => {
      expect(mediaUrl('https://cdn.example.com/a.png')).toBe('https://cdn.example.com/a.png');
      expect(mediaUrl(null)).toBeNull();
    });
  });

  describe('admin APIs', () => {
    it('should return deleted posts from mock API', async () => {
      const result = await api.listDeletedPosts('admin');
      expect(result).toEqual([]);
    });

    it('should restore posts from mock API', async () => {
      const result = await api.restorePost('1', 'admin');
      expect(result).toEqual({ success: true, message: '操作が完了しました（モック）' });
    });
  });

  describe('version API', () => {
    it('should return the application version from mock API', async () => {
      const result = await api.version();
      expect(result).toEqual({ name: 'ThreadForge', version: '0.1.0' });
    });
  });
});
