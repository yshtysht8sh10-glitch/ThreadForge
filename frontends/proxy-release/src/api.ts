export type MaterialTag = { id: number; name: string; sortOrder: number };
export type MaterialTerm = { id: number; label: string; description: string; sortOrder: number };
export type TermAnswer = MaterialTerm & { accepted: boolean | null };
export type MaterialMedia = { id: number; url: string; originalName: string; sizeBytes: number };
export type MaterialDesign = {
  pageBackgroundColor: string;
  pageTextColor: string;
  headerBackgroundColor: string;
  headerTextColor: string;
  headerBorderColor: string;
  panelBackgroundColor: string;
  panelBorderColor: string;
  headingBackgroundColor: string;
  headingTextColor: string;
  accentColor: string;
  buttonBackgroundColor: string;
  buttonTextColor: string;
  secondaryButtonBackgroundColor: string;
  secondaryButtonTextColor: string;
  dangerButtonBackgroundColor: string;
  dangerButtonTextColor: string;
  inputBackgroundColor: string;
  inputTextColor: string;
  imageBackgroundColor: string;
  mutedTextColor: string;
  positiveColor: string;
  negativeColor: string;
  unknownColor: string;
};
export type MaterialSettings = {
  title: string;
  description: string;
  homePageUrl: string;
  trialPlayUrl: string;
  trialPlayButtonsEnabled: boolean;
  manualBody: string;
  groupParent: 'tag' | 'author';
  maxArchiveKb: number;
  maxImageKb: number;
  allowedArchiveExtensions: string;
  ssoEnabled: boolean;
  design: MaterialDesign;
};
export const defaultMaterialDesign: MaterialDesign = {
  pageBackgroundColor: '#050505',
  pageTextColor: '#f2f2f2',
  headerBackgroundColor: '#000000',
  headerTextColor: '#ffffff',
  headerBorderColor: '#222222',
  panelBackgroundColor: '#111820',
  panelBorderColor: '#6c7787',
  headingBackgroundColor: '#59636f',
  headingTextColor: '#ffffff',
  accentColor: '#8fb0ff',
  buttonBackgroundColor: '#3974ee',
  buttonTextColor: '#ffffff',
  secondaryButtonBackgroundColor: '#303640',
  secondaryButtonTextColor: '#ffffff',
  dangerButtonBackgroundColor: '#c44141',
  dangerButtonTextColor: '#ffffff',
  inputBackgroundColor: '#282f38',
  inputTextColor: '#ffffff',
  imageBackgroundColor: '#020202',
  mutedTextColor: '#aab4c0',
  positiveColor: '#72e9a2',
  negativeColor: '#ff9292',
  unknownColor: '#f0cf78',
};
export type MaterialItem = {
  id: number;
  userId: number | null;
  authorKey: string;
  authorName: string;
  authorIcon: string | null;
  name: string;
  notes: string;
  tagId: number;
  tagName: string;
  primaryTagId: number;
  primaryTagName: string;
  archiveUrl: string;
  archiveOriginalName: string;
  archiveSizeBytes: number;
  imageUrl: string | null;
  imageOriginalName: string | null;
  createdAt: string;
  updatedAt: string;
  viewCount: number;
  draft: boolean;
  deletedAt: string | null;
  adminOnly: boolean;
  publicationType: 'normal' | 'test';
  visibility: 'public' | 'unlisted';
  expiresAt: string | null;
  testMemo: string;
  playUrl?: string | null;
  terms: TermAnswer[];
  media: MaterialMedia[];
};
export type AdminMaterialItem = MaterialItem & {
  archiveFile: string;
  archivePath: string;
  imagePath: string | null;
  legacySource: string | null;
  passwordConfigured: boolean;
  webMugenCharacterId: string | null;
  trialPlayError: string | null;
};
export type User = {
  id: number;
  login_id: string;
  display_name: string;
  post_password: string;
  home_url: string | null;
  icon_path: string | null;
  materials_author_name: string | null;
  materials_default_terms: Record<string, boolean>;
};
export type TrialPlayResult = {
  success: boolean;
  kind?: 'character' | 'stage';
  contentId?: string;
  characterId?: string;
  characterPath?: string;
  stageId?: string;
  stagePath?: string;
  playUrl?: string;
  code?: string;
  message?: string;
  skipped?: boolean;
};
export type TrialPlayBulkSummary = {
  target: number;
  succeeded: number;
  failed: number;
  failures: Array<{ id: number; name: string; code: string; message: string }>;
};
export type AdminUser = User & {
  created_at: string;
  updated_at: string;
  last_login_at: string | null;
  last_session_at: string | null;
  active_session_count: number;
  post_count: number;
  claim_count: number;
  material_count: number;
};

export const frontendId = 'proxy-release';
export const apiBase = () => import.meta.env.VITE_API_BASE_URL || `http://127.0.0.1:8000/api.php?frontend_id=${frontendId}`;

