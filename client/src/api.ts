import { NewPostData, Post, ThreadResponse, SearchResult, UserProfile } from './types';
import { APP_NAME, APP_VERSION } from './version';

export type AdminUser = {
  id: number;
  login_id: string;
  display_name: string;
  post_password: string;
  home_url?: string | null;
  icon_path?: string | null;
  created_at: string;
  updated_at: string;
  last_login_at?: string | null;
  last_session_at?: string | null;
  active_session_count?: number;
  post_count: number;
  claim_count: number;
};

export type ThreadArchivePeriod = {
  year: string;
  month: string;
  count: number;
};

export type ThreadArchiveMeta = {
  success: boolean;
  periods: ThreadArchivePeriod[];
  total: number;
};

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
    eejanaikaOmigotoText: string;
    eejanaikaOmigotoColor: string;
    eejanaikaGoodjobText: string;
    eejanaikaGoodjobColor: string;
    eejanaikaEejanaikaText: string;
    eejanaikaEejanaikaColor: string;
    socialHashtags: string;
    allowedImageTypes: string[];
    listOrder?: 'number' | 'createdAt' | string;
  };
};

export const DEFAULT_PUBLIC_SETTINGS: PublicSettings = {
  config: {
    bbsTitle: 'ThreadForge',
    homePageUrl: '/',
    manualTitle: 'ThreadForge',
    tweetEnabled: false,
    blueskyEnabled: false,
    mastodonEnabled: false,
    misskeyEnabled: false,
    gdgdEnabled: false,
    gdgdLabel: '特殊投稿',
    eejanaikaOmigotoText: 'お美事にございまする',
    eejanaikaOmigotoColor: '#ff72ff',
    eejanaikaGoodjobText: 'いい仕事してますねぇ',
    eejanaikaGoodjobColor: '#27a8ff',
    eejanaikaEejanaikaText: 'ええじゃないか',
    eejanaikaEejanaikaColor: '#fff200',
    socialHashtags: '#art',
    allowedImageTypes: ['gif', 'png', 'jpeg', 'jpg', 'bmp'],
    listOrder: 'number',
    manualBody: [
      'この取説は、このサイトを利用する方向けの案内です。',
      '',
      '# 【HOME】',
      '- サイト管理者が設定したHOMEリンクへ移動します。',
      '- 外部サイトやトップページなど、このサイトごとの案内先が開きます。',
      '',
      '# 【一覧】',
      '## 投稿を見る',
      '- 投稿作品とコメントの一部を、サイトで設定された並び順で確認できます。',
      '- 作品画像、タイトル、本文、作者名、投稿日時、閲覧数、コメント数、簡単リアクション数、SNSリアクション数が表示されます。',
      '- タイトルや画像を選ぶと、その投稿の個別ページを開けます。',
      '## コメントと簡単リアクション',
      '- 投稿ごとにコメントフォームを開けます。',
      '- 簡単リアクションでは、サイトで設定された定型文を短いコメントとして送信できます。',
      '- ログイン中は一覧上の簡単リアクションボタンから、設定済みの名前ですぐ投稿できます。',
      '## 作者アイコン',
      '- 作者アイコンにカーソルを合わせると拡大表示されます。',
      '- 作者アイコンをクリックすると、その作者の作品一覧を表示できます。',
      '',
      '# 【投稿】',
      '## 入力項目',
      '- 名前、タイトル、URL / HOME、画像、本文、投稿パスワードを入力します。',
      '- 画像は管理画面で許可された形式を使用できます。初期設定ではGIF、PNG、JPEG、JPG、BMPに対応し、選択した画像は投稿前にプレビューできます。',
      '- 投稿パスワードは、あとから編集や削除をするときに使います。',
      '## 投稿オプション',
      '- {{gdgdLabel}}が有効なサイトでは、通常投稿とは別の枠色で投稿できます。',
      '- SNS転記が有効なサイトでは、投稿時にSNSへ転記できます。',
      '- SNS転記OFFを選ぶとSNSへ転記しません。',
      '',
      '# 【削除】',
      '- 投稿やコメントは、投稿時のパスワードで削除できます。',
      '- 削除した投稿は消え、管理者しか復元できないため慎重に操作してください。',
      '- 親投稿を削除すると、その投稿についたコメントも非表示になります。',
      '- 定型文の簡単リアクションを削除するには、管理者パスワードが必要です。',
      '',
      '# 【編集】',
      '- 投稿やコメントは、投稿時のパスワードで編集できます。',
      '- 定型文の簡単リアクションは編集できません。',
      '- 投稿を編集しても、すでにSNSへ転記された内容は変更されません。',
      '- 投稿を削除した場合は、対応するSNS投稿も削除されます。',
      '- 編集された投稿やコメントには rev01 のような編集回数が表示されます。カーソルを合わせると編集日時を確認できます。',
      '',
      '# 【検索】',
      '- タイトル、本文、投稿者名を対象に投稿を探せます。',
      '- 検索結果を選ぶと、一覧の該当投稿位置へ移動します。',
      '',
      '# 【順位】',
      '- コメント数、閲覧数、簡単リアクション数、SNSリアクション数などを選び、数が多い投稿を確認できます。',
      '- タイトルを選ぶと、一覧の該当投稿位置へ移動します。',
      '',
      '# 【取説】',
      '- このページです。',
      '',
      '# 【ログイン】',
      '## ID作成とログイン',
      '- IDとログインパスワードを作成してログインできます。',
      '- ID作成時には、同じIDが使われていないか確認されます。',
      '- ログインすると、名前、投稿パスワード、URL / HOME、アイコンを保存できます。',
      '## ログイン中の投稿',
      '- 投稿やコメントでは、NAME欄の代わりに「ひとこと」を入力できます。',
      '- 名前は「ユーザー名@ひとこと」として表示されます。',
      '- 自分の投稿やコメントには、画面上に編集/削除リンクが表示されます。',
      '',
      '# 【ユーザー設定】',
      '- ログイン後のユーザー設定では、自分の投稿/返信一覧を確認できます。',
      '- 自分の投稿作品の統計を確認できます。',
      '- 過去投稿を自分の作品として紐づけできます。',
      '- 自分の作品として紐づけた投稿は作品一覧や統計に含まれます。',
      '- 作者アイコンや作者ページから、そのユーザーが投稿した作品と、自分の作品として紐づけた作品を確認できます。',
    ].join('\n'),
  },
};

