import { CSSProperties, FormEvent, ReactNode, useEffect, useMemo, useRef, useState } from 'react';
import { api, apiBase, defaultMaterialDesign, groupMaterials, homeHref, MaterialDesign, MaterialItem, MaterialSettings, MaterialTag, MaterialTerm, mediaUrl, packAuthorGroups, User } from './api';

type Page = 'list' | 'post' | 'delete' | 'edit' | 'manual' | 'login' | 'admin';
type AdminTab = 'items' | 'deleted' | 'maintenance' | 'analytics' | 'settings' | 'design';
const TOKEN_KEY = 'threadforgeMaterialsUserToken';
const ADMIN_KEY = 'threadforgeMaterialsAdminPassword';

function App() {
  const [page, setPage] = useState<Page>(() => pageFromHash());
  const [settings, setSettings] = useState<MaterialSettings | null>(null);
  const [tags, setTags] = useState<MaterialTag[]>([]);
  const [terms, setTerms] = useState<MaterialTerm[]>([]);
  const [items, setItems] = useState<MaterialItem[]>([]);
  const [token, setToken] = useState(() => localStorage.getItem(TOKEN_KEY) ?? '');
  const [user, setUser] = useState<User | null>(null);
  const [notice, setNotice] = useState('');
  const [error, setError] = useState('');
  const [previewDesign, setPreviewDesign] = useState<MaterialDesign | null>(null);

  const reload = async () => {
    const [settingsResponse, itemResponse] = await Promise.all([api.settings(), api.items()]);
    setSettings(settingsResponse.settings);
    setTags(settingsResponse.tags);
    setTerms(settingsResponse.terms);
    setItems(itemResponse.items);
  };

  useEffect(() => {
    reload().catch((reason) => setError((reason as Error).message));
    const handler = () => setPage(pageFromHash());
    window.addEventListener('hashchange', handler);
    return () => window.removeEventListener('hashchange', handler);
  }, []);

  useEffect(() => {
    if (!token) {
      setUser(null);
      return;
    }
    api.currentUser(token).then((response) => setUser(response.user)).catch(() => {
      localStorage.removeItem(TOKEN_KEY);
      setToken('');
    });
  }, [token]);

  const navigate = (next: Page) => {
    if (next !== 'admin') setPreviewDesign(null);
    window.location.hash = next === 'list' ? '#/' : `#/${next}`;
    setPage(next);
    window.scrollTo({ top: 0 });
  };

  if (!settings) return <main className="loading">素材庫を読み込んでいます...</main>;
  const visibleSettings = previewDesign ? { ...settings, design: previewDesign } : settings;
  const style = designVariables(visibleSettings);
  const common = { settings, tags, terms, items, token, user, setUser, setToken, reload, navigate, setNotice, setError, setPreviewDesign };

  return (
    <div className="materials-app" style={style}>
      <Header settings={settings} page={page} navigate={navigate} user={user} />
      <main className="page-shell">
        {notice && <p className="system-message success">{notice}</p>}
        {error && <p className="system-message error">{error}</p>}
        {page === 'list' && <LibraryPage {...common} />}
        {page === 'post' && <MaterialForm mode="create" {...common} />}
        {page === 'delete' && <SelectionPage mode="delete" {...common} />}
        {page === 'edit' && <SelectionPage mode="edit" {...common} />}
        {page === 'manual' && <ManualPage settings={settings} />}
        {page === 'login' && <LoginPage {...common} />}
        {page === 'admin' && <AdminPage {...common} />}
      </main>
      {(['list', 'delete', 'edit'] as Page[]).includes(page) &&
        <button className="back-to-top" type="button" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>上に戻る</button>}
    </div>
  );
}

function Header({ settings, page, navigate, user }: { settings: MaterialSettings; page: Page; navigate: (page: Page) => void; user: User | null }) {
  const tab = (target: Page, label: string) => (
    <button className={page === target ? 'active' : ''} onClick={() => navigate(target)}>{label}</button>
  );
  return (
    <header className="site-header">
      <nav aria-label="素材庫メニュー">
        <span className="decoration-square" aria-hidden="true">■</span>
        <a href={homeHref(settings.homePageUrl)}>HOME</a>
        {tab('list', '一覧')}
        {tab('post', '投稿')}
        {tab('delete', '削除')}
        {tab('edit', '編集')}
        {tab('manual', '取説')}
        {tab('login', user ? user.materials_author_name || user.display_name : 'ログイン')}
        <button className="admin-square" aria-label="管理画面" title="" onClick={() => navigate('admin')}>■</button>
      </nav>
    </header>
  );
}

type CommonProps = {
  settings: MaterialSettings; tags: MaterialTag[]; terms: MaterialTerm[]; items: MaterialItem[];
  token: string; user: User | null; setUser: (user: User | null) => void; setToken: (token: string) => void;
  reload: () => Promise<void>; navigate: (page: Page) => void; setNotice: (message: string) => void; setError: (message: string) => void;
  setPreviewDesign: (design: MaterialDesign | null) => void;
};

function LibraryPage({ settings, items }: CommonProps) {
  const groups = groupMaterials(items, settings.groupParent);
  return (
    <>
      <section className="library-heading">
        <h1>{settings.title}</h1>
        <p>{settings.description}</p>
      </section>
      <nav className="catalog-index" aria-label="素材目次">
        {groups.map((group) => <span className="catalog-index-group" key={group.key}>
          <a href={`#group-${slug(group.key)}`}>{group.label}</a>
          {group.groups.map((inner) => <a className="author-index-link" key={inner.key} href={`#author-${slug(group.key)}-${slug(inner.key)}`}>{inner.label}</a>)}
        </span>)}
      </nav>
      {groups.length === 0 && <Empty>登録された素材はありません。</Empty>}
      {settings.groupingParent === 'author'
        ? packAuthorGroups(groups.map((group) => ({
          ...group,
          items: group.groups.flatMap((inner) => inner.items),
        }))).map((row, rowIndex) => <div className="author-row author-parent-row" key={rowIndex}>
          {row.map((group) => renderCatalogGroup(group))}
        </div>)
        : groups.map((group) => (
          renderCatalogGroup(group)
        ))}
    </>
  );
}