async function request<T>(action: string, values: Record<string, string> = {}, method: 'GET' | 'POST' = 'GET', body?: FormData): Promise<T> {
  let response: Response;
  if (method === 'GET') {
    const url = new URL(apiBase(), window.location.href);
    url.searchParams.set('frontend_id', frontendId);
    url.searchParams.set('action', action);
    Object.entries(values).forEach(([key, value]) => url.searchParams.set(key, value));
    response = await fetch(url);
  } else {
    const payload = body ?? new FormData();
    payload.set('frontend_id', frontendId);
    payload.set('action', action);
    Object.entries(values).forEach(([key, value]) => payload.set(key, value));
    response = await fetch(apiBase(), { method: 'POST', body: payload });
  }
  const text = await response.text();
  let data: any;
  try {
    data = JSON.parse(text);
  } catch {
    const htmlResponse = /^\s*</.test(text);
    throw new Error(htmlResponse
      ? `APIがHTMLエラーを返しました（HTTP ${response.status}）。api.phpの配置、PHPエラーログ、アップロード上限を確認してください。`
      : (text.trim() || `API request failed (HTTP ${response.status}).`));
  }
  if (!response.ok || data.success === false) throw new Error(data.message || `API request failed (HTTP ${response.status}).`);
  return data as T;
}

export const api = {
  settings: () => request<{ settings: MaterialSettings; tags: MaterialTag[]; terms: MaterialTerm[] }>('materialsSettings'),
  items: () => request<{ items: MaterialItem[] }>('listMaterialItems'),
  item: (id: number) => request<{ item: MaterialItem }>('getMaterialItem', { id: String(id) }),
  create: (body: FormData, token: string) => request<{ message: string; trialPlay: TrialPlayResult; item: MaterialItem }>('createMaterialItem', token ? { auth_token: token } : {}, 'POST', body),
  update: (body: FormData, token: string) => request<{ message: string; trialPlay: TrialPlayResult; item: MaterialItem }>('updateMaterialItem', token ? { auth_token: token } : {}, 'POST', body),
  verify: (id: number, password: string, token: string) => request<{ item: MaterialItem }>('verifyMaterialPassword', {
    id: String(id), password, ...(token ? { auth_token: token } : {}),
  }, 'POST'),
  promote: (body: FormData, token: string) => request<{ message: string; trialPlay: TrialPlayResult; item: MaterialItem }>('promoteTestMaterialItem', token ? { auth_token: token } : {}, 'POST', body),
  remove: (id: number, password: string, token: string) => request<{ message: string }>('deleteMaterialItem', {
    id: String(id), password, ...(token ? { auth_token: token } : {}),
  }, 'POST'),
  login: (loginId: string, password: string) => request<{ token: string; user: User }>('loginUser', { login_id: loginId, password }, 'POST'),
  register: (body: FormData) => request<{ token: string; user: User }>('registerUser', {}, 'POST', body),
  sso: (token: string) => request<{ token: string; user: User }>('ssoLogin', { token }, 'POST'),
  currentUser: (token: string) => request<{ user: User }>('currentUser', { auth_token: token }),
  logout: (token: string) => request('logoutUser', { auth_token: token }, 'POST'),
  updateProfile: (body: FormData, token: string) => request<{ user: User }>('updateMaterialProfile', { auth_token: token }, 'POST', body),
  adminStatus: () => request<{ adminPasswordConfigured: boolean }>('adminStatus'),
  getAdmin: (password: string) => request<{
    settings: { config: Record<string, unknown>; skin: Record<string, unknown> };
    webMugen: { tokenConfigured: boolean };
  }>('getSettings', { admin_password: password }),
  updateSettings: (password: string, settings: unknown, webMugenApiToken = '') => request<{ message: string }>('updateSettings', {
    admin_password: password,
    settings_b64: base64EncodeUtf8(JSON.stringify(settings)),
    ...(webMugenApiToken ? { webmugen_api_token: webMugenApiToken } : {}),
  }, 'POST'),
  saveCatalog: (password: string, tags: MaterialTag[], terms: MaterialTerm[]) => request<{ message: string }>('saveMaterialCatalog', {
    admin_password: password, tags: JSON.stringify(tags), terms: JSON.stringify(terms),
  }, 'POST'),
  deleted: (password: string) => request<{ items: MaterialItem[] }>('listDeletedMaterialItems', { admin_password: password }),
  adminDelete: (password: string, ids: number[]) => request<{ message: string }>('adminDeleteMaterialItems', {
    admin_password: password, ids: ids.join(','),
  }, 'POST'),
  restore: (password: string, ids: number[]) => request<{ message: string }>('restoreMaterialItems', {
    admin_password: password, ids: ids.join(','),
  }, 'POST'),
  purge: (password: string, ids: number[]) => request<{ message: string }>('purgeMaterialItems', {
    admin_password: password, ids: ids.join(','),
  }, 'POST'),
  users: (password: string) => request<{ users: AdminUser[] }>('listAdminUsers', { admin_password: password }),
  updateUser: (
    password: string,
    user: AdminUser,
    loginPassword: string,
    loginPasswordConfirm: string,
    icon: File | null,
    removeIcon: boolean,
  ) => {
    const body = new FormData();
    body.set('admin_password', password);
    body.set('id', String(user.id));
    body.set('login_id', user.login_id);
    body.set('display_name', user.display_name);
    body.set('post_password', user.post_password);
    body.set('home_url', user.home_url ?? '');
    body.set('materials_author_name', user.materials_author_name ?? user.display_name);
    body.set('materials_default_terms', JSON.stringify(user.materials_default_terms));
    body.set('login_password', loginPassword);
    body.set('login_password_confirm', loginPasswordConfirm);
    body.set('remove_icon', removeIcon ? '1' : '0');
    if (icon) body.set('icon', icon);
    return request<{ message: string }>('adminUpdateUser', {}, 'POST', body);
  },
  deleteUser: (password: string, id: number, stage: 1 | 2) => request<{ message: string }>('adminDeleteUser', {
    admin_password: password, id: String(id), stage: String(stage),
  }, 'POST'),
  assignAuthor: (password: string, id: number, userId: string, authorName: string) => request<{ message: string }>('assignMaterialAuthor', {
    admin_password: password, id: String(id), user_id: userId, author_name: authorName,
  }, 'POST'),
  adminItems: (password: string) => request<{ items: AdminMaterialItem[] }>('listAdminMaterialItems', { admin_password: password }),
  adminItem: (password: string, id: number) => request<{ item: AdminMaterialItem }>('getAdminMaterialItem', {
    admin_password: password, id: String(id),
  }),
  adminUpdateItem: (password: string, body: FormData) => {
    body.set('admin_password', password);
    return request<{ message: string; item: AdminMaterialItem }>('adminUpdateMaterialItem', {}, 'POST', body);
  },
  adminPublishProxyRelease: (password: string, id: number) => request<{ message: string; trialPlay: TrialPlayResult; item: AdminMaterialItem }>('adminPublishProxyRelease', {
    admin_password: password, id: String(id),
  }, 'POST'),
  adminBulkPublishProxyReleases: (password: string) => request<{ message: string; summary: TrialPlayBulkSummary }>('adminBulkPublishProxyReleases', {
    admin_password: password,
  }, 'POST'),
  analytics: (password: string) => request<{ summary: Record<string, string>; months: Array<Record<string, string>> }>('materialAnalytics', { admin_password: password }),
  changeAdminPassword: (password: string, next: string) => request<{ message: string }>('changeAdminPassword', {
    admin_password: password, new_admin_password: next, new_admin_password_confirm: next,
  }, 'POST'),
  initializeAdminPassword: (next: string) => request<{ message: string }>('initializeAdminPassword', {
    new_admin_password: next, new_admin_password_confirm: next,
  }, 'POST'),
};