export const DEFAULT_ADMIN_SETTINGS = {
  config: {
    ...DEFAULT_PUBLIC_SETTINGS.config,
    tweetBaseUrl: '',
    tweetConsumerKey: '',
    tweetConsumerSecret: '',
    tweetAccessToken: '',
    tweetAccessTokenSecret: '',
    blueskyEnabled: false,
    blueskyServiceUrl: '',
    blueskyPublicApiUrl: '',
    blueskyHandle: '',
    blueskyAppPassword: '',
    mastodonEnabled: false,
    mastodonInstanceUrl: '',
    mastodonAccessToken: '',
    mastodonVisibility: '',
    misskeyEnabled: false,
    misskeyInstanceUrl: '',
    misskeyAccessToken: '',
    ssoEnabled: false,
    ssoSharedSecret: '',
    listOrder: 'number',
    maxUploadBytes: 5100000,
    maxImageWidth: 1280,
    maxImageHeight: 960,
  },
  skin: {
    normalFrameColor: '#a23dff',
    gdgdFrameColor: '#6dffc0',
    backgroundColor: '#000000',
    pageTextColor: '#ffffff',
    linkColor: '#58a6ff',
    panelBackgroundColor: '#101821',
    panelTitleBackgroundColor: '#5b6572',
    panelBorderColor: '#738196',
    labelColor: '#8fc0ff',
    inputBackgroundColor: '#30343a',
    inputTextColor: '#ffffff',
    buttonBackgroundColor: '#3f74ff',
    buttonTextColor: '#ffffff',
    buttonBorderColor: '#8fb0ff',
    secondaryButtonBackgroundColor: '#2f333b',
    secondaryButtonTextColor: '#ffffff',
    secondaryButtonBorderColor: '#7a8495',
    normalHeaderColor: '#7f00a8',
    normalTextColor: '#ffffff',
    gdgdHeaderColor: '#39988a',
    gdgdTextColor: '#ffffff',
    replyBorderColor: '#7a8495',
    quickReactionButtonBackgroundColor: '#30343b',
    dangerColor: '#ff7c7c',
    warningColor: '#ffd36a',
    successColor: '#8dff8d',
  },
};