function renderCatalogGroup(group: ReturnType<typeof groupMaterials>[number]) {
  return (
        <section className="catalog-section" id={`group-${slug(group.key)}`} key={group.key}>
          <h2>{group.label}</h2>
          <div className="author-masonry">
            {group.groups.map((inner) => (
              <div
                className="catalog-subsection"
                id={`author-${slug(group.key)}-${slug(inner.key)}`}
                key={inner.key}
                style={{ '--author-card-count': Math.min(inner.items.length, 4) } as CSSProperties}
              >
                <h3>{inner.label}</h3>
                <div className="material-grid">{inner.items.map((item) => <MaterialCard item={item} key={item.id} />)}</div>
              </div>
            ))}
          </div>
        </section>
  );
}

function MaterialCard({ item, selectable, selected, onSelect }: { item: MaterialItem; selectable?: boolean; selected?: boolean; onSelect?: () => void }) {
  return (
    <article className={`material-card ${selected ? 'selected' : ''} ${item.adminOnly ? 'admin-only' : ''}`}>
      {selectable && <input className="card-select" type="radio" checked={selected} disabled={item.adminOnly} onChange={onSelect} aria-label={`${item.name}を選択`} />}
      {selectable && item.adminOnly && <span className="admin-only-badge">管理画面のみ</span>}
      <div className="material-image">
        {item.imageUrl ? <img src={mediaUrl(item.imageUrl) ?? ''} alt={`${item.name}の説明画像`} loading="lazy" /> :
          (item.media ?? []).length ? <div className="audio-preview">{(item.media ?? []).map((media) => <label key={media.id} title={media.originalName}><span>{media.originalName}</span><audio controls preload="none" src={mediaUrl(media.url) ?? ''} /></label>)}</div> : <span>NO IMAGE</span>}
      </div>
      <div className="material-card-body">
        <h4 title={item.name}>{item.name}</h4>
        <a className="download-button" title={item.archiveOriginalName} href={mediaUrl(item.archiveUrl) ?? '#'} download>{item.archiveOriginalName}</a>
        <small>{formatBytes(item.archiveSizeBytes)} / {item.tagName}</small>
        <dl className="terms-table">
          {item.terms.map((term) => <div key={term.id}><dt>{term.label}</dt><dd className={term.accepted === null ? 'unknown' : term.accepted ? 'yes' : 'no'}>{term.accepted === null ? '?' : term.accepted ? '○' : '×'}</dd></div>)}
        </dl>
        {item.notes && <p className="notes">{item.notes}</p>}
      </div>
    </article>
  );
}

function MaterialForm(props: CommonProps & { mode: 'create' | 'edit'; item?: MaterialItem }) {
  const { mode, item, tags, terms, token, user, reload, navigate, setNotice, setError } = props;
  const [name, setName] = useState(item?.name ?? 'blank');
  const [author, setAuthor] = useState(item?.authorName ?? user?.materials_author_name ?? user?.display_name ?? '');
  const [notes, setNotes] = useState(item?.notes ?? '');
  const [tagId, setTagId] = useState(String(item?.tagId ?? tags[0]?.id ?? ''));
  const [password, setPassword] = useState(user?.post_password ?? '');
  const [archive, setArchive] = useState<File | null>(null);
  const [image, setImage] = useState<File | null>(null);
  const [audioFiles, setAudioFiles] = useState<File[]>([]);
  const [answers, setAnswers] = useState<Record<string, boolean | null>>(() => {
    if (item) return Object.fromEntries(item.terms.map((term) => [String(term.id), term.accepted]));
    return defaultTermAnswers(terms, user?.materials_default_terms);
  });
  const [busy, setBusy] = useState(false);
  const preview = useMemo(() => image ? URL.createObjectURL(image) : null, [image]);
  const selectedTag = tags.find((tag) => String(tag.id) === tagId);
  const isAudioTag = /音声|ボイス/.test(selectedTag?.name ?? '');
  useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true); setError(''); setNotice('');
    const body = new FormData();
    body.set('name', name); body.set('author_name', author); body.set('notes', notes); body.set('tag_id', tagId);
    body.set('password', password); body.set('terms', JSON.stringify(answers));
    if (archive) body.set('archive', archive);
    if (image) body.set('image', image);
    audioFiles.forEach((file) => body.append('audio[]', file));
    if (item) body.set('id', String(item.id));
    try {
      const response = mode === 'create' ? await api.create(body, token) : await api.update(body, token);
      await reload(); setNotice(response.message); navigate('list');
    } catch (reason) { setError((reason as Error).message); }
    finally { setBusy(false); }
  };

  return (
    <Panel title={mode === 'create' ? '素材を投稿' : `素材を編集: ${item?.name ?? ''}`}>
      <form className="material-form" onSubmit={submit}>
        <div className="form-grid two-columns">
          <label>名称<input value={name} onChange={(event) => setName(event.target.value)} required /></label>
          <label><span>作者名<span className="required-marker" aria-hidden="true">*</span></span><input value={author} onChange={(event) => setAuthor(event.target.value)} required /></label>
        </div>
        <label>タグ<select value={tagId} onChange={(event) => setTagId(event.target.value)} required>
          {tags.map((tag) => <option value={tag.id} key={tag.id}>{tag.name}</option>)}
        </select></label>
        <label>備考<textarea value={notes} onChange={(event) => setNotes(event.target.value)} /></label>
        <label><span>ファイル（{props.settings.allowedArchiveExtensions}）{mode === 'create' && <span className="required-marker" aria-hidden="true">*</span>}</span>
          <input type="file" required={mode === 'create'} accept={acceptExtensions(props.settings.allowedArchiveExtensions)} onChange={(event) => setArchive(event.target.files?.[0] ?? null)} />
        </label>
        {isAudioTag
          ? <label>試聴用MP3（複数選択可）<input type="file" accept="audio/mpeg,.mp3" multiple onChange={(event) => setAudioFiles(Array.from(event.target.files ?? []))} /></label>
          : <label>説明用画像<input type="file" accept="image/png,image/jpeg,image/gif,image/webp" onChange={(event) => setImage(event.target.files?.[0] ?? null)} /></label>}
        {!isAudioTag && (preview || item?.imageUrl) && <img className="form-preview" src={preview ?? mediaUrl(item?.imageUrl ?? null) ?? ''} alt="説明画像プレビュー" />}
        {isAudioTag && (item?.media ?? []).map((media) => <audio className="form-audio" key={media.id} controls src={mediaUrl(media.url) ?? ''} />)}
        <fieldset className="terms-editor"><legend>利用規約</legend>
          {terms.map((term) => <div className="term-choice" key={term.id}>
            <span><strong>{term.label}</strong><small>{term.description}</small></span>
            <label><input type="radio" name={`term-${term.id}`} checked={answers[String(term.id)] === true} onChange={() => setAnswers({ ...answers, [String(term.id)]: true })} />○</label>
            <label><input type="radio" name={`term-${term.id}`} checked={answers[String(term.id)] === false} onChange={() => setAnswers({ ...answers, [String(term.id)]: false })} />×</label>
          </div>)}
        </fieldset>
        <label><span>投稿パスワード<span className="required-marker" aria-hidden="true">*</span></span><input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
        <div className="actions"><button className="secondary" type="button" onClick={() => navigate('list')}>戻る</button><button disabled={busy}>{busy ? '送信中...' : mode === 'create' ? '投稿する' : '更新する'}</button></div>
      </form>
    </Panel>
  );
}

