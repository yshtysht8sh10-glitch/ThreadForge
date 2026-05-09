import { NewPostData, Post, ThreadResponse, SearchResult } from './types';
import { APP_NAME, APP_VERSION } from './version';

export type PublicSettings = {
  config: {
    bbsTitle: string;
    homePageUrl: string;
    manualTitle: string;
    manualBody: string;
    tweetEnabled: boolean;
    blueskyEnabled: boolean;
    mastodonEnabled: boolean;
    misskeyEnabled: boolean;
    gdgdEnabled: boolean;
    gdgdLabel: string;
  };
};

export const DEFAULT_PUBLIC_SETTINGS: PublicSettings = {
  config: {
    bbsTitle: 'ThreadForge',
    homePageUrl: '/',
    manualTitle: 'ThreadForge 取扱説明書',
    tweetEnabled: false,
    blueskyEnabled: false,
    mastodonEnabled: false,
    misskeyEnabled: false,
    gdgdEnabled: true,
    gdgdLabel: 'gdgd投稿',
    manualBody: [
      'ThreadForge は、スレッド形式で作品や記事を投稿できる掲示板です。',
      '',
      '投稿',
      '新規投稿ではタイトル、本文、画像、gdgd投稿、SNS転記OFFを指定できます。',
      'SNS転記関連の項目は新規投稿と投稿編集で使います。返信では表示されません。',
      '',
      '返信',
      '返信では名前、URL / HOME、本文、パスワードを入力できます。',
      '返信に画像投稿はありません。',
      '',
      '削除と編集',
      '削除は画面上から非表示にしますが、内部データは保持します。',
      '投稿と返信は、投稿時のパスワードで編集または削除できます。',
      '',
      '管理',
      '管理画面では一括削除、バックアップ、インポート、設定変更を行えます。',
    ].join('\n'),
  },
};

export const DEFAULT_ADMIN_SETTINGS = {
  config: {
    ...DEFAULT_PUBLIC_SETTINGS.config,
    tweetBaseUrl: 'https://twitter.com/MUGEN87112020/status/',
    tweetConsumerKey: '',
    tweetConsumerSecret: '',
    tweetAccessToken: '',
    tweetAccessTokenSecret: '',
    blueskyEnabled: false,
    blueskyServiceUrl: 'https://bsky.social',
    blueskyPublicApiUrl: 'https://public.api.bsky.app',
    blueskyHandle: '',
    blueskyAppPassword: '',
    mastodonEnabled: false,
    mastodonInstanceUrl: '',
    mastodonAccessToken: '',
    mastodonVisibility: 'public',
    misskeyEnabled: false,
    misskeyInstanceUrl: '',
    misskeyAccessToken: '',
    logView: 20,
    maxUploadBytes: 5100000,
    maxImageWidth: 1280,
    maxImageHeight: 960,
  },
  skin: {
    normalFrameColor: '#a23dff',
    gdgdFrameColor: '#6dffc0',
    backgroundColor: '#000000',
  },
};

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
    tweet_url: 'https://x.com/threadforge/status/1',
    display_no: 1,
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
        reply_no: 1,
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
    display_no: 2,
    replies: [],
    reply_count: 0,
  },
  {
    id: 4,
    thread_id: 4,
    parent_id: 0,
    name: 'Tweet譛ｪ逋ｻ録繝ｦ繝ｼ繧ｶ繝ｼ',
    url: null,
    title: 'Tweet蜈域悴逋ｻ録縺ｮ繧ｹ繝ｬ繝・ラ',
    message: 'Tweet先URLがまだ記録されていない投稿です。',
    image_path: null,
    created_at: '2024-01-01 14:00:00',
    gdgd: false,
    tweet_off: false,
    tweet_text: null,
    tweet_url: null,
    display_no: 3,
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
    case 'getThread':
      const threadId = params.get('id');
      const thread = mockPosts.find(p => p.id === Number(threadId));
      return { thread, replies: [] } as T;
    case 'getPost':
      const postId = params.get('id');
      const post = mockPosts.find(p => p.id === Number(postId)) || mockPosts[0];
      return post as T;
    case 'publicSettings':
      return { success: true, settings: DEFAULT_PUBLIC_SETTINGS } as T;
    case 'getSettings':
      return { success: true, settings: DEFAULT_ADMIN_SETTINGS } as T;
    case 'search':
      const q = params.get('q') || '';
      const scope = params.get('scope') || 'all';
      const results = mockPosts.filter(p =>
        scope === 'title' ? p.title.includes(q)
          : scope === 'message' ? p.message.includes(q)
            : scope === 'name' ? p.name.includes(q)
              : p.title.includes(q) || p.message.includes(q) || p.name.includes(q)
      );
      return results as T;
    case 'createPost':
    case 'updatePost':
    case 'deletePost':
    case 'restorePost':
    case 'adminDeletePosts':
    case 'updateSettings':
    case 'changeAdminPassword':
    case 'importBackup':
      return { success: true, message: '操作が完了しました（モック）' } as T;
    case 'adminCheckIntegrity':
      return {
        success: true,
        message: 'DBを確認しました（モック）',
        orphan_replies: 0,
        missing_image_post_ids: [],
      } as T;
    case 'refreshSocialReactions':
      return {
        success: true,
        message: 'SNSリアクションを更新しました（モック）',
        updated: 0,
        errors: [],
      } as T;
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
      throw new Error(`${response.status} ${response.statusText || defaultStatusText(response.status)}`);
    }
    return response.text();
  },
  getThread: async (id: string): Promise<ThreadResponse> => {
    return fetchJson<ThreadResponse>(`${apiBase()}?action=getThread&id=${encodeURIComponent(id)}`);
  },
  getPost: async (id: string): Promise<Post> => {
    return fetchJson<Post>(`${apiBase()}?action=getPost&id=${encodeURIComponent(id)}`);
  },
  version: async (): Promise<{ name: string; version: string }> => {
    return { name: APP_NAME, version: APP_VERSION };
  },
  publicSettings: async (): Promise<{ success: boolean; settings: PublicSettings }> => {
    return fetchJson<{ success: boolean; settings: PublicSettings }>(`${apiBase()}?action=publicSettings`);
  },
  search: async (q: string, scope = 'all'): Promise<SearchResult[]> => {
    return fetchJson<SearchResult[]>(`${apiBase()}?action=search&q=${encodeURIComponent(q)}&scope=${encodeURIComponent(scope)}`);
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
  refreshSocialReactions: async (adminPassword: string): Promise<{ success: boolean; message: string; updated: number; errors: string[] }> => {
    return fetchJson(`${apiBase()}?action=refreshSocialReactions&admin_password=${encodeURIComponent(adminPassword)}`);
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
  changeAdminPassword: async (currentPassword: string, newPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'changeAdminPassword');
    formData.append('admin_password', currentPassword);
    formData.append('new_admin_password', newPassword);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
};

function defaultStatusText(status: number): string {
  return status === 500 ? 'Internal Server Error' : '';
}
