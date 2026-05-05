import { NewPostData, Post, ThreadResponse, SearchResult } from './types';

export function apiBase(): string {
  return import.meta.env.VITE_API_BASE_URL || '/api.php';
}

export function mediaUrl(path?: string | null): string | null {
  if (!path) {
    return null;
  }

  if (/^https?:\/\//i.test(path)) {
    return path;
  }

  const base = new URL(apiBase(), window.location.origin);
  return new URL(path, base.origin).toString();
}

export function isMockMode(): boolean {
  return (import.meta.env.VITE_USE_MOCK ?? 'true') === 'true';
}

export async function fetchJson<T>(input: RequestInfo, init?: RequestInit): Promise<T> {
  if (isMockMode()) {
    return new Promise((resolve) => {
      setTimeout(() => resolve(mockApiResponse(input, init)), 500);
    });
  }

  const response = await fetch(input, init);
  if (!response.ok) {
    let message = `${response.status} ${response.statusText}`;
    try {
      const payload = await response.clone().json();
      if (payload?.message) {
        message = `${message}: ${payload.message}`;
      }
    } catch {
      const text = await response.text();
      if (text.trim()) {
        message = `${message}: ${text.trim()}`;
      }
    }
    throw new Error(message);
  }
  return response.json();
}

// モックデータ
const mockPosts: Post[] = [
  {
    id: 1,
    thread_id: 1,
    parent_id: 0,
    name: 'テストユーザー',
    url: null,
    title: 'テストスレッド',
    message: 'これはテストの投稿です。画像掲示板の機能を確認しています。',
    image_path: '/storage/data/mock.png',
    created_at: '2024-01-01 12:00:00',
    gdgd: true,
    tweet_off: false,
    tweet_text: '[DT000000：テストスレッド]\n作者：テストユーザー\n\nこれはテストの投稿です。\n\n#ドット絵 #pixelart',
    tweet_url: null,
    tweet_like_count: 0,
    tweet_retweet_count: 0,
    tweet_comment_count: 0,
    tweet_impression_count: 0,
    replies: [
      {
        id: 3,
        thread_id: 1,
        parent_id: 1,
        name: '返信ユーザー',
        url: null,
        title: 'Re: テストスレッド',
        message: '一覧に表示される返信です。',
        image_path: null,
        created_at: '2024-01-01 12:30:00',
        gdgd: false,
        tweet_off: true,
        tweet_text: null,
        tweet_url: null,
        tweet_like_count: 0,
        tweet_retweet_count: 0,
        tweet_comment_count: 0,
        tweet_impression_count: 0,
      },
    ],
    reply_count: 1,
  },
  {
    id: 2,
    thread_id: 2,
    parent_id: 0,
    name: '別のユーザー',
    url: 'https://example.com',
    title: 'もう一つのスレッド',
    message: 'こちらもテスト投稿です。返信機能も確認しましょう。',
    image_path: null,
    created_at: '2024-01-01 13:00:00',
    gdgd: false,
    tweet_off: true,
    tweet_text: null,
    tweet_url: null,
    tweet_like_count: 0,
    tweet_retweet_count: 0,
    tweet_comment_count: 0,
    tweet_impression_count: 0,
    replies: [],
    reply_count: 0,
  },
];

function mockApiResponse<T>(input: RequestInfo, init?: RequestInit): T {
  const url = typeof input === 'string' ? input : input.url;
  const params = new URLSearchParams(url.split('?')[1] || '');

  let action = params.get('action');
  if (!action && init?.body instanceof FormData) {
    action = init.body.get('action') as string | null;
  }

  switch (action) {
    case 'listThreads':
      return mockPosts as T;
    case 'listDeletedPosts':
      return [] as T;
    case 'version':
      return { name: 'ThreadForge', version: '0.1.0' } as T;
    case 'getThread':
      const threadId = params.get('id');
      const thread = mockPosts.find(p => p.id === Number(threadId));
      return { thread, replies: [] } as T;
    case 'search':
      const q = params.get('q') || '';
      const results = mockPosts.filter(p =>
        p.title.includes(q) || p.message.includes(q) || p.name.includes(q)
      );
      return results as T;
    case 'createPost':
    case 'updatePost':
    case 'deletePost':
    case 'restorePost':
      return { success: true, message: '操作が完了しました（モック）' } as T;
    default:
      return { success: false, message: 'アクションが無効です（モック）' } as T;
  }
}

export const api = {
  listThreads: async (): Promise<Post[]> => {
    return fetchJson<Post[]>(`${apiBase()}?action=listThreads`);
  },
  rss: async (): Promise<string> => {
    const response = await fetch(`${apiBase()}?action=rss`);
    if (!response.ok) {
      throw new Error(`${response.status} ${response.statusText}`);
    }
    return response.text();
  },
  version: async (): Promise<{ name: string; version: string }> => {
    return fetchJson<{ name: string; version: string }>(`${apiBase()}?action=version`);
  },
  getThread: async (id: string): Promise<ThreadResponse> => {
    return fetchJson<ThreadResponse>(`${apiBase()}?action=getThread&id=${encodeURIComponent(id)}`);
  },
  getPost: async (id: string): Promise<Post> => {
    return fetchJson<Post>(`${apiBase()}?action=getPost&id=${encodeURIComponent(id)}`);
  },
  search: async (q: string): Promise<SearchResult[]> => {
    return fetchJson<SearchResult[]>(`${apiBase()}?action=search&q=${encodeURIComponent(q)}`);
  },
  createPost: async (payload: NewPostData): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        if (key === 'file' && value instanceof File) {
          formData.append('file', value);
        } else {
          formData.append(key, String(value));
        }
      }
    });
    formData.append('action', 'createPost');

    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  updatePost: async (payload: FormData): Promise<{ success: boolean; message: string }> => {
    payload.append('action', 'updatePost');
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: payload,
    });
  },
  deletePost: async (id: string, password: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'deletePost');
    formData.append('id', id);
    formData.append('password', password);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  listDeletedPosts: async (adminPassword: string): Promise<Post[]> => {
    return fetchJson<Post[]>(`${apiBase()}?action=listDeletedPosts&admin_password=${encodeURIComponent(adminPassword)}`);
  },
  restorePost: async (id: string, adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'restorePost');
    formData.append('id', id);
    formData.append('admin_password', adminPassword);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  adminDeletePosts: async (ids: string[], adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'adminDeletePosts');
    formData.append('ids', ids.join(','));
    formData.append('admin_password', adminPassword);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  adminCheckIntegrity: async (adminPassword: string): Promise<{ success: boolean; message: string; orphan_replies: number; missing_image_post_ids: number[] }> => {
    return fetchJson(`${apiBase()}?action=adminCheckIntegrity&admin_password=${encodeURIComponent(adminPassword)}`);
  },
  importBackup: async (file: File, adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'importBackup');
    formData.append('admin_password', adminPassword);
    formData.append('backup', file);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  getSettings: async (adminPassword: string): Promise<{ success: boolean; settings: any }> => {
    return fetchJson(`${apiBase()}?action=getSettings&admin_password=${encodeURIComponent(adminPassword)}`);
  },
  updateSettings: async (settings: any, adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'updateSettings');
    formData.append('admin_password', adminPassword);
    formData.append('settings', JSON.stringify(settings));
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
};