function SelectionPage(props: CommonProps & { mode: 'delete' | 'edit' }) {
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [password, setPassword] = useState(props.user?.post_password ?? '');
  const selected = props.items.find((item) => item.id === selectedId);
  const groups = groupMaterials(props.items, props.settings.groupParent);
  if (props.mode === 'edit' && selected) return <MaterialForm {...props} mode="edit" item={selected} />;
  const executeDelete = async () => {
    if (!selectedId || !confirm('選択した素材を削除しますか？')) return;
    try {
      const response = await api.remove(selectedId, password, props.token);
      await props.reload(); props.setNotice(response.message); setSelectedId(null);
    } catch (reason) { props.setError((reason as Error).message); }
  };
  return (
    <Panel title={props.mode === 'delete' ? '素材を削除' : '素材を編集'}>
      <p>対象を1件選択してください。ログイン投稿は本人のログイン中、ゲスト投稿は投稿パスワードで操作できます。パスワード未設定の投稿は管理画面からのみ変更できます。</p>
      <SelectionCatalog groups={groups} selectedId={selectedId} setSelectedId={setSelectedId} />
      {selected && <div className="selection-actions">
        {props.mode === 'delete' && <label>投稿パスワード<input type="password" value={password} onChange={(event) => setPassword(event.target.value)} /></label>}
        <button onClick={props.mode === 'delete' ? executeDelete : () => undefined}>{props.mode === 'delete' ? '削除する' : '編集画面へ'}</button>
      </div>}
    </Panel>
  );
}

function SelectionCatalog({
  groups,
  selectedId,
  setSelectedId,
}: {
  groups: ReturnType<typeof groupMaterials>;
  selectedId: number | null;
  setSelectedId: (id: number) => void;
}) {
  const scrollTo = (id: string) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  return <>
    <nav className="catalog-index selection-index" aria-label="対象素材の目次">
      {groups.map((group) => <span className="catalog-index-group" key={group.key}>
        <button type="button" onClick={() => scrollTo(`selection-group-${slug(group.key)}`)}>{group.label}</button>
        {group.groups.map((inner) => <button
          type="button"
          className="author-index-link"
          key={inner.key}
          onClick={() => scrollTo(`selection-author-${slug(group.key)}-${slug(inner.key)}`)}
        >{inner.label}</button>)}
      </span>)}
    </nav>
    {groups.map((group) => <section className="catalog-section" id={`selection-group-${slug(group.key)}`} key={group.key}>
      <h2>{group.label}</h2>
      <div className="author-masonry">
        {group.groups.map((inner) => <div
          className="catalog-subsection"
          id={`selection-author-${slug(group.key)}-${slug(inner.key)}`}
          key={inner.key}
          style={{ '--author-card-count': Math.min(inner.items.length, 4) } as CSSProperties}
        >
          <h3>{inner.label}</h3>
          <div className="material-grid">{inner.items.map((item) => <MaterialCard
            key={item.id}
            item={item}
            selectable
            selected={selectedId === item.id}
            onSelect={() => { if (!item.adminOnly) setSelectedId(item.id); }}
          />)}</div>
        </div>)}
      </div>
    </section>)}
  </>;
}

function ManualPage({ settings }: { settings: MaterialSettings }) {
  return <Panel title="取扱説明書"><div className="manual-body">{settings.manualBody.split('\n').map((line, index) => <p key={index}>{line || '\u00a0'}</p>)}</div></Panel>;
}