export function mediaUrl(path: string | null) {
  return path ? new URL(path, new URL(apiBase(), window.location.href)).toString() : null;
}

export function homeHref(value: string) {
  const trimmed = value.trim();
  if (/^[a-z][a-z0-9+.-]*:/i.test(trimmed) || trimmed.startsWith('/') || trimmed.startsWith('.')) return trimmed || './';
  return trimmed ? `https://${trimmed}` : './';
}

function base64EncodeUtf8(value: string) {
  const bytes = new TextEncoder().encode(value);
  let binary = '';
  bytes.forEach((byte) => { binary += String.fromCharCode(byte); });
  return btoa(binary);
}

export function groupMaterials(items: MaterialItem[], parent: 'tag' | 'author') {
  const outer = new Map<string, { label: string; groups: Map<string, { label: string; items: MaterialItem[] }> }>();
  items.forEach((item) => {
    const outerKey = parent === 'tag' ? `tag:${item.tagId}` : item.authorKey;
    const outerLabel = parent === 'tag' ? item.tagName : item.authorName;
    const innerKey = parent === 'tag' ? item.authorKey : `tag:${item.tagId}`;
    const innerLabel = parent === 'tag' ? item.authorName : item.tagName;
    if (!outer.has(outerKey)) outer.set(outerKey, { label: outerLabel, groups: new Map() });
    const group = outer.get(outerKey)!;
    if (!group.groups.has(innerKey)) group.groups.set(innerKey, { label: innerLabel, items: [] });
    group.groups.get(innerKey)!.items.push(item);
  });
  return [...outer.entries()].map(([key, value]) => ({
    key, label: value.label, groups: [...value.groups.entries()].map(([innerKey, group]) => ({ key: innerKey, ...group })),
  }));
}

export function packAuthorGroups<T extends { items: MaterialItem[] }>(groups: T[]): T[][] {
  const rows: T[][] = [];
  let current: T[] = [];
  let itemCount = 0;
  groups.forEach((group) => {
    if (group.items.length > 2) {
      if (current.length) rows.push(current);
      rows.push([group]);
      current = [];
      itemCount = 0;
      return;
    }
    if (current.length && itemCount + group.items.length > 4) {
      rows.push(current);
      current = [];
      itemCount = 0;
    }
    current.push(group);
    itemCount += group.items.length;
  });
  if (current.length) rows.push(current);
  return rows;
}
