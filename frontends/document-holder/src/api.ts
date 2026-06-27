export type MaterialTag = { id: number; name: string; parentId: number | null; sortOrder: number };
export type MaterialTerm = { id: number; label: string; description: string; sortOrder: number };
export type TermAnswer = MaterialTerm & { accepted: boolean | null };

export type DescriptionBanner = {
  imageUrl: string;
  linkUrl: string;
  alt: string;
};

export type MaterialSettings = {
  title: string;
  description: string;
  descriptionBannerEnabled: boolean;
  descriptionBannerImageUrl: string;
  descriptionBannerLinkUrl: string;
  descriptionBannerAlt: string;
  descriptionBannerIntervalMs: number;
  descriptionBanners: DescriptionBanner[];
  homePageUrl: string;
  manualBody: string;
  groupParent: 'tag' | 'author';
  maxArchiveKb: number;
  maxImageKb: number;
  allowedArchiveExtensions: string;
  ssoEnabled: boolean;
  design: MaterialDesign;
};

export type MaterialDesign = {
  pageBackgroundColor: string;
  pageTextColor: string;
  headerBackgroundColor: string;
  headerTextColor: string;
  headerBorderColor: string;
  panelBackgroundColor: string;
  panelBorderColor: string;
  headingBackgroundColor: string;
  headingBackgroundImageUrl: string;
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
  formLabelColor: string;
  editorBackgroundColor: string;
  editorTextColor: string;
  editorBorderColor: string;
  toolbarBackgroundColor: string;
  selectionBackgroundColor: string;
  selectionHoverBackgroundColor: string;
  selectionTextColor: string;
  selectionMetaColor: string;
  imageBackgroundColor: string;
  mutedTextColor: string;
  listRowTextColor: string;
  listRowMetaColor: string;
  listRowBorderColor: string;
  tocGroupTitleColor: string;
  positiveColor: string;
  negativeColor: string;
  unknownColor: string;
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
  headingBackgroundImageUrl: '',
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
  formLabelColor: '#aac4df',
  editorBackgroundColor: '#070b10',
  editorTextColor: '#f5f7fb',
  editorBorderColor: '#6c7787',
  toolbarBackgroundColor: '#0c1219',
  selectionBackgroundColor: '#090e14',
  selectionHoverBackgroundColor: '#151f2a',
  selectionTextColor: '#f2f2f2',
  selectionMetaColor: '#aab4c0',
  imageBackgroundColor: '#020202',
  mutedTextColor: '#aab4c0',
  listRowTextColor: '#f2f2f2',
  listRowMetaColor: '#aab4c0',
  listRowBorderColor: '#3e4753',
  tocGroupTitleColor: '#f2f2f2',
  positiveColor: '#72e9a2',
  negativeColor: '#ff9292',
  unknownColor: '#f0cf78',
};

export type MaterialItem = {
  id: number;
  userId: number | null;
  authorKey: string;
  authorName: string;
  name: string;
  notes: string;
  tagId: number;
  tagName: string;
  primaryTagId: number;
  primaryTagName: string;
  subtagId: number | null;
  subtagName: string | null;
  archiveUrl: string;
  archiveOriginalName: string;
  archiveSizeBytes: number;
  createdAt: string;
  updatedAt: string;
  viewCount: number;
  draft: boolean;
  deletedAt: string | null;
  adminOnly: boolean;
  terms: TermAnswer[];
};

export type User = {
  id: number;
  login_id: string;
  display_name: string;
  post_password: string;
  materials_author_name: string | null;
  materials_default_terms: Record<string, boolean>;
  materials_default_group_parent: 'tag' | 'author' | null;
};

export const apiBase = () => import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api.php';

async function request<T>(action: string, values: Record<string, string> = {}, method: 'GET' | 'POST' = 'GET', body?: FormData): Promise<T> {
  const params = { frontend_id: 'document-holder', ...values };
  let response: Response;
  if (method === 'GET') {
    const url = new URL(apiBase(), window.location.href);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
    response = await fetch(url);
  } else {
    const payload = body ?? new FormData();
    payload.set('action', action);
    Object.entries(params).forEach(([key, value]) => payload.set(key, value));
    response = await fetch(apiBase(), { method: 'POST', body: payload });
  }
  const text = await response.text();
  let data: any;
  try {
    data = JSON.parse(text);
  } catch {
    throw new Error(text.trim() || `API request failed (HTTP ${response.status}).`);
  }
  if (!response.ok || data.success === false) throw new Error(data.message || `API request failed (HTTP ${response.status}).`);
  return data as T;
}