function LoginPage(props: CommonProps) {
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [loginId, setLoginId] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [authorName, setAuthorName] = useState(props.user?.materials_author_name ?? props.user?.display_name ?? '');
  const [postPassword, setPostPassword] = useState(props.user?.post_password ?? '');
  const [defaults, setDefaults] = useState<Record<string, boolean>>(() => defaultTermAnswers(props.terms, props.user?.materials_default_terms));
  const [icon, setIcon] = useState<File | null>(null);
  const ssoDone = useRef(false);

  useEffect(() => {
    if (!props.user) return;
    setAuthorName(props.user.materials_author_name ?? props.user.display_name);
    setPostPassword(props.user.post_password);
    setDefaults(defaultTermAnswers(props.terms, props.user.materials_default_terms));
  }, [props.user, props.terms]);

  useEffect(() => {
    if (ssoDone.current) return;
    const sso = new URLSearchParams(location.hash.split('?')[1] ?? location.search).get('sso');
    if (!sso) return;
    ssoDone.current = true;
    api.sso(sso).then((response) => completeLogin(response.token, response.user)).catch((reason) => props.setError((reason as Error).message));
  }, []);

  const completeLogin = (nextToken: string, nextUser: User) => {
    localStorage.setItem(TOKEN_KEY, nextToken); props.setToken(nextToken); props.setUser(nextUser);
    setAuthorName(nextUser.materials_author_name ?? nextUser.display_name); setPostPassword(nextUser.post_password);
    setDefaults(defaultTermAnswers(props.terms, nextUser.materials_default_terms)); props.setNotice('ログインしました。');
  };
  const submitAuth = async (event: FormEvent) => {
    event.preventDefault(); props.setError('');
    try {
      if (mode === 'login') {
        const response = await api.login(loginId, password); completeLogin(response.token, response.user);
      } else {
        if (password !== passwordConfirm) {
          props.setError('ログインパスワードと確認入力が一致しません。');
          return;
        }
        const body = new FormData();
        body.set('login_id', loginId); body.set('password', password); body.set('password_confirm', passwordConfirm);
        const response = await api.register(body); completeLogin(response.token, response.user);
      }
    } catch (reason) { props.setError((reason as Error).message); }
  };
  const saveProfile = async (event: FormEvent) => {
    event.preventDefault();
    const body = new FormData();
    body.set('author_name', authorName); body.set('post_password', postPassword);
    body.set('default_terms', JSON.stringify(defaults));
    if (icon) body.set('icon', icon);
    try {
      const response = await api.updateProfile(body, props.token); props.setUser(response.user); props.setNotice('素材庫プロフィールを保存しました。');
    } catch (reason) { props.setError((reason as Error).message); }
  };
  if (props.user) return (
    <Panel title="ユーザー設定">
      <div className="user-summary">{props.user.icon_path && <img src={mediaUrl(props.user.icon_path) ?? ''} alt="" />}<strong>{props.user.login_id}</strong>
        <button className="secondary" onClick={() => { api.logout(props.token).catch(() => undefined); localStorage.removeItem(TOKEN_KEY); props.setToken(''); props.setUser(null); }}>ログアウト</button>
      </div>
      <form className="material-form" onSubmit={saveProfile}>
        <label><span>作者名<span className="required-marker" aria-hidden="true">*</span></span><input value={authorName} onChange={(event) => setAuthorName(event.target.value)} required /></label>
        <label>投稿パスワード<input value={postPassword} onChange={(event) => setPostPassword(event.target.value)} /></label>
        <label>アイコン<input type="file" accept="image/png,image/jpeg,image/gif" onChange={(event) => setIcon(event.target.files?.[0] ?? null)} /></label>
        <fieldset className="terms-editor"><legend>利用規約の初期値</legend>{props.terms.map((term) => <div className="term-choice" key={term.id}>
          <span>{term.label}</span><label><input type="radio" checked={defaults[String(term.id)] === true} onChange={() => setDefaults({ ...defaults, [String(term.id)]: true })} />○</label>
          <label><input type="radio" checked={defaults[String(term.id)] === false} onChange={() => setDefaults({ ...defaults, [String(term.id)]: false })} />×</label>
        </div>)}</fieldset>
        <div className="actions"><button>保存する</button></div>
      </form>
    </Panel>
  );
  return (
    <Panel title={mode === 'login' ? 'ログイン' : 'ユーザー登録'}>
      <div className="segmented"><button className={mode === 'login' ? 'active' : ''} onClick={() => setMode('login')}>ログイン</button>
        {!props.settings.ssoEnabled && <button className={mode === 'register' ? 'active' : ''} onClick={() => setMode('register')}>新規登録</button>}</div>
      <form className="material-form" onSubmit={submitAuth}>
        <label><span>ログインID<span className="required-marker" aria-hidden="true">*</span></span><input value={loginId} onChange={(event) => setLoginId(event.target.value)} required pattern="[A-Za-z0-9_.-]{3,40}" /></label>
        <label><span>ログインパスワード<span className="required-marker" aria-hidden="true">*</span></span><input type={showPassword ? 'text' : 'password'} minLength={mode === 'register' ? 8 : undefined} value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
        {mode === 'register' && <label><span>ログインパスワード（確認）<span className="required-marker" aria-hidden="true">*</span></span><input type={showPassword ? 'text' : 'password'} minLength={8} value={passwordConfirm} onChange={(event) => setPasswordConfirm(event.target.value)} required /></label>}
        <label className="inline-check"><input type="checkbox" checked={showPassword} onChange={(event) => setShowPassword(event.target.checked)} />ログインパスワードを表示</label>
        <div className="actions"><button>{mode === 'login' ? 'ログイン' : '登録してログイン'}</button></div>
      </form>
      {props.settings.ssoEnabled && <p>親サイトから渡されたSSOトークンにも対応しています。</p>}
    </Panel>
  );
}