export function apiBase(): string {
  return import.meta.env.VITE_API_BASE_URL || 'api.php';
}

export function mediaUrl(path?: string | null): string | null {
  if (!path) {
    return null;
  }

  if (/^https?:\/\//i.test(path)) {
    return path;
  }

  const base = new URL(apiBase(), window.location.href);
  const relativePath = path.startsWith('/') ? path.slice(1) : path;
  return new URL(relativePath, base).toString();
}

export function isMockMode(): boolean {
  return (import.meta.env.VITE_USE_MOCK ?? 'true') === 'true';
}

const DEFAULT_FETCH_TIMEOUT_MS = 60000;

function summarizeResponseText(text: string): string {
  const trimmed = text.replace(/\s+/g, ' ').trim();
  if (!trimmed) {
    return '';
  }
  return trimmed.length > 500 ? `${trimmed.slice(0, 500)}...` : trimmed;
}

export async function fetchJson<T>(input: RequestInfo, init?: RequestInit): Promise<T> {
  if (isMockMode()) {
    return new Promise((resolve) => {
      setTimeout(() => resolve(mockApiResponse(input, init)), 500);
    });
  }

  const controller = new AbortController();
  const timeoutId = window.setTimeout(() => controller.abort(), DEFAULT_FETCH_TIMEOUT_MS);
  const signal = init?.signal ?? controller.signal;

  let response: Response;
  try {
    response = await fetch(input, { ...init, signal });
  } catch (err) {
    if ((err as Error).name === 'AbortError') {
      throw new Error('APIの応答がタイムアウトしました。サーバー側のエラーまたは処理待ちの可能性があります。');
    }
    throw err;
  } finally {
    window.clearTimeout(timeoutId);
  }

  const text = await response.text();
  if (!response.ok) {
    let message = `${response.status} ${response.statusText}`;
    try {
      const payload = JSON.parse(text);
      if (payload?.message) {
        message = `${message}: ${payload.message}`;
      }
    } catch {
      const summary = summarizeResponseText(text);
      if (summary) {
        message = `${message}: ${summary}`;
      }
    }
    throw new Error(message);
  }
  try {
    return JSON.parse(text) as T;
  } catch {
    const summary = summarizeResponseText(text);
    throw new Error(summary ? `APIの応答がJSONではありません: ${summary}` : 'APIの応答が空です。');
  }
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
    tweet_text: '[DT000000：テストスレッド]\n作者：テストユーザー\n\nこれはテストの投稿です。\n\n#art',
    tweet_url: 'https://x.com/threadforge/status/1',
    view_count: 12,
    board_reactions: { views: 12, eejanaika: 4, omigoto: 2, goodjob: 1 },
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
    view_count: 5,
    board_reactions: { views: 5, eejanaika: 0, omigoto: 0, goodjob: 0 },
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
    view_count: 8,
    board_reactions: { views: 8, eejanaika: 1, omigoto: 0, goodjob: 0 },
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
    case 'listThreads': {
      const years = splitQuerySet(params.get('years'));
      const months = splitQuerySet(params.get('months'));
      const filteredPosts = filterMockPostsByPeriod(mockPosts, years, months);
      const page = Math.max(1, Number(params.get('page') || '1') || 1);
      const limit = Math.max(1, Number(params.get('limit') || String(filteredPosts.length)) || filteredPosts.length);
      const start = (page - 1) * limit;
      return filteredPosts.slice(start, start + limit) as T;
    }
    case 'listAdminThreads': {
      const years = splitQuerySet(params.get('years'));
      const months = splitQuerySet(params.get('months'));
      const filteredPosts = filterMockPostsByPeriod(mockPosts, years, months);
      const page = Math.max(1, Number(params.get('page') || '1') || 1);
      const limit = Math.max(1, Number(params.get('limit') || String(filteredPosts.length)) || filteredPosts.length);
      const start = (page - 1) * limit;
      return filteredPosts.slice(start, start + limit) as T;
    }
    case 'listThreadArchiveMeta': {
      const years = splitQuerySet(params.get('years'));
      const months = splitQuerySet(params.get('months'));
      const periodMap = new Map<string, number>();
      mockPosts.forEach((post) => {
        const key = post.created_at.slice(0, 7);
        periodMap.set(key, (periodMap.get(key) ?? 0) + 1);
      });
      const periods = [...periodMap.entries()]
        .map(([key, count]) => ({ year: key.slice(0, 4), month: key.slice(5, 7), count }))
        .sort((a, b) => `${b.year}-${b.month}`.localeCompare(`${a.year}-${a.month}`));
      return {
        success: true,
        periods,
        total: filterMockPostsByPeriod(mockPosts, years, months).length,
      } as T;
    }
    case 'listDeletedPosts':
      return [] as T;
    case 'listAnalyticsPosts':
    case 'listBoardAnalyticsPosts':
    case 'listRankingPosts':
      return mockPosts as T;
    case 'listUserDashboard':
      return { success: true, posts: mockPosts, analytics_posts: mockPosts } as T;
    case 'listUserPosts':
      return {
        success: true,
        user: {
          id: 1,
          login_id: 'blank',
          display_name: 'Blank',
          post_password: '',
          home_url: null,
          icon_path: null,
        },
        posts: mockPosts.filter((post) => post.parent_id === 0),
      } as T;
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
    case 'loginUser':
    case 'ssoLogin':
    case 'registerUser':
    case 'currentUser':
    case 'updateUserProfile':
      return {
        success: true,
        token: 'mock-token',
        user: {
          id: 1,
          login_id: 'blank',
          display_name: 'Blank',
          post_password: 'password',
          home_url: 'https://example.com',
          icon_path: null,
        },
      } as T;
    case 'checkLoginId':
      return { success: true, available: true } as T;
    case 'adminStatus':
      return { success: true, adminPasswordConfigured: true } as T;
    case 'getSettings':
      return {
        success: true,
        settings: DEFAULT_ADMIN_SETTINGS,
        system: {
          cronPath: '/home/example/threadforge/server/cron.php',
          cronApiUrl: 'https://example.com/api.php?action=cronRefreshSocialReactions&api_key=',
          cronApiKey: 'mock-cron-api-key',
          adminPasswordConfigured: true,
        },
      } as T;
    case 'search': {
      const q = params.get('q') || '';
      const scope = params.get('scope') || 'all';
      const results = mockPosts.filter(p =>
        scope === 'title' ? p.title.includes(q)
          : scope === 'message' ? p.message.includes(q)
            : scope === 'name' ? p.name.includes(q)
              : p.title.includes(q) || p.message.includes(q) || p.name.includes(q)
      );
      const page = Math.max(1, Number(params.get('page') || '1') || 1);
      const limit = Math.max(1, Number(params.get('limit') || String(results.length)) || results.length);
      const start = (page - 1) * limit;
      return results.slice(start, start + limit) as T;
    }
    case 'createPost':
    case 'updatePost':
    case 'deletePost':
    case 'recordPostView':
    case 'recordAccess':
    case 'claimUserPost':
    case 'unclaimUserPost':
    case 'restorePost':
    case 'purgePostData':
    case 'destroyPostNumber':
    case 'adminDeletePosts':
    case 'adminUpdateUser':
    case 'adminDeleteUser':
    case 'updateSettings':
    case 'initializeAdminPassword':
    case 'changeAdminPassword':
    case 'importBackup':
      return { success: true, message: '操作が完了しました（モック）' } as T;
    case 'adminCheckIntegrity':
    case 'renumberPostsByCreatedAt':
      return {
        success: true,
        message: 'DBを確認しました（モック）',
        orphan_replies: 0,
        missing_image_post_ids: [],
      } as T;
    case 'listAdminUsers':
      return {
        success: true,
        users: [{
          id: 1,
          login_id: 'blank',
          display_name: 'Blank',
          post_password: 'password',
          home_url: '',
          icon_path: null,
          created_at: '2024-01-01 00:00:00',
          updated_at: '2024-01-01 00:00:00',
          last_login_at: null,
          last_session_at: null,
          active_session_count: 0,
          post_count: 1,
          claim_count: 0,
        }],
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

function splitQuerySet(value: string | null): Set<string> {
  return new Set((value ?? '').split(',').map((item) => item.trim()).filter(Boolean));
}

function filterMockPostsByPeriod(posts: Post[], years: Set<string>, months: Set<string>): Post[] {
  if (years.size === 0 && months.size === 0) {
    return posts;
  }
  return posts.filter((post) => {
    const year = post.created_at.slice(0, 4);
    const month = post.created_at.slice(0, 7);
    return months.has(month) || (years.has(year) && ![...months].some((selectedMonth) => selectedMonth.startsWith(`${year}-`)));
  });
}

export const api = {
  listThreads: async (
    targetId?: number | string | null,
    page?: number | string | null,
    limit?: number | string | null,
    years?: string[] | null,
    months?: string[] | null,
  ): Promise<Post[]> => {
    const target = targetId ? `&target_id=${encodeURIComponent(String(targetId))}` : '';
    const pageParam = page ? `&page=${encodeURIComponent(String(page))}` : '';
    const limitParam = limit ? `&limit=${encodeURIComponent(String(limit))}` : '';
    const yearsParam = years && years.length > 0 ? `&years=${encodeURIComponent(years.join(','))}` : '';
    const monthsParam = months && months.length > 0 ? `&months=${encodeURIComponent(months.join(','))}` : '';
    return fetchJson<Post[]>(`${apiBase()}?action=listThreads${target}${pageParam}${limitParam}${yearsParam}${monthsParam}`);
  },
  listAdminThreads: async (
    adminPassword: string,
    page?: number | string | null,
    limit?: number | string | null,
    years?: string[] | null,
    months?: string[] | null,
  ): Promise<Post[]> => {
    const pageParam = page ? `&page=${encodeURIComponent(String(page))}` : '';
    const limitParam = limit ? `&limit=${encodeURIComponent(String(limit))}` : '';
    const yearsParam = years && years.length > 0 ? `&years=${encodeURIComponent(years.join(','))}` : '';
    const monthsParam = months && months.length > 0 ? `&months=${encodeURIComponent(months.join(','))}` : '';
    return fetchJson<Post[]>(`${apiBase()}?action=listAdminThreads&admin_password=${encodeURIComponent(adminPassword)}${pageParam}${limitParam}${yearsParam}${monthsParam}`);
  },
  listThreadArchiveMeta: async (years?: string[] | null, months?: string[] | null): Promise<ThreadArchiveMeta> => {
    const yearsParam = years && years.length > 0 ? `&years=${encodeURIComponent(years.join(','))}` : '';
    const monthsParam = months && months.length > 0 ? `&months=${encodeURIComponent(months.join(','))}` : '';
    return fetchJson<ThreadArchiveMeta>(`${apiBase()}?action=listThreadArchiveMeta${yearsParam}${monthsParam}`);
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
  recordAccess: async (): Promise<{ success: boolean; access_count: number }> => {
    const formData = new FormData();
    formData.append('action', 'recordAccess');
    return fetchJson(`${apiBase()}`, { method: 'POST', body: formData });
  },
  search: async (
    q: string,
    scope = 'all',
    page?: number | string | null,
    limit?: number | string | null,
    kinds?: string | null,
    order?: 'newest' | 'oldest' | string | null,
  ): Promise<SearchResult[]> => {
    const pageParam = page ? `&page=${encodeURIComponent(String(page))}` : '';
    const limitParam = limit ? `&limit=${encodeURIComponent(String(limit))}` : '';
    const kindsParam = kinds ? `&kinds=${encodeURIComponent(kinds)}` : '';
    const orderParam = order ? `&order=${encodeURIComponent(order)}` : '';
    return fetchJson<SearchResult[]>(`${apiBase()}?action=search&q=${encodeURIComponent(q)}&scope=${encodeURIComponent(scope)}${pageParam}${limitParam}${kindsParam}${orderParam}`);
  },
  checkLoginId: async (loginId: string): Promise<{ success: boolean; available: boolean; message?: string }> => {
    return fetchJson(`${apiBase()}?action=checkLoginId&login_id=${encodeURIComponent(loginId)}`);
  },
  loginUser: async (loginId: string, password: string): Promise<{ success: boolean; token: string; user: UserProfile; message?: string }> => {
    const formData = new FormData();
    formData.append('action', 'loginUser');
    formData.append('login_id', loginId);
    formData.append('password', password);
    return fetchJson(`${apiBase()}`, { method: 'POST', body: formData });
  },
  ssoLogin: async (token: string): Promise<{ success: boolean; token: string; user: UserProfile; message?: string }> => {
    const formData = new FormData();
    formData.append('action', 'ssoLogin');
    formData.append('token', token);
    return fetchJson(`${apiBase()}`, { method: 'POST', body: formData });
  },
  registerUser: async (payload: { login_id: string; password: string; display_name?: string; post_password?: string; home_url?: string; icon?: File | null }): Promise<{ success: boolean; token: string; user: UserProfile; message?: string }> => {
    const formData = new FormData();
    formData.append('action', 'registerUser');
    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null) return;
      if (key === 'icon' && value instanceof File) {
        formData.append('icon', value);
      } else {
        formData.append(key, String(value));
      }
    });
    return fetchJson(`${apiBase()}`, { method: 'POST', body: formData });
  },
  currentUser: async (token: string): Promise<{ success: boolean; user: UserProfile }> => {
    return fetchJson(`${apiBase()}?action=currentUser&auth_token=${encodeURIComponent(token)}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
  },
  updateUserProfile: async (payload: { token: string; display_name: string; post_password: string; home_url?: string; icon?: File | null }): Promise<{ success: boolean; user: UserProfile; message?: string }> => {
    const formData = new FormData();
    formData.append('action', 'updateUserProfile');
    formData.append('auth_token', payload.token);
    formData.append('display_name', payload.display_name);
    formData.append('post_password', payload.post_password);
    formData.append('home_url', payload.home_url ?? '');
    if (payload.icon) {
      formData.append('icon', payload.icon);
    }
    return fetchJson(`${apiBase()}`, { method: 'POST', body: formData });
  },
  listUserDashboard: async (token: string): Promise<{ success: boolean; posts: Post[]; analytics_posts: Post[] }> => {
    return fetchJson(`${apiBase()}?action=listUserDashboard&auth_token=${encodeURIComponent(token)}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
  },
  listUserPosts: async (userId: string): Promise<{ success: boolean; user: UserProfile; posts: Post[] }> => {
    return fetchJson(`${apiBase()}?action=listUserPosts&user_id=${encodeURIComponent(userId)}`);
  },
  claimUserPost: async (token: string, id: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'claimUserPost');
    formData.append('auth_token', token);
    formData.append('id', id);
    return fetchJson(`${apiBase()}`, { method: 'POST', body: formData });
  },
  unclaimUserPost: async (token: string, id: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'unclaimUserPost');
    formData.append('auth_token', token);
    formData.append('id', id);
    return fetchJson(`${apiBase()}`, { method: 'POST', body: formData });
  },
  logoutUser: async (token: string): Promise<{ success: boolean }> => {
    const formData = new FormData();
    formData.append('action', 'logoutUser');
    formData.append('auth_token', token);
    return fetchJson(`${apiBase()}`, { method: 'POST', body: formData });
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
  deletePost: async (id: string, password: string, token?: string | null): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'deletePost');
    formData.append('id', id);
    formData.append('password', password);
    if (token) {
      formData.append('auth_token', token);
    }
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  recordPostView: async (id: number): Promise<{ success: boolean; view_count: number }> => {
    const formData = new FormData();
    formData.append('action', 'recordPostView');
    formData.append('id', String(id));
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  listDeletedPosts: async (adminPassword: string): Promise<Post[]> => {
    return fetchJson<Post[]>(`${apiBase()}?action=listDeletedPosts&admin_password=${encodeURIComponent(adminPassword)}`);
  },
  listAnalyticsPosts: async (adminPassword: string): Promise<Post[]> => {
    return fetchJson<Post[]>(`${apiBase()}?action=listAnalyticsPosts&admin_password=${encodeURIComponent(adminPassword)}`);
  },
  listBoardAnalyticsPosts: async (): Promise<Post[]> => {
    return fetchJson<Post[]>(`${apiBase()}?action=listBoardAnalyticsPosts`);
  },
  listRankingPosts: async (metric = 'views'): Promise<Post[]> => {
    return fetchJson<Post[]>(`${apiBase()}?action=listRankingPosts&metric=${encodeURIComponent(metric)}`);
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
  purgePostData: async (id: string, adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'purgePostData');
    formData.append('id', id);
    formData.append('admin_password', adminPassword);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  destroyPostNumber: async (id: string, adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'destroyPostNumber');
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
  listAdminUsers: async (adminPassword: string): Promise<{ success: boolean; users: AdminUser[] }> => {
    return fetchJson(`${apiBase()}?action=listAdminUsers&admin_password=${encodeURIComponent(adminPassword)}`);
  },
  adminUpdateUser: async (payload: Partial<AdminUser> & { id: number; login_password?: string }, adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'adminUpdateUser');
    formData.append('admin_password', adminPassword);
    Object.entries(payload).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        formData.append(key, String(value));
      }
    });
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  adminDeleteUser: async (id: number, stage: 1 | 2, adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'adminDeleteUser');
    formData.append('id', String(id));
    formData.append('stage', String(stage));
    formData.append('admin_password', adminPassword);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  adminCheckIntegrity: async (adminPassword: string): Promise<{ success: boolean; message: string; orphan_replies: number; missing_image_post_ids: number[] }> => {
    return fetchJson(`${apiBase()}?action=adminCheckIntegrity&admin_password=${encodeURIComponent(adminPassword)}`);
  },
  renumberPostsByCreatedAt: async (adminPassword: string): Promise<{ success: boolean; message: string; renumbered_posts: number; renumbered_threads: number }> => {
    const formData = new FormData();
    formData.append('action', 'renumberPostsByCreatedAt');
    formData.append('admin_password', adminPassword);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
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
  getSettings: async (adminPassword: string): Promise<{ success: boolean; settings: any; system?: { cronPath?: string; cronApiUrl?: string; cronApiKey?: string; adminPasswordConfigured?: boolean } }> => {
    return fetchJson(`${apiBase()}?action=getSettings&admin_password=${encodeURIComponent(adminPassword)}`);
  },
  adminStatus: async (): Promise<{ success: boolean; adminPasswordConfigured: boolean }> => {
    return fetchJson(`${apiBase()}?action=adminStatus`);
  },
  initializeAdminPassword: async (newPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'initializeAdminPassword');
    formData.append('new_admin_password', newPassword);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  updateSettings: async (settings: any, adminPassword: string): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'updateSettings');
    formData.append('admin_password', adminPassword);
    formData.append('settings_b64', base64EncodeUtf8(JSON.stringify(settings)));
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
  changeAdminPassword: async (currentPassword: string, newPassword: string, confirmPassword = newPassword): Promise<{ success: boolean; message: string }> => {
    const formData = new FormData();
    formData.append('action', 'changeAdminPassword');
    formData.append('admin_password', currentPassword);
    formData.append('new_admin_password', newPassword);
    formData.append('new_admin_password_confirm', confirmPassword);
    return fetchJson(`${apiBase()}`, {
      method: 'POST',
      body: formData,
    });
  },
};

function defaultStatusText(status: number): string {
  return status === 500 ? 'Internal Server Error' : '';
}

function base64EncodeUtf8(value: string): string {
  const bytes = new TextEncoder().encode(value);
  let binary = '';
  bytes.forEach((byte) => {
    binary += String.fromCharCode(byte);
  });
  return btoa(binary);
}