export const api = {
  settings: () => request<{ settings: MaterialSettings; tags: MaterialTag[]; terms: MaterialTerm[] }>('materialsSettings'),
  items: () => request<{ items: MaterialItem[] }>('listMaterialItems'),
  create: (body: FormData, token: string) => request<{ message: string }>('createMaterialItem', token ? { auth_token: token } : {}, 'POST', body),
  update: (body: FormData, token: string) => request<{ message: string }>('updateMaterialItem', token ? { auth_token: token } : {}, 'POST', body),
  remove: (id: number, password: string, token: string) => request<{ message: string }>('deleteMaterialItem', { id: String(id), password, ...(token ? { auth_token: token } : {}) }, 'POST'),
  login: (loginId: string, password: string) => request<{ token: string; user: User }>('loginUser', { login_id: loginId, password }, 'POST'),
  currentUser: (token: string) => request<{ user: User }>('currentUser', { auth_token: token }),
  logout: (token: string) => request('logoutUser', { auth_token: token }, 'POST'),
  updateMaterialProfile: (body: FormData, token: string) => request<{ user: User }>('updateMaterialProfile', { auth_token: token }, 'POST', body),
  recordView: (id: number) => request<{ view_count: number }>('recordMaterialView', { id: String(id) }, 'POST'),
  materialAnalytics: (password: string) => request<{ summary: Record<string, number>; months: Array<Record<string, string | number>> }>('materialAnalytics', { admin_password: password }),
  adminStatus: () => request<{ adminPasswordConfigured: boolean }>('adminStatus'),
  getAdmin: (password: string) => request<{ settings: { config: Record<string, unknown>; skin: Record<string, unknown> } }>('getSettings', { admin_password: password }),
  updateSettings: (password: string, settings: unknown) => request<{ message: string }>('updateSettings', {
    admin_password: password, settings_b64: base64EncodeUtf8(JSON.stringify(settings)),
  }, 'POST'),
  saveCatalog: (password: string, tags: MaterialTag[], terms: MaterialTerm[]) => request<{ message: string }>('saveMaterialCatalog', {
    admin_password: password,
    tags: JSON.stringify(tags),
    terms: JSON.stringify(terms),
  }, 'POST'),
  initializeAdminPassword: (next: string) => request<{ message: string }>('initializeAdminPassword', {
    new_admin_password: next, new_admin_password_confirm: next,
  }, 'POST'),
};

function base64EncodeUtf8(value: string) {
  const bytes = new TextEncoder().encode(value);
  let binary = '';
  bytes.forEach((byte) => { binary += String.fromCharCode(byte); });
  return btoa(binary);
}

export function homeHref(value: string) {
  const trimmed = value.trim();
  if (/^[a-z][a-z0-9+.-]*:/i.test(trimmed) || trimmed.startsWith('/') || trimmed.startsWith('.')) return trimmed || './';
  return trimmed ? `https://${trimmed}` : './';
}

export function groupMaterials(items: MaterialItem[], parent: 'tag' | 'author') {
  const outer = new Map<string, { label: string; groups: Map<string, { label: string; items: MaterialItem[] }> }>();
  items.forEach((item) => {
    const outerKey = parent === 'tag' ? `tag:${item.primaryTagId}` : item.authorKey;
    const outerLabel = parent === 'tag' ? item.primaryTagName : item.authorName;
    const innerKey = parent === 'tag'
      ? `${item.authorKey}:sub:${item.subtagId ?? 0}`
      : `tag:${item.primaryTagId}:sub:${item.subtagId ?? 0}`;
    const innerLabel = parent === 'tag'
      ? [item.authorName, item.subtagName].filter(Boolean).join(' / ')
      : [item.primaryTagName, item.subtagName].filter(Boolean).join(' / ');
    if (!outer.has(outerKey)) outer.set(outerKey, { label: outerLabel, groups: new Map() });
    const group = outer.get(outerKey)!;
    if (!group.groups.has(innerKey)) group.groups.set(innerKey, { label: innerLabel, items: [] });
    group.groups.get(innerKey)!.items.push(item);
  });
  return [...outer.entries()].map(([key, value]) => ({
    key,
    label: value.label,
    groups: [...value.groups.entries()].map(([innerKey, group]) => ({ key: innerKey, ...group })),
  }));
}