function AdminPage(props: CommonProps) {
  const [password, setPassword] = useState(() => localStorage.getItem(ADMIN_KEY) ?? '');
  const [authenticated, setAuthenticated] = useState(false);
  const [configured, setConfigured] = useState(true);
  const [tab, setTab] = useState<AdminTab>('items');
  const [selected, setSelected] = useState<number[]>([]);
  const [deleted, setDeleted] = useState<MaterialItem[]>([]);
  const [adminSettings, setAdminSettings] = useState<{ config: Record<string, unknown>; skin: Record<string, unknown> } | null>(null);
  const [savedAdminSettings, setSavedAdminSettings] = useState<{ config: Record<string, unknown>; skin: Record<string, unknown> } | null>(null);
  const [draftTags, setDraftTags] = useState(props.tags);
  const [draftTerms, setDraftTerms] = useState(props.terms);
  const [users, setUsers] = useState<Array<{ id: number; login_id: string; display_name: string }>>([]);
  const [analytics, setAnalytics] = useState<{ summary: Record<string, string>; months: Array<Record<string, string>> } | null>(null);
  const [nextAdminPassword, setNextAdminPassword] = useState('');
  const importRef = useRef<HTMLInputElement>(null);
  const settingsImportRef = useRef<HTMLInputElement>(null);
  const designImportRef = useRef<HTMLInputElement>(null);

  useEffect(() => { api.adminStatus().then((response) => setConfigured(response.adminPasswordConfigured)); }, []);
  const load = async (value = password) => {
    const [settingsResponse, deletedResponse, userResponse, analyticsResponse] = await Promise.all([
      api.getAdmin(value), api.deleted(value), api.users(value), api.analytics(value),
    ]);
    const settingsWithDesignDefaults = {
      ...settingsResponse.settings,
      skin: {
        ...settingsResponse.settings.skin,
        ...materialDesignSkin(materialDesignFromSkin(settingsResponse.settings.skin)),
      },
    };
    setAdminSettings(settingsWithDesignDefaults); setSavedAdminSettings(structuredClone(settingsWithDesignDefaults)); setDeleted(deletedResponse.items); setUsers(userResponse.users);
    setAnalytics(analyticsResponse); setAuthenticated(true); localStorage.setItem(ADMIN_KEY, value); props.setError('');
  };
  const authenticate = async (event: FormEvent) => {
    event.preventDefault();
    try {
      if (!configured) await api.initializeAdminPassword(password);
      await load(password);
    } catch (reason) { props.setError((reason as Error).message); }
  };
  if (!authenticated || !adminSettings) return <Panel title="管理"><form className="material-form" onSubmit={authenticate}>
    <label>{configured ? '管理パスワード' : '初期管理パスワード'}<input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
    <div className="actions"><button>{configured ? '管理画面を開く' : '設定して管理画面を開く'}</button></div>
  </form></Panel>;

  const toggle = (id: number) => setSelected((current) => current.includes(id) ? current.filter((value) => value !== id) : [...current, id]);
  const runAdmin = async (action: () => Promise<{ message: string }>) => {
    try { const response = await action(); props.setNotice(response.message); setSelected([]); await props.reload(); await load(); }
    catch (reason) { props.setError((reason as Error).message); }
  };
  const saveSettings = async () => {
    await runAdmin(() => api.updateSettings(password, adminSettings));
    setSavedAdminSettings(structuredClone(adminSettings));
    props.setPreviewDesign(null);
  };
  const exportAdminJson = async (section: 'settings' | 'design') => {
    const payload = section === 'settings'
      ? { format: 'threadforge-materials-library-settings', version: 1, config: adminSettings.config }
      : { format: 'threadforge-materials-library-design', version: 1, design: materialDesignFromSkin(adminSettings.skin) };
    try {
      await saveJsonWithPicker(
        payload,
        section === 'settings' ? 'materials-library-settings.json' : 'materials-library-design.json',
      );
      props.setNotice(`${section === 'settings' ? '設定' : 'デザイン'}JSONをエクスポートしました。`);
      props.setError('');
    } catch (reason) {
      if (!isAbortError(reason)) props.setError((reason as Error).message);
    }
  };
  const importAdminJson = async (section: 'settings' | 'design', file: File | null) => {
    if (!file) return;
    try {
      const payload = JSON.parse(await file.text()) as Record<string, unknown>;
      if (!isPlainObject(payload)) throw new Error('JSONの形式が正しくありません。');
      if (section === 'settings') {
        if (payload.format !== 'threadforge-materials-library-settings' || !isPlainObject(payload.config)) {
          throw new Error('materials-libraryの設定JSONではありません。');
        }
        setAdminSettings({ ...adminSettings, config: { ...adminSettings.config, ...payload.config } });
      } else {
        if (payload.format !== 'threadforge-materials-library-design' || !isPlainObject(payload.design)) {
          throw new Error('materials-libraryのデザインJSONではありません。');
        }
        const design = normalizeMaterialDesign(payload.design);
        setAdminSettings({ ...adminSettings, skin: { ...adminSettings.skin, ...materialDesignSkin(design) } });
        props.setPreviewDesign(design);
      }
      props.setNotice(`${section === 'settings' ? '設定' : 'デザイン'}JSONを読み込みました。保存するまではDBへ反映されません。`);
      props.setError('');
    } catch (reason) {
      props.setError((reason as Error).message);
    }
  };
  const config = adminSettings.config;
  const skin = adminSettings.skin;

  return (
    <>
      <Panel title="管理"><p>管理者としてログインしています。素材庫の投稿、保守、設定、デザインを管理できます。</p></Panel>
      <nav className="admin-tabs">{(['items', 'deleted', 'maintenance', 'analytics', 'settings', 'design'] as AdminTab[]).map((value) =>
        <button key={value} className={tab === value ? 'active' : ''} onClick={() => { setTab(value); setSelected([]); }}>{adminTabLabel(value)}</button>)}</nav>
      {tab === 'items' && <Panel title="一括削除・作者ID割当">
        <AdminItemRows items={props.items} selected={selected} toggle={toggle} users={users} onAssign={(item, userId, authorName) => runAdmin(() => api.assignAuthor(password, item.id, userId, authorName))} />
        <div className="actions"><button className="danger" disabled={!selected.length} onClick={() => runAdmin(() => api.adminDelete(password, selected))}>チェックした素材を一括削除</button></div>
      </Panel>}
      {tab === 'deleted' && <Panel title="復元 / 完全消去">
        <AdminItemRows items={deleted} selected={selected} toggle={toggle} />
        <div className="actions"><button disabled={!selected.length} onClick={() => runAdmin(() => api.restore(password, selected))}>復元する</button>
          <button className="danger" disabled={!selected.length} onClick={() => confirm('ファイルを含めて完全消去しますか？') && runAdmin(() => api.purge(password, selected))}>完全消去</button></div>
      </Panel>}
      {tab === 'maintenance' && <Panel title="保守・バックアップ">
        <p>データベースと素材ファイルをまとめてエクスポートできます。</p>
        <div className="actions"><a className="button-link" href={`${apiBase()}?action=exportBackup&admin_password=${encodeURIComponent(password)}`}>フルバックアップをダウンロード</a></div>
        <hr /><p>フルバックアップZipを選択して復元します。現在のデータは置き換わります。</p>
        <input ref={importRef} type="file" accept=".zip" />
        <div className="actions"><button onClick={() => importBackup(password, importRef.current?.files?.[0] ?? null, load, props.setNotice, props.setError)}>インポートして復元</button></div>
        <hr /><label>新しい管理パスワード<input type="password" value={nextAdminPassword} onChange={(event) => setNextAdminPassword(event.target.value)} /></label>
        <div className="actions"><button onClick={async () => {
          try {
            const response = await api.changeAdminPassword(password, nextAdminPassword);
            setPassword(nextAdminPassword);
            localStorage.setItem(ADMIN_KEY, nextAdminPassword);
            setNextAdminPassword('');
            props.setNotice(response.message);
            await load(nextAdminPassword);
          } catch (reason) {
            props.setError((reason as Error).message);
          }
        }}>管理パスワードを変更</button></div>
      </Panel>}
      {tab === 'analytics' && <Panel title="アナリティクス">{analytics && <><div className="stat-grid">
        <Stat label="公開素材" value={analytics.summary.total_items} /><Stat label="作者" value={analytics.summary.authors} />
        <Stat label="使用タグ" value={analytics.summary.used_tags} /><Stat label="総容量" value={formatBytes(Number(analytics.summary.total_bytes))} />
      </div><table className="analytics-table"><thead><tr><th>月</th><th>登録数</th><th>容量</th></tr></thead><tbody>{analytics.months.map((month) =>
        <tr key={month.month}><td>{month.month}</td><td>{month.count}</td><td>{formatBytes(Number(month.size_bytes))}</td></tr>)}</tbody></table></>}</Panel>}
      {tab === 'settings' && <><Panel title="素材庫設定">
        <div className="material-form">
          <label>タイトル<input value={String(config.materialsTitle ?? '')} onChange={(e) => setAdminSettings({ ...adminSettings, config: { ...config, materialsTitle: e.target.value } })} /></label>
          <label>説明<textarea value={String(config.materialsDescription ?? '')} onChange={(e) => setAdminSettings({ ...adminSettings, config: { ...config, materialsDescription: e.target.value } })} /></label>
          <label>HOMEリンク<input value={String(config.materialsHomePageUrl ?? '')} onChange={(e) => setAdminSettings({ ...adminSettings, config: { ...config, materialsHomePageUrl: e.target.value } })} /></label>
          <label>整理順<select value={String(config.materialsGroupParent ?? 'tag')} onChange={(e) => setAdminSettings({ ...adminSettings, config: { ...config, materialsGroupParent: e.target.value } })}><option value="tag">タグ → 作者</option><option value="author">作者 → タグ</option></select></label>
          <label>取扱説明書<textarea className="large" value={String(config.materialsManualBody ?? '')} onChange={(e) => setAdminSettings({ ...adminSettings, config: { ...config, materialsManualBody: e.target.value } })} /></label>
          <div className="form-grid two-columns"><label>素材上限 KB<input type="number" value={Number(config.materialsMaxArchiveKb ?? 102400)} onChange={(e) => setAdminSettings({ ...adminSettings, config: { ...config, materialsMaxArchiveKb: Number(e.target.value) } })} /></label>
            <label>画像上限 KB<input type="number" value={Number(config.materialsMaxImageKb ?? 10240)} onChange={(e) => setAdminSettings({ ...adminSettings, config: { ...config, materialsMaxImageKb: Number(e.target.value) } })} /></label></div>
          <label>許可する圧縮形式<input value={String(config.materialsAllowedArchiveExtensions ?? '')} onChange={(e) => setAdminSettings({ ...adminSettings, config: { ...config, materialsAllowedArchiveExtensions: e.target.value } })} /></label>
          <div className="actions"><button onClick={saveSettings}>設定を保存</button></div>
          <div className="admin-import-export">
            <strong>設定JSON</strong>
            <div className="actions">
              <button type="button" className="secondary" onClick={() => void exportAdminJson('settings')}>エクスポート</button>
              <button type="button" className="secondary" onClick={() => settingsImportRef.current?.click()}>インポート</button>
              <input ref={settingsImportRef} className="visually-hidden-file" type="file" accept="application/json,.json" onChange={(event) => {
                void importAdminJson('settings', event.currentTarget.files?.[0] ?? null);
                event.currentTarget.value = '';
              }} />
            </div>
          </div>
        </div>
      </Panel><CatalogEditor tags={draftTags} terms={draftTerms} setTags={setDraftTags} setTerms={setDraftTerms} save={async () => {
        try {
          const response = await api.saveCatalog(password, draftTags, draftTerms);
          const current = await api.settings();
          setDraftTags(current.tags);
          setDraftTerms(current.terms);
          await props.reload();
          props.setNotice(response.message);
        } catch (reason) {
          props.setError((reason as Error).message);
        }
      }} /></>}
      {tab === 'design' && <Panel title="デザイン">
        <p>変更は画面へ即時反映されます。保存ボタンを押すまではDBへ保存されません。</p>
        <div className="color-grid">{designFields.map(([key, label]) => <label key={key}>{label}<span className="color-input"><input type="color" value={String(skin[key] ?? '#000000')} onChange={(e) => updateMaterialDesign(key, e.target.value, adminSettings, setAdminSettings, props.setPreviewDesign)} /><input value={String(skin[key] ?? '')} onChange={(e) => updateMaterialDesign(key, e.target.value, adminSettings, setAdminSettings, props.setPreviewDesign)} /></span></label>)}</div>
        <div className="actions">
          <button className="secondary" onClick={() => {
            const next = { ...adminSettings, skin: { ...skin, ...materialDesignSkin(defaultMaterialDesign) } };
            setAdminSettings(next); props.setPreviewDesign(defaultMaterialDesign);
            props.setNotice('デザインをデフォルトに戻しました。保存するまでは確定しません。');
          }}>デフォルトに戻す</button>
          <button className="secondary" onClick={() => {
            if (!savedAdminSettings) return;
            const restored = structuredClone(savedAdminSettings);
            setAdminSettings(restored);
            props.setPreviewDesign(materialDesignFromSkin(restored.skin));
            props.setNotice('デザインを編集前の状態に戻しました。');
          }}>編集前に戻す</button>
          <button onClick={saveSettings}>デザインを保存</button>
        </div>
        <div className="admin-import-export">
          <strong>デザインJSON</strong>
          <div className="actions">
            <button type="button" className="secondary" onClick={() => void exportAdminJson('design')}>エクスポート</button>
            <button type="button" className="secondary" onClick={() => designImportRef.current?.click()}>インポート</button>
            <input ref={designImportRef} className="visually-hidden-file" type="file" accept="application/json,.json" onChange={(event) => {
              void importAdminJson('design', event.currentTarget.files?.[0] ?? null);
              event.currentTarget.value = '';
            }} />
          </div>
        </div>
      </Panel>}
    </>
  );
}

function AdminItemRows({ items, selected, toggle, users, onAssign }: { items: MaterialItem[]; selected: number[]; toggle: (id: number) => void; users?: Array<{ id: number; login_id: string; display_name: string }>; onAssign?: (item: MaterialItem, userId: string, authorName: string) => void }) {
  return <div className="admin-item-list">{items.map((item) => <div className="admin-item-row" key={item.id}>
    <input type="checkbox" checked={selected.includes(item.id)} onChange={() => toggle(item.id)} />
    <span>No.{item.id}</span><strong>{item.name}</strong><span>{item.authorName}</span><span>{item.tagName}</span>
    {users && onAssign && <AuthorAssignment item={item} users={users} save={onAssign} />}
  </div>)}</div>;
}

function AuthorAssignment({ item, users, save }: { item: MaterialItem; users: Array<{ id: number; login_id: string; display_name: string }>; save: (item: MaterialItem, userId: string, authorName: string) => void }) {
  const [userId, setUserId] = useState(item.userId ? String(item.userId) : '');
  const [name, setName] = useState(item.authorName);
  return <span className="author-assignment"><select value={userId} onChange={(e) => setUserId(e.target.value)}><option value="">IDなし</option>{users.map((user) => <option value={user.id} key={user.id}>{user.login_id}</option>)}</select>
    <input value={name} onChange={(e) => setName(e.target.value)} /><button onClick={() => save(item, userId, name)}>割当</button></span>;
}

function CatalogEditor({ tags, terms, setTags, setTerms, save }: { tags: MaterialTag[]; terms: MaterialTerm[]; setTags: (tags: MaterialTag[]) => void; setTerms: (terms: MaterialTerm[]) => void; save: () => void | Promise<void> }) {
  return <Panel title="タグ・利用規約">
    <h3>タグ</h3>{tags.map((tag, index) => <div className="catalog-edit-row" key={`${tag.id}-${index}`}><input value={tag.name} onChange={(e) => setTags(tags.map((value, i) => i === index ? { ...value, name: e.target.value } : value))} /><button className="secondary" onClick={() => setTags(tags.filter((_, i) => i !== index))}>削除</button></div>)}
    <button className="secondary" onClick={() => setTags([...tags, { id: 0, name: '', sortOrder: tags.length }])}>タグを追加</button>
    <h3>利用規約</h3>{terms.map((term, index) => <div className="catalog-edit-row term" key={`${term.id}-${index}`}><input value={term.label} onChange={(e) => setTerms(terms.map((value, i) => i === index ? { ...value, label: e.target.value } : value))} /><input value={term.description} onChange={(e) => setTerms(terms.map((value, i) => i === index ? { ...value, description: e.target.value } : value))} /><button className="secondary" onClick={() => setTerms(terms.filter((_, i) => i !== index))}>削除</button></div>)}
    <button className="secondary" onClick={() => setTerms([...terms, { id: 0, label: '', description: '', sortOrder: terms.length }])}>利用規約を追加</button>
    <div className="actions"><button onClick={save}>タグと利用規約を保存</button></div>
  </Panel>;
}

function Panel({ title, children }: { title: string; children: ReactNode }) { return <section className="panel"><h1>{title}</h1><div className="panel-body">{children}</div></section>; }
function Empty({ children }: { children: ReactNode }) { return <p className="empty">{children}</p>; }
function Stat({ label, value }: { label: string; value: string }) { return <div className="stat"><span>{label}</span><strong>{value}</strong></div>; }
function pageFromHash(): Page {
  const value = location.hash.replace(/^#\/?/, '').split('?')[0] as Page;
  return ['post', 'delete', 'edit', 'manual', 'login', 'admin'].includes(value) ? value : 'list';
}
function designVariables(settings: MaterialSettings): CSSProperties {
  return {
    '--page-bg': settings.design.pageBackgroundColor, '--page-text': settings.design.pageTextColor,
    '--header-bg': settings.design.headerBackgroundColor, '--header-text': settings.design.headerTextColor,
    '--header-border': settings.design.headerBorderColor,
    '--panel-bg': settings.design.panelBackgroundColor, '--panel-border': settings.design.panelBorderColor,
    '--heading-bg': settings.design.headingBackgroundColor, '--heading-text': settings.design.headingTextColor,
    '--accent': settings.design.accentColor,
    '--button-bg': settings.design.buttonBackgroundColor, '--button-text': settings.design.buttonTextColor,
    '--secondary-button-bg': settings.design.secondaryButtonBackgroundColor,
    '--secondary-button-text': settings.design.secondaryButtonTextColor,
    '--danger-button-bg': settings.design.dangerButtonBackgroundColor,
    '--danger-button-text': settings.design.dangerButtonTextColor,
    '--input-bg': settings.design.inputBackgroundColor, '--input-text': settings.design.inputTextColor,
    '--image-bg': settings.design.imageBackgroundColor, '--muted-text': settings.design.mutedTextColor,
    '--positive': settings.design.positiveColor, '--negative': settings.design.negativeColor,
    '--unknown': settings.design.unknownColor,
  } as CSSProperties;
}
function slug(value: string) { return encodeURIComponent(value).replace(/%/g, ''); }
function formatBytes(value: number) { if (value >= 1024 * 1024) return `${(value / 1024 / 1024).toFixed(1)}MB`; if (value >= 1024) return `${Math.ceil(value / 1024)}KB`; return `${value}bytes`; }
function acceptExtensions(value: string) { return value.split(/[\s,;]+/).filter(Boolean).map((extension) => `.${extension.replace(/^\./, '')}`).join(','); }
export function defaultTermAnswers(terms: MaterialTerm[], saved: Record<string, boolean> = {}) {
  return Object.fromEntries(terms.map((term) => [
    String(term.id),
    Object.prototype.hasOwnProperty.call(saved, String(term.id)) ? Boolean(saved[String(term.id)]) : true,
  ]));
}
function adminTabLabel(tab: AdminTab) { return ({ items: '一括削除', deleted: '復元 / 消去', maintenance: '保守', analytics: 'アナリティクス', settings: '設定', design: 'デザイン' })[tab]; }
const designFields: Array<[string, string]> = [
  ['materialsPageBackgroundColor', 'ページ背景'], ['materialsPageTextColor', '文字色'],
  ['materialsHeaderBackgroundColor', '上部メニュー背景'], ['materialsHeaderTextColor', '上部メニュー文字'],
  ['materialsHeaderBorderColor', '上部メニュー枠線'],
  ['materialsPanelBackgroundColor', 'パネル背景'], ['materialsPanelBorderColor', '枠線'],
  ['materialsHeadingBackgroundColor', '見出し背景'], ['materialsHeadingTextColor', '見出し文字'],
  ['materialsAccentColor', 'アクセント'],
  ['materialsButtonBackgroundColor', 'ボタン背景'], ['materialsButtonTextColor', 'ボタン文字'],
  ['materialsSecondaryButtonBackgroundColor', '補助ボタン背景'], ['materialsSecondaryButtonTextColor', '補助ボタン文字'],
  ['materialsDangerButtonBackgroundColor', '削除ボタン背景'], ['materialsDangerButtonTextColor', '削除ボタン文字'],
  ['materialsInputBackgroundColor', '入力欄背景'], ['materialsInputTextColor', '入力欄文字'],
  ['materialsImageBackgroundColor', '画像欄背景'], ['materialsMutedTextColor', '補助文字'],
  ['materialsPositiveColor', '利用規約 ○'], ['materialsNegativeColor', '利用規約 ×'],
  ['materialsUnknownColor', '利用規約 ？'],
];
function materialDesignSkin(design: MaterialDesign): Record<string, string> {
  return Object.fromEntries(Object.entries(design).map(([key, value]) => [`materials${key[0].toUpperCase()}${key.slice(1)}`, value]));
}
function materialDesignFromSkin(skin: Record<string, unknown>): MaterialDesign {
  return Object.fromEntries(Object.entries(defaultMaterialDesign).map(([key, fallback]) => [
    key,
    String(skin[`materials${key[0].toUpperCase()}${key.slice(1)}`] ?? fallback),
  ])) as MaterialDesign;
}
export function normalizeMaterialDesign(value: Record<string, unknown>): MaterialDesign {
  return Object.fromEntries(Object.entries(defaultMaterialDesign).map(([key, fallback]) => {
    const candidate = value[key];
    return [key, typeof candidate === 'string' && candidate.trim() !== '' ? candidate : fallback];
  })) as MaterialDesign;
}
export function isPlainObject(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}
async function saveJsonWithPicker(value: unknown, filename: string) {
  const blob = new Blob([JSON.stringify(value, null, 2)], { type: 'application/json' });
  const picker = (window as Window & { showSaveFilePicker?: (options: unknown) => Promise<any> }).showSaveFilePicker;
  if (typeof picker === 'function') {
    const handle = await picker({
      suggestedName: filename,
      types: [{
        description: 'JSON file',
        accept: { 'application/json': ['.json'] },
      }],
    });
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
function isAbortError(error: unknown) {
  return error instanceof Error && error.name === 'AbortError';
}
function updateMaterialDesign(
  key: string,
  value: string,
  settings: { config: Record<string, unknown>; skin: Record<string, unknown> },
  setSettings: (settings: { config: Record<string, unknown>; skin: Record<string, unknown> }) => void,
  setPreview: (design: MaterialDesign | null) => void,
) {
  const next = { ...settings, skin: { ...settings.skin, [key]: value } };
  setSettings(next);
  setPreview(materialDesignFromSkin(next.skin));
}
async function importBackup(password: string, file: File | null, reload: () => Promise<void>, notice: (message: string) => void, error: (message: string) => void) {
  if (!file) { error('バックアップZipを選択してください。'); return; }
  const body = new FormData(); body.set('action', 'importBackup'); body.set('admin_password', password); body.set('backup', file);
  try {
    const response = await fetch(apiBase(), { method: 'POST', body }); const data = await response.json();
    if (!response.ok || data.success === false) throw new Error(data.message);
    notice(data.message); await reload();
  } catch (reason) { error((reason as Error).message); }
}

export { acceptExtensions, formatBytes };
export default App;
