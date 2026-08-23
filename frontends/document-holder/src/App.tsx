import { CSSProperties, FormEvent, KeyboardEvent, useEffect, useRef, useState } from 'react';
import { api, defaultMaterialDesign, DescriptionBanner as Banner, groupMaterials, homeHref, MaterialDesign, MaterialItem, MaterialSettings, MaterialTag, MaterialTerm, User } from './api';

type Page = 'list' | 'article' | 'post' | 'delete' | 'edit' | 'manual' | 'login' | 'admin';
type DraftMode = 'write' | 'upload';
type Route = { page: Page; id?: number };
type AdminTab = 'posts' | 'deleted' | 'settings' | 'design';

const TOKEN_KEY = 'threadforgeDocumentHolderUserToken';
const DOCUMENT_HTML_PREFIX = '<!-- threadforge-document-html -->';
const DRAFT_KEY_PREFIX = 'threadforgeDocumentHolderDraft';

function App() {
  const [route, setRoute] = useState<Route>(() => routeFromHash());
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
    const handler = () => setRoute(routeFromHash());
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

  useEffect(() => {
    if (!settings || route.page !== 'article') return;
    const design = previewDesign ?? settings.design;
    const background = design.editorBackgroundColor || design.panelBackgroundColor || design.pageBackgroundColor;
    if (!background) return;

    const root = document.getElementById('root');
    const previousHtmlBackground = document.documentElement.style.background;
    const previousBodyBackground = document.body.style.background;
    const previousRootBackground = root?.style.background ?? '';

    document.documentElement.style.background = background;
    document.body.style.background = background;
    if (root) root.style.background = background;

    return () => {
      document.documentElement.style.background = previousHtmlBackground;
      document.body.style.background = previousBodyBackground;
      if (root) root.style.background = previousRootBackground;
    };
  }, [settings, previewDesign, route.page]);

  const navigate = (page: Page, id?: number) => {
    if (page !== 'admin') setPreviewDesign(null);
    window.location.hash = page === 'list' ? '#/' : page === 'article' ? `#/article/${id}` : `#/${page}`;
    setRoute({ page, id });
    window.scrollTo({ top: 0 });
  };

  if (!settings) {
    return (
      <main className="loading">
        {error ? (
          <div className="message error">
            {error}
          </div>
        ) : (
          'ドキュメントを読み込んでいます...'
        )}
      </main>
    );
  }

  const visibleSettings = previewDesign ? { ...settings, design: previewDesign } : settings;
  const common = { settings, tags, terms, items, token, user, setToken, setUser, reload, navigate, setNotice, setError, setPreviewDesign };
  const article = items.find((item) => item.id === route.id);

  return (
    <div className={`document-app ${route.page === 'article' ? 'document-app-reader' : ''}`} style={designVariables(visibleSettings)}>
      <Header settings={settings} page={route.page} navigate={navigate} user={user} />
      <main className={route.page === 'article' ? 'reader-shell' : 'page-shell'}>
        {notice && <p className="system-message success">{notice}</p>}
        {error && <p className="system-message error">{error}</p>}
        {route.page === 'list' && <DocumentList {...common} />}
        {route.page === 'article' && <ArticlePage item={article} navigate={navigate} />}
        {route.page === 'post' && <DocumentForm mode="create" {...common} />}
        {route.page === 'delete' && <SelectDocument mode="delete" {...common} />}
        {route.page === 'edit' && <SelectDocument mode="edit" {...common} />}
        {route.page === 'manual' && <ManualPage settings={settings} />}
        {route.page === 'login' && <LoginPage {...common} />}
        {route.page === 'admin' && <AdminPage {...common} />}
      </main>
      {(['list', 'delete', 'edit', 'article'] as Page[]).includes(route.page) &&
        <button className="back-to-top secondary" type="button" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>上に戻る</button>}
    </div>
  );
}

function Header({ settings, page, navigate, user }: { settings: MaterialSettings; page: Page; navigate: (page: Page, id?: number) => void; user: User | null }) {
  const tab = (target: Page, label: string) => (
    <button className={page === target ? 'active' : ''} type="button" onClick={() => navigate(target)}>{label}</button>
  );
  return (
    <header className="site-header">
      <nav aria-label="ドキュメントホルダーメニュー">
        <span className="decoration-square" aria-hidden="true">■</span>
        <a href={homeHref(settings.homePageUrl)}>HOME</a>
        {tab('list', '一覧')}
        {tab('post', '投稿')}
        {tab('delete', '削除')}
        {tab('edit', '編集')}
        {tab('manual', '取説')}
        {tab('login', user ? user.materials_author_name || user.display_name : 'ログイン')}
        <button className="admin-square" aria-label="管理画面" title="" type="button" onClick={() => navigate('admin')}>■</button>
      </nav>
    </header>
  );
}

type CommonProps = {
  settings: MaterialSettings;
  tags: MaterialTag[];
  terms: MaterialTerm[];
  items: MaterialItem[];
  token: string;
  user: User | null;
  setToken: (token: string) => void;
  setUser: (user: User | null) => void;
  reload: () => Promise<void>;
  navigate: (page: Page, id?: number) => void;
  setNotice: (message: string) => void;
  setError: (message: string) => void;
  setPreviewDesign: (design: MaterialDesign | null) => void;
};

function DocumentList({ settings, items, navigate, user }: CommonProps) {
  const defaultGroupParent = user?.materials_default_group_parent ?? settings.groupParent;
  const [groupParent, setGroupParent] = useState<'tag' | 'author'>(defaultGroupParent);
  useEffect(() => setGroupParent(defaultGroupParent), [defaultGroupParent]);
  const groups = groupMaterials(items, groupParent);
  const latest = [...items].sort((a, b) => b.updatedAt.localeCompare(a.updatedAt));

  return (
    <>
      <section className="panel hero-panel">
        <h1>{settings.title}</h1>
        <div className={`panel-body hero-description ${settings.descriptionBannerEnabled && bannerList(settings).length ? 'with-banner' : ''}`}>
          {settings.descriptionBannerEnabled && <DescriptionBanner settings={settings} />}
          <p>{settings.description}</p>
        </div>
      </section>
      <section className="panel updates-panel">
        <h2>更新履歴</h2>
        <div className="article-link-list scroll-list">
          {latest.length === 0 && <p>投稿された記事はありません。</p>}
          {latest.map((item) => <button className="article-link-row" type="button" key={item.id} onClick={() => navigate('article', item.id)}>
            <span className="article-link-title">{item.draft && <span className="draft-badge">＜書きかけ＞</span>}{item.name}</span>
            <span className="article-link-meta">{item.authorName} / {formatDate(item.updatedAt)} / 閲覧数: {item.viewCount}</span>
          </button>)}
        </div>
      </section>
      <section className="panel toc">
        <h2>目次</h2>
        <div className="toc-switcher" role="group" aria-label="目次の並び">
          <button type="button" className={groupParent === 'tag' ? '' : 'secondary'} onClick={() => setGroupParent('tag')}>タグを親にする</button>
          <button type="button" className={groupParent === 'author' ? '' : 'secondary'} onClick={() => setGroupParent('author')}>投稿者名を親にする</button>
        </div>
        <div className={`toc-body document-toc ${groupParent === 'tag' ? 'toc-parent-tag' : 'toc-parent-author'}`}>
          {groups.length === 0 && <p>投稿された記事はありません。</p>}
          {groups.map((group) => <div className="toc-group" key={group.key}>
            <h3><span>{group.label}</span><small>{group.groups.reduce((sum, inner) => sum + inner.items.length, 0)}件</small></h3>
            {group.groups.map((inner) => <div className="toc-author" key={inner.key}>
              <h4>{inner.label}</h4>
              <ul>
                {inner.items.map((item) => <li key={item.id}>
                  <button type="button" onClick={() => navigate('article', item.id)}>
                    <span>
                      {groupParent === 'tag' && <strong className="toc-item-author">{item.authorName}</strong>}
                      {item.draft && <span className="draft-badge">＜書きかけ＞</span>}
                      {item.name}
                    </span>
                    <small>{formatDate(item.updatedAt)}</small>
                  </button>
                </li>)}
              </ul>
            </div>)}
          </div>)}
        </div>
      </section>
    </>
  );
}

function bannerList(settings: MaterialSettings): Banner[] {
  const banners = settings.descriptionBanners?.length
    ? settings.descriptionBanners
    : settings.descriptionBannerImageUrl
      ? [{ imageUrl: settings.descriptionBannerImageUrl, linkUrl: settings.descriptionBannerLinkUrl, alt: settings.descriptionBannerAlt }]
      : [];
  return banners.filter((banner) => banner.imageUrl);
}

function DescriptionBanner({ settings }: { settings: MaterialSettings }) {
  const banners = bannerList(settings);
  const [index, setIndex] = useState(0);
  useEffect(() => {
    if (banners.length < 2) return undefined;
    const timer = window.setInterval(() => setIndex((current) => (current + 1) % banners.length), settings.descriptionBannerIntervalMs || 5000);
    return () => window.clearInterval(timer);
  }, [banners.length, settings.descriptionBannerIntervalMs]);
  if (banners.length === 0) return null;
  return (
    <div className="description-banner carousel">
      {banners.map((banner, bannerIndex) => {
        const image = <img src={banner.imageUrl} alt={banner.alt || settings.descriptionBannerAlt} />;
        return (
          <div className={`description-banner-slide ${bannerIndex === index ? 'active' : ''}`} key={`${banner.imageUrl}-${bannerIndex}`}>
            {banner.linkUrl ? <a href={banner.linkUrl}>{image}</a> : image}
          </div>
        );
      })}
    </div>
  );
}

function ArticlePage({ item, navigate }: { item?: MaterialItem; navigate: (page: Page, id?: number) => void }) {
  const [viewCount, setViewCount] = useState(item?.viewCount ?? 0);
  useEffect(() => {
    if (!item) return;
    setViewCount(item.viewCount);
    api.recordView(item.id).then((response) => setViewCount(response.view_count)).catch(() => undefined);
  }, [item?.id]);
  if (!item) return <Panel title="記事が見つかりません"><p>指定された記事は存在しません。</p></Panel>;
  return (
    <>
      <article className="panel article-reader">
        <h1>{item.draft && <span className="draft-badge large">＜書きかけ＞</span>}{item.name}</h1>
        <div className="panel-body">
          <p className="article-meta">投稿者: {item.authorName} / 投稿日: {formatDate(item.createdAt)} / 更新日: {formatDate(item.updatedAt)} / 閲覧数: {viewCount}</p>
          <div className="reader-content" dangerouslySetInnerHTML={{ __html: documentHtmlFromItem(item) }} />
        </div>
      </article>
      <div className="actions"><button className="secondary" type="button" onClick={() => navigate('list')}>一覧に戻る</button></div>
    </>
  );
}

function DocumentForm(props: CommonProps & { mode: 'create' | 'edit'; item?: MaterialItem; initialPassword?: string }) {
  const { mode, item, tags, token, user, reload, navigate, setNotice, setError } = props;
  const editorRef = useRef<HTMLDivElement | null>(null);
  const fileRef = useRef<HTMLInputElement | null>(null);
  const imageRef = useRef<HTMLInputElement | null>(null);
  const draftKey = item ? `${DRAFT_KEY_PREFIX}:edit:${item.id}` : `${DRAFT_KEY_PREFIX}:create`;
  const primaryTags = tags.filter((tag) => tag.parentId === null);
  const initialPrimaryId = item?.primaryTagId ?? primaryTags[0]?.id ?? tags[0]?.id;
  const [name, setName] = useState(item?.name ?? 'blank');
  const [author, setAuthor] = useState(item?.authorName ?? user?.materials_author_name ?? user?.display_name ?? '');
  const [tagId, setTagId] = useState(String(initialPrimaryId ?? ''));
  const [subtagInput, setSubtagInput] = useState(item?.subtagName ?? '');
  const [draftFlag, setDraftFlag] = useState(Boolean(item?.draft));
  const [password, setPassword] = useState(mode === 'edit' ? props.initialPassword ?? '' : user?.post_password ?? '');
  const [draftMode, setDraftMode] = useState<DraftMode>('write');
  const [textColor, setTextColor] = useState('#ffffff');
  const [tableBg, setTableBg] = useState('#202833');
  const [archive, setArchive] = useState<File | null>(null);
  const [status, setStatus] = useState(() => localStorage.getItem(draftKey) ? '保存済みの下書きがあります。' : '30秒ごとに自動保存します。');
  const [busy, setBusy] = useState(false);
  const childTags = tags.filter((tag) => tag.parentId === Number(tagId));

  useEffect(() => {
    if (mode === 'edit') setPassword(props.initialPassword ?? '');
  }, [mode, props.initialPassword, item?.id]);

  useEffect(() => {
    if (!editorRef.current) return;
    editorRef.current.innerHTML = item ? documentHtmlFromItem(item) : '<p>本文を書いてください。</p>';
  }, [item?.id]);

  const editorHtml = () => editorRef.current?.innerHTML ?? '';
  const writeEditor = (html: string) => {
    if (editorRef.current) editorRef.current.innerHTML = html;
  };
  const exec = (command: string, value?: string) => {
    editorRef.current?.focus();
    document.execCommand(command, false, value);
  };
  const saveDraft = (silent = false) => {
    const draft = { name, author, tagId, subtagInput, draftFlag, ...(mode === 'create' ? { password } : {}), draftMode, editorHtml: editorHtml(), textColor, tableBg, savedAt: new Date().toISOString() };
    localStorage.setItem(draftKey, JSON.stringify(draft));
    if (!silent) setStatus(`下書きを保存しました。${formatDate(draft.savedAt)}`);
  };
  const loadDraft = () => {
    const raw = localStorage.getItem(draftKey);
    if (!raw) {
      setStatus('読み込める下書きはありません。');
      return;
    }
    const draft = JSON.parse(raw);
    setName(draft.name ?? name);
    setAuthor(draft.author ?? author);
    setTagId(draft.tagId ?? tagId);
    setSubtagInput(draft.subtagInput ?? draft.newSubtagName ?? '');
    setDraftFlag(Boolean(draft.draftFlag));
    setPassword(mode === 'edit' ? props.initialPassword ?? password : draft.password ?? password);
    setDraftMode(draft.draftMode ?? 'write');
    setTextColor(draft.textColor ?? textColor);
    setTableBg(draft.tableBg ?? tableBg);
    requestAnimationFrame(() => writeEditor(draft.editorHtml ?? ''));
    setStatus(`下書きを読み込みました。${formatDate(draft.savedAt)}`);
  };
  const discardDraft = () => {
    localStorage.removeItem(draftKey);
    setStatus('一時保存を破棄しました。');
  };

  useEffect(() => {
    if (draftMode !== 'write') return;
    const timer = window.setInterval(() => saveDraft(true), 30000);
    return () => window.clearInterval(timer);
  }, [name, author, tagId, subtagInput, draftFlag, password, draftMode, textColor, tableBg]);

  const insertLink = () => {
    const url = window.prompt('リンク先URL');
    if (url) exec('createLink', url);
  };
  const insertTable = () => {
    exec('insertHTML', `<table style="background:${escapeAttribute(tableBg)}"><tbody><tr><th>項目</th><th>内容</th></tr><tr><td></td><td></td></tr></tbody></table><p><br></p>`);
  };
  const insertImage = async () => {
    const file = imageRef.current?.files?.[0];
    if (!file) return;
    const src = await readFileAsDataUrl(file);
    exec('insertHTML', `<p><img src="${escapeAttribute(src)}" alt="${escapeAttribute(file.name)}"></p>`);
    if (imageRef.current) imageRef.current.value = '';
  };
  const keepHeadingFromEatingText = (event: KeyboardEvent<HTMLDivElement>) => {
    if (event.key !== 'Enter') return;
    const node = window.getSelection()?.anchorNode;
    const element = node instanceof Element ? node : node?.parentElement;
    if (!element?.closest('h1,h2,h3')) return;
    event.preventDefault();
    document.execCommand('insertHTML', false, '<p><br></p>');
  };

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError('');
    setNotice('');
    try {
      const body = new FormData();
      body.set('name', name);
      body.set('author_name', author);
      body.set('tag_id', tagId);
      const subtagValue = subtagInput.trim();
      const selectedSubtag = subtagValue ? childTags.find((tag) => tag.name === subtagValue) : null;
      if (selectedSubtag) body.set('subtag_id', String(selectedSubtag.id));
      else if (subtagValue) body.set('new_subtag_name', subtagValue);
      body.set('draft', draftFlag ? '1' : '0');
      body.set('password', password);
      body.set('terms', JSON.stringify({}));
      if (item) body.set('id', String(item.id));

      if (draftMode === 'write') {
        const html = normalizeEditorHtml(editorHtml());
        body.set('notes', DOCUMENT_HTML_PREFIX + html);
        body.set('archive', fileFromBytes(`${safeFileName(name)}.zip`, makeZip([{ path: 'index.html', data: textBytes(htmlDocument(name, html)) }])));
      } else {
        const file = archive ?? fileRef.current?.files?.[0] ?? null;
        if (!file && mode === 'create') throw new Error('index.html または zip ファイルを選択してください。');
        body.set('notes', file ? await htmlFromUpload(file) : DOCUMENT_HTML_PREFIX + documentHtmlFromItem(item!));
        if (file) body.set('archive', file);
      }

      const response = mode === 'create' ? await api.create(body, token) : await api.update(body, token);
      localStorage.removeItem(draftKey);
      await reload();
      setNotice(response.message);
      navigate('list');
    } catch (reason) {
      setError((reason as Error).message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Panel title={mode === 'create' ? 'ドキュメントを投稿' : `ドキュメントを編集: ${item?.name ?? ''}`}>
      <form className="material-form document-form" onSubmit={submit}>
        <div className="form-grid two-columns">
          <label><span>名称<span className="required-marker" aria-hidden="true">*</span></span><input value={name} onChange={(event) => setName(event.target.value)} required /></label>
          <label><span>作者名<span className="required-marker" aria-hidden="true">*</span></span><input value={author} onChange={(event) => setAuthor(event.target.value)} required /></label>
        </div>
        <div className="form-grid two-columns">
          <label><span>第一階層タグ<span className="required-marker" aria-hidden="true">*</span></span>
            <select value={tagId} onChange={(event) => { setTagId(event.target.value); setSubtagInput(''); }} required>
              {primaryTags.map((tag) => <option value={tag.id} key={tag.id}>{tag.name}</option>)}
            </select>
          </label>
          <label><span>第二階層タグ</span>
            <div className="subtag-combo">
              <select
                value={childTags.some((tag) => tag.name === subtagInput) ? subtagInput : ''}
                onChange={(event) => setSubtagInput(event.target.value)}
              >
                <option value="">未指定</option>
                {childTags.map((tag) => <option value={tag.name} key={tag.id}>{tag.name}</option>)}
              </select>
              <input
                value={childTags.some((tag) => tag.name === subtagInput) ? '' : subtagInput}
                onChange={(event) => setSubtagInput(event.target.value)}
                placeholder="新しい第二階層タグ"
              />
            </div>
          </label>
        </div>
        <label className="inline-check"><input type="checkbox" checked={draftFlag} onChange={(event) => setDraftFlag(event.target.checked)} />書きかけとして表示する</label>
        <fieldset className="post-mode">
          <legend>投稿方法</legend>
          <label><input type="radio" checked={draftMode === 'write'} onChange={() => setDraftMode('write')} />その場で書く</label>
          <label><input type="radio" checked={draftMode === 'upload'} onChange={() => setDraftMode('upload')} />index.html または zip を投稿</label>
        </fieldset>
        {draftMode === 'upload' ? (
          <label><span>ファイル{mode === 'create' && <span className="required-marker" aria-hidden="true">*</span>}</span>
            <input ref={fileRef} type="file" accept=".html,.htm,.zip" required={mode === 'create'} onChange={(event) => setArchive(event.target.files?.[0] ?? null)} />
          </label>
        ) : (
          <>
            <div className="rich-toolbar" aria-label="本文装飾">
              <button type="button" className="secondary" onClick={() => exec('formatBlock', '<p>')}>本文</button>
              <button type="button" className="secondary" onClick={() => exec('formatBlock', '<h2>')}>見出し</button>
              <button type="button" className="secondary" onClick={() => exec('bold')}>太字</button>
              <button type="button" className="secondary" onClick={insertLink}>リンク</button>
              <button type="button" className="secondary" onClick={insertTable}>表</button>
              <label>文字色<input type="color" value={textColor} onChange={(event) => { setTextColor(event.target.value); exec('foreColor', event.target.value); }} /></label>
              <label>表背景<input type="color" value={tableBg} onChange={(event) => setTableBg(event.target.value)} /></label>
              <input ref={imageRef} type="file" accept="image/*" hidden onChange={() => void insertImage()} />
              <button type="button" className="secondary" onClick={() => imageRef.current?.click()}>画像</button>
            </div>
            <label>本文</label>
            <div
              ref={editorRef}
              className="rich-editor"
              contentEditable
              data-placeholder="本文を書いてください。"
              suppressContentEditableWarning
              onKeyDown={keepHeadingFromEatingText}
            />
          </>
        )}
        <label><span>投稿パスワード<span className="required-marker" aria-hidden="true">*</span></span><input type="password" value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="new-password" required /></label>
        <p className="draft-status">{status}</p>
        <div className="draft-actions">
          <button type="button" className="secondary" onClick={() => saveDraft(false)}>一時保存</button>
          <button type="button" className="secondary" onClick={loadDraft}>下書きを読込</button>
          <button type="button" className="secondary" onClick={discardDraft}>一時保存を破棄</button>
        </div>
        <div className="actions">
          <button className="secondary" type="button" onClick={() => navigate('list')}>戻る</button>
          <button disabled={busy}>{busy ? '送信中...' : mode === 'create' ? '投稿する' : '更新する'}</button>
        </div>
      </form>
    </Panel>
  );
}

function SelectDocument(props: CommonProps & { mode: 'delete' | 'edit' }) {
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [password, setPassword] = useState(props.user?.post_password ?? '');
  const [editing, setEditing] = useState(false);
  const [checkingPassword, setCheckingPassword] = useState(false);
  const selected = props.items.find((item) => item.id === selectedId);
  const groups = groupMaterials(props.items, props.settings.groupParent);

  if (props.mode === 'edit' && selected && editing) {
    return <DocumentForm {...props} mode="edit" item={selected} initialPassword={password} />;
  }

  const executeDelete = async () => {
    if (!selectedId) return;
    if (!password.trim()) {
      props.setError('投稿パスワードを入力してください。');
      return;
    }
    if (!confirm('選択したドキュメントを削除しますか？')) return;
    try {
      const response = await api.remove(selectedId, password, props.token);
      await props.reload();
      props.setNotice(response.message);
      setSelectedId(null);
    } catch (reason) {
      props.setError((reason as Error).message);
    }
  };

  const openEdit = async () => {
    if (!selectedId) return;
    if (!password.trim()) {
      props.setError('投稿パスワードを入力してください。');
      return;
    }
    setCheckingPassword(true);
    try {
      await api.verifyPassword(selectedId, password, props.token);
      props.setError('');
      setEditing(true);
    } catch (reason) {
      props.setError((reason as Error).message);
    } finally {
      setCheckingPassword(false);
    }
  };

  return (
    <Panel title={props.mode === 'delete' ? 'ドキュメントを削除' : 'ドキュメントを編集'}>
      <p>対象を1件選び、投稿時に設定した投稿パスワードを入力してください。</p>
      <ArticleSelectList groups={groups} selectedId={selectedId} setSelectedId={(id) => { setSelectedId(id); setEditing(false); }} />
      {selected && <div className="selection-actions">
        <label>投稿パスワード<input type="password" value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="new-password" required /></label>
        <button type="button" disabled={checkingPassword} onClick={props.mode === 'delete' ? executeDelete : openEdit}>{props.mode === 'delete' ? '削除する' : checkingPassword ? '確認中...' : '編集画面へ'}</button>
      </div>}
    </Panel>
  );
}

function ArticleSelectList({ groups, selectedId, setSelectedId }: { groups: ReturnType<typeof groupMaterials>; selectedId: number | null; setSelectedId: (id: number) => void }) {
  if (groups.length === 0) return <p>対象のドキュメントはありません。</p>;
  return (
    <div className="article-selection-list">
      {groups.flatMap((group) => group.groups.flatMap((inner) => inner.items.map((item) => (
        <label className={`article-selection-row ${selectedId === item.id ? 'selected' : ''} ${item.adminOnly ? 'admin-only' : ''}`} key={item.id}>
          <input type="radio" name="article-selection" checked={selectedId === item.id} disabled={item.adminOnly} onChange={() => setSelectedId(item.id)} />
          <span className="article-selection-title">{item.name}</span>
          <span className="article-selection-meta">{item.authorName}</span>
          <span className="article-selection-meta">{item.primaryTagName}{item.subtagName ? ` / ${item.subtagName}` : ''}</span>
          <span className="article-selection-meta">{formatDate(item.updatedAt)}</span>
          <span className="article-selection-preview">{plainTextPreview(item.notes)}</span>
          {item.adminOnly && <span className="admin-only-badge-inline">管理画面のみ</span>}
        </label>
      ))))}
    </div>
  );
}

function ManualPage({ settings }: { settings: MaterialSettings }) {
  return <Panel title="取説"><div className="manual-body">{settings.manualBody.split('\n').map((line, index) => <p key={index}>{line || '\u00a0'}</p>)}</div></Panel>;
}

function LoginPage(props: CommonProps) {
  const [loginId, setLoginId] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [profileAuthor, setProfileAuthor] = useState(props.user?.materials_author_name ?? props.user?.display_name ?? '');
  const [profilePostPassword, setProfilePostPassword] = useState(props.user?.post_password ?? '');
  const [profileGroupParent, setProfileGroupParent] = useState<'site' | 'tag' | 'author'>(props.user?.materials_default_group_parent ?? 'site');

  useEffect(() => {
    setProfileAuthor(props.user?.materials_author_name ?? props.user?.display_name ?? '');
    setProfilePostPassword(props.user?.post_password ?? '');
    setProfileGroupParent(props.user?.materials_default_group_parent ?? 'site');
  }, [props.user]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    props.setError('');
    try {
      const response = await api.login(loginId, password);
      localStorage.setItem(TOKEN_KEY, response.token);
      props.setToken(response.token);
      props.setUser(response.user);
      props.setNotice('ログインしました。');
    } catch (reason) {
      props.setError((reason as Error).message);
    }
  };

  const saveProfile = async (event: FormEvent) => {
    event.preventDefault();
    props.setError('');
    props.setNotice('');
    try {
      const body = new FormData();
      body.set('author_name', profileAuthor);
      body.set('post_password', profilePostPassword);
      body.set('default_terms', JSON.stringify(props.user?.materials_default_terms ?? {}));
      body.set('default_group_parent', profileGroupParent === 'site' ? '' : profileGroupParent);
      const response = await api.updateMaterialProfile(body, props.token);
      props.setUser(response.user);
      props.setNotice('ユーザー設定を保存しました。');
    } catch (reason) {
      props.setError((reason as Error).message);
    }
  };

  if (props.user) {
    const ownItems = props.items.filter((item) => item.userId === props.user?.id || item.authorName === (props.user?.materials_author_name ?? props.user?.display_name));
    const totalViews = ownItems.reduce((sum, item) => sum + item.viewCount, 0);
    return <Panel title="ログイン中">
      <p>{props.user.materials_author_name || props.user.display_name} としてログインしています。</p>
      <section className="analytics-box">
        <h2>アナリティクス</h2>
        <p>投稿数: {ownItems.length} / 閲覧数合計: {totalViews}</p>
      </section>
      <form className="material-form user-profile-form" onSubmit={saveProfile}>
        <label>作者名<input value={profileAuthor} onChange={(event) => setProfileAuthor(event.target.value)} required /></label>
        <label>投稿パスワード<input value={profilePostPassword} onChange={(event) => setProfilePostPassword(event.target.value)} /></label>
        <label>目次の初期並び
          <select value={profileGroupParent} onChange={(event) => setProfileGroupParent(event.target.value as 'site' | 'tag' | 'author')}>
            <option value="site">サイト設定に従う</option>
            <option value="tag">タグを親にする</option>
            <option value="author">投稿者名を親にする</option>
          </select>
        </label>
        <div className="actions"><button type="submit">ユーザー設定を保存</button></div>
      </form>
      <button className="secondary" type="button" onClick={() => {
        api.logout(props.token).catch(() => undefined);
        localStorage.removeItem(TOKEN_KEY);
        props.setToken('');
        props.setUser(null);
      }}>ログアウト</button>
    </Panel>;
  }

  return (
    <Panel title="ログイン">
      <form className="material-form" onSubmit={submit}>
        <label><span>ログインID<span className="required-marker" aria-hidden="true">*</span></span><input value={loginId} onChange={(event) => setLoginId(event.target.value)} required /></label>
        <label><span>ログインパスワード<span className="required-marker" aria-hidden="true">*</span></span><input type={showPassword ? 'text' : 'password'} value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
        <label className="inline-check"><input type="checkbox" checked={showPassword} onChange={(event) => setShowPassword(event.target.checked)} />ログインパスワードを表示</label>
        <div className="actions"><button>ログイン</button></div>
      </form>
    </Panel>
  );
}

function AdminPage(props: CommonProps) {
  const [password, setPassword] = useState('');
  const [configured, setConfigured] = useState(true);
  const [authenticated, setAuthenticated] = useState(false);
  const [adminSettings, setAdminSettings] = useState<{ config: Record<string, unknown>; skin: Record<string, unknown> } | null>(null);
  const [savedSettings, setSavedSettings] = useState<{ config: Record<string, unknown>; skin: Record<string, unknown> } | null>(null);
  const [localTags, setLocalTags] = useState<MaterialTag[]>(props.tags);
  const [analytics, setAnalytics] = useState<Record<string, number> | null>(null);
  const [tab, setTab] = useState<AdminTab>('posts');
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [deletedItems, setDeletedItems] = useState<MaterialItem[]>([]);

  useEffect(() => {
    api.adminStatus().then((response) => setConfigured(response.adminPasswordConfigured)).catch((reason) => props.setError((reason as Error).message));
  }, []);
  useEffect(() => setLocalTags(props.tags), [props.tags]);

  const load = async (value = password) => {
    const [settingsResponse, analyticsResponse, deletedResponse] = await Promise.all([api.getAdmin(value), api.materialAnalytics(value), api.deletedItems(value)]);
    const next = {
      ...settingsResponse.settings,
      config: {
        materialsTitle: props.settings.title,
        materialsDescription: props.settings.description,
        materialsDescriptionBannerEnabled: props.settings.descriptionBannerEnabled,
        materialsDescriptionBanners: props.settings.descriptionBanners,
        materialsDescriptionBannerIntervalMs: props.settings.descriptionBannerIntervalMs,
        ...settingsResponse.settings.config,
      },
      skin: {
        ...settingsResponse.settings.skin,
        ...materialDesignSkin(materialDesignFromSkin(settingsResponse.settings.skin)),
      },
    };
    setAdminSettings(next);
    setSavedSettings(structuredClone(next));
    setAnalytics(analyticsResponse.summary);
    setDeletedItems(deletedResponse.items);
    setAuthenticated(true);
    props.setPreviewDesign(materialDesignFromSkin(next.skin));
    props.setError('');
  };

  const authenticate = async (event: FormEvent) => {
    event.preventDefault();
    try {
      if (!configured) await api.initializeAdminPassword(password);
      await load(password);
    } catch (reason) {
      props.setError((reason as Error).message);
    }
  };

  if (!authenticated || !adminSettings) {
    return (
      <Panel title="管理">
        <form className="material-form" onSubmit={authenticate}>
          <label>{configured ? '管理パスワード' : '初期管理パスワード'}<input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
          <div className="actions"><button>管理画面を開く</button></div>
        </form>
      </Panel>
    );
  }

  const skin = adminSettings.skin;
  const config = adminSettings.config;
  const updateConfig = (key: string, value: unknown) => setAdminSettings({ ...adminSettings, config: { ...config, [key]: value } });
  const updateDesign = (key: keyof MaterialDesign, value: string) => {
    const nextDesign = { ...materialDesignFromSkin(skin), [key]: value };
    const next = { ...adminSettings, skin: { ...skin, ...materialDesignSkin(nextDesign) } };
    setAdminSettings(next);
    props.setPreviewDesign(nextDesign);
  };
  const save = async () => {
    try {
      const response = await api.updateSettings(password, adminSettings);
      await api.saveCatalog(password, localTags, props.terms);
      props.setNotice(response.message);
      setSavedSettings(structuredClone(adminSettings));
      await props.reload();
    } catch (reason) {
      props.setError((reason as Error).message);
    }
  };
  const restoreSaved = () => {
    if (!savedSettings) return;
    setAdminSettings(structuredClone(savedSettings));
    props.setPreviewDesign(materialDesignFromSkin(savedSettings.skin));
  };
  const restoreDefault = () => {
    const next = { ...adminSettings, skin: { ...skin, ...materialDesignSkin(defaultMaterialDesign) } };
    setAdminSettings(next);
    props.setPreviewDesign(defaultMaterialDesign);
  };
  const loadHeadingImage = async (file: File | null) => {
    if (!file) return;
    updateDesign('headingBackgroundImageUrl', await readFileAsDataUrl(file));
  };
  const banners = normalizeBanners(config.materialsDescriptionBanners ?? props.settings.descriptionBanners);
  const toggleSelected = (id: number) => setSelectedIds((ids) => ids.includes(id) ? ids.filter((value) => value !== id) : [...ids, id]);
  const clearSelection = () => setSelectedIds([]);
  const adminDeleteSelected = async () => {
    const response = await api.adminDelete(password, selectedIds);
    props.setNotice(response.message);
    clearSelection();
    await props.reload();
  };
  const restoreSelected = async () => {
    const response = await api.restoreItems(password, selectedIds);
    props.setNotice(response.message);
    clearSelection();
    const deletedResponse = await api.deletedItems(password);
    setDeletedItems(deletedResponse.items);
    await props.reload();
  };
  const purgeSelected = async () => {
    const response = await api.purgeItems(password, selectedIds);
    props.setNotice(response.message);
    clearSelection();
    const deletedResponse = await api.deletedItems(password);
    setDeletedItems(deletedResponse.items);
    await props.reload();
  };

  return (
    <>
      <Panel title="管理">
        <p>管理者としてログインしています。ドキュメントホルダーの設定、タグ、デザインを管理できます。</p>
        {analytics && <p>投稿数: {analytics.total_items ?? 0} / 閲覧数合計: {analytics.total_views ?? 0} / 作者数: {analytics.authors ?? 0}</p>}
      </Panel>
      <div className="admin-tabs" role="tablist" aria-label="管理メニュー">
        <button type="button" className={tab === 'posts' ? '' : 'secondary'} onClick={() => { setTab('posts'); clearSelection(); }}>投稿管理</button>
        <button type="button" className={tab === 'deleted' ? '' : 'secondary'} onClick={() => { setTab('deleted'); clearSelection(); }}>復元 / 消去</button>
        <button type="button" className={tab === 'settings' ? '' : 'secondary'} onClick={() => { setTab('settings'); clearSelection(); }}>設定</button>
        <button type="button" className={tab === 'design' ? '' : 'secondary'} onClick={() => { setTab('design'); clearSelection(); }}>デザイン</button>
      </div>
      {tab === 'posts' && <Panel title="投稿管理">
        <p>チェックした投稿を、投稿パスワードなしで一括削除できます。</p>
        <AdminItemTable items={props.items} selectedIds={selectedIds} toggleSelected={toggleSelected} />
        <div className="actions"><button className="danger" type="button" disabled={selectedIds.length === 0} onClick={() => void adminDeleteSelected()}>チェックした項目を一括削除</button></div>
      </Panel>}
      {tab === 'deleted' && <Panel title="復元 / 消去">
        <p>削除済み投稿を復元、または完全消去できます。</p>
        <AdminItemTable items={deletedItems} selectedIds={selectedIds} toggleSelected={toggleSelected} />
        <div className="actions">
          <button type="button" disabled={selectedIds.length === 0} onClick={() => void restoreSelected()}>復元する</button>
          <button className="danger" type="button" disabled={selectedIds.length === 0} onClick={() => void purgeSelected()}>完全消去する</button>
        </div>
      </Panel>}
      {tab === 'settings' && <>
      <Panel title="掲示板設定">
        <div className="material-form admin-config-form">
          <label>タイトル<input value={String(config.materialsTitle ?? '')} onChange={(event) => updateConfig('materialsTitle', event.target.value)} /></label>
          <label>説明文<textarea value={String(config.materialsDescription ?? '')} onChange={(event) => updateConfig('materialsDescription', event.target.value)} /></label>
          <fieldset className="settings-group banner-settings">
            <legend>説明欄バナー</legend>
            <label className="inline-check"><input type="checkbox" checked={Boolean(config.materialsDescriptionBannerEnabled)} onChange={(event) => updateConfig('materialsDescriptionBannerEnabled', event.target.checked)} />説明欄にバナーを表示する</label>
            <label>切替間隔(ms)<input type="number" value={Number(config.materialsDescriptionBannerIntervalMs ?? 5000)} onChange={(event) => updateConfig('materialsDescriptionBannerIntervalMs', Number(event.target.value))} /></label>
            {banners.map((banner, index) => <div className="banner-row" key={index}>
              <input value={banner.imageUrl} onChange={(event) => updateBanner(index, { ...banner, imageUrl: event.target.value }, banners, updateConfig)} placeholder="./assets/title01.gif" />
              <input value={banner.linkUrl} onChange={(event) => updateBanner(index, { ...banner, linkUrl: event.target.value }, banners, updateConfig)} placeholder="リンク先（任意）" />
              <button type="button" className="secondary" onClick={() => updateConfig('materialsDescriptionBanners', banners.filter((_, i) => i !== index))}>削除</button>
            </div>)}
            <button type="button" className="secondary" onClick={() => updateConfig('materialsDescriptionBanners', [...banners, { imageUrl: '', linkUrl: '', alt: 'banner' }])}>バナーを追加</button>
          </fieldset>
          <label>目次の既定並び
            <select value={String(config.materialsGroupParent ?? 'tag')} onChange={(event) => updateConfig('materialsGroupParent', event.target.value)}>
              <option value="tag">タグを親にする</option>
              <option value="author">投稿者名を親にする</option>
            </select>
          </label>
          <fieldset className="settings-group">
            <legend>親サイトSSO</legend>
            <label>SSO使用
              <select value={Boolean(config.ssoEnabled) ? '1' : '0'} onChange={(event) => updateConfig('ssoEnabled', event.target.value === '1')}>
                <option value="0">OFF</option>
                <option value="1">ON</option>
              </select>
            </label>
            <label>SSO共有秘密鍵<input type="password" value={String(config.ssoSharedSecret ?? '')} onChange={(event) => updateConfig('ssoSharedSecret', event.target.value)} /></label>
            <p className="hint">ONにすると新規登録と管理者によるユーザー情報編集/消去を親サイト側に寄せます。</p>
          </fieldset>
          <div className="actions"><button type="button" onClick={() => void save()}>設定を保存</button></div>
        </div>
      </Panel>
      <Panel title="タグ">
        <TagEditor tags={localTags} setTags={setLocalTags} />
        <div className="actions"><button type="button" onClick={() => void save()}>タグを保存</button></div>
      </Panel>
      </>}
      {tab === 'design' && <Panel title="デザイン">
        <div className="color-grid">
          {allDesignFields.map(([key, label]) => <label key={key}>{label}<span className="color-input">
            <input type="color" value={String(skin[`materials${key[0].toUpperCase()}${key.slice(1)}`] ?? defaultMaterialDesign[key])} onChange={(event) => updateDesign(key, event.target.value)} />
            <input value={String(skin[`materials${key[0].toUpperCase()}${key.slice(1)}`] ?? '')} onChange={(event) => updateDesign(key, event.target.value)} />
          </span></label>)}
        </div>
        <fieldset className="settings-group">
          <legend>見出しバー画像</legend>
          <label>画像URL / data URL<input value={String(skin.materialsHeadingBackgroundImageUrl ?? '')} onChange={(event) => updateDesign('headingBackgroundImageUrl', event.target.value)} /></label>
          <label>1px画像を読み込む<input type="file" accept="image/*" onChange={(event) => void loadHeadingImage(event.target.files?.[0] ?? null)} /></label>
        </fieldset>
        <div className="actions">
          <button className="secondary" type="button" onClick={restoreDefault}>デフォルトに戻す</button>
          <button className="secondary" type="button" onClick={restoreSaved}>編集前に戻す</button>
          <button type="button" onClick={() => void save()}>設定を保存</button>
        </div>
      </Panel>}
    </>
  );
}

function AdminItemTable({ items, selectedIds, toggleSelected }: { items: MaterialItem[]; selectedIds: number[]; toggleSelected: (id: number) => void }) {
  if (items.length === 0) return <p className="empty compact">対象はありません。</p>;
  return (
    <div className="admin-item-table">
      {items.map((item) => <label className="admin-item-row" key={item.id}>
        <input type="checkbox" checked={selectedIds.includes(item.id)} onChange={() => toggleSelected(item.id)} />
        <strong>No.{item.id}</strong>
        <span>{item.name}</span>
        <span>{item.authorName}</span>
        <span>{item.primaryTagName}{item.subtagName ? ` / ${item.subtagName}` : ''}</span>
        <small>{formatDate(item.updatedAt)}</small>
      </label>)}
    </div>
  );
}

function updateBanner(index: number, banner: Banner, banners: Banner[], updateConfig: (key: string, value: unknown) => void) {
  updateConfig('materialsDescriptionBanners', banners.map((item, i) => i === index ? banner : item));
}

function normalizeBanners(value: unknown): Banner[] {
  if (!Array.isArray(value)) return [];
  return value.map((item) => ({
    imageUrl: String((item as Banner).imageUrl ?? ''),
    linkUrl: String((item as Banner).linkUrl ?? ''),
    alt: String((item as Banner).alt ?? 'banner'),
  }));
}

function TagEditor({ tags, setTags }: { tags: MaterialTag[]; setTags: (tags: MaterialTag[]) => void }) {
  const primary = tags.filter((tag) => tag.parentId === null);
  return (
    <div className="tag-editor">
      {tags.map((tag, index) => <div className="tag-editor-row" key={`${tag.id}-${index}`}>
        <input value={tag.name} onChange={(event) => setTags(tags.map((item, i) => i === index ? { ...item, name: event.target.value } : item))} />
        <select value={tag.parentId ?? ''} onChange={(event) => setTags(tags.map((item, i) => i === index ? { ...item, parentId: event.target.value ? Number(event.target.value) : null } : item))}>
          <option value="">第一階層</option>
          {primary.filter((parent) => parent.id !== tag.id).map((parent) => <option value={parent.id} key={parent.id}>{parent.name}</option>)}
        </select>
        <button type="button" className="secondary" onClick={() => setTags(tags.filter((_, i) => i !== index))}>削除</button>
      </div>)}
      <button type="button" className="secondary" onClick={() => setTags([...tags, { id: 0, name: '新しいタグ', parentId: null, sortOrder: tags.length }])}>タグを追加</button>
    </div>
  );
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) {
  return <section className="panel"><h1>{title}</h1><div className="panel-body">{children}</div></section>;
}

function routeFromHash(): Route {
  const hash = window.location.hash.replace(/^#\/?/, '');
  const match = hash.match(/^article\/(\d+)/);
  if (match) return { page: 'article', id: Number(match[1]) };
  if (['post', 'delete', 'edit', 'manual', 'login', 'admin'].includes(hash)) return { page: hash as Page };
  return { page: 'list' };
}

function documentHtmlFromItem(item: MaterialItem) {
  if (item.notes.startsWith(DOCUMENT_HTML_PREFIX)) return sanitizePreview(item.notes.slice(DOCUMENT_HTML_PREFIX.length));
  return `<p>${escapeHtml(item.notes).replace(/\n/g, '<br>')}</p>`;
}

function normalizeEditorHtml(value: string) {
  const html = sanitizePreview(value).trim();
  const text = stripHtml(html).trim();
  return text || /<img\b/i.test(html) || /<table\b/i.test(html) ? html : '<p></p>';
}

function sanitizePreview(value: string) {
  const template = document.createElement('template');
  template.innerHTML = value;
  template.content.querySelectorAll('script,iframe,object,embed,link,meta').forEach((node) => node.remove());
  template.content.querySelectorAll('*').forEach((node) => {
    [...node.attributes].forEach((attr) => {
      if (/^on/i.test(attr.name) || (/^(href|src)$/i.test(attr.name) && /^javascript:/i.test(attr.value))) {
        node.removeAttribute(attr.name);
      }
    });
  });
  return template.innerHTML;
}

function plainTextPreview(value: string) {
  const text = stripHtml(value.startsWith(DOCUMENT_HTML_PREFIX) ? value.slice(DOCUMENT_HTML_PREFIX.length) : value).replace(/\s+/g, ' ').trim();
  return text.length > 90 ? `${text.slice(0, 90)}...` : text;
}

function stripHtml(value: string) {
  const element = document.createElement('div');
  element.innerHTML = value;
  return element.textContent ?? '';
}

function formatDate(value: string) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
}

function safeFileName(value: string) {
  return (value || 'document').replace(/[\\/:*?"<>|]+/g, '_').slice(0, 80);
}

async function htmlFromUpload(file: File) {
  if (/\.html?$/i.test(file.name)) {
    return DOCUMENT_HTML_PREFIX + await file.text();
  }
  return DOCUMENT_HTML_PREFIX + `<p>${escapeHtml(file.name)} を添付しました。本文は記事ファイルをダウンロードして確認してください。</p>`;
}

function htmlDocument(title: string, html: string) {
  return `<!doctype html><html lang="ja"><head><meta charset="utf-8"><title>${escapeHtml(title)}</title></head><body>${html}</body></html>`;
}

function readFileAsDataUrl(file: File) {
  return new Promise<string>((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result ?? ''));
    reader.onerror = () => reject(reader.error ?? new Error('画像を読み込めませんでした。'));
    reader.readAsDataURL(file);
  });
}

function escapeHtml(value: string) {
  return value.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char] ?? char));
}

function escapeAttribute(value: string) {
  return escapeHtml(value).replace(/`/g, '&#96;');
}

function textBytes(value: string) {
  return new TextEncoder().encode(value);
}

function fileFromBytes(name: string, bytes: Uint8Array) {
  return new File([new Blob([bytes], { type: 'application/zip' })], name, { type: 'application/zip' });
}

function makeZip(files: Array<{ path: string; data: Uint8Array }>) {
  const localParts: Uint8Array[] = [];
  const centralParts: Uint8Array[] = [];
  let offset = 0;
  for (const file of files) {
    const name = textBytes(file.path.replace(/\\/g, '/'));
    const crc = crc32(file.data);
    const local = new Uint8Array(30 + name.length + file.data.length);
    const view = new DataView(local.buffer);
    view.setUint32(0, 0x04034b50, true);
    view.setUint16(8, 0, true);
    view.setUint32(14, crc, true);
    view.setUint32(18, file.data.length, true);
    view.setUint32(22, file.data.length, true);
    view.setUint16(26, name.length, true);
    local.set(name, 30);
    local.set(file.data, 30 + name.length);
    localParts.push(local);

    const central = new Uint8Array(46 + name.length);
    const centralView = new DataView(central.buffer);
    centralView.setUint32(0, 0x02014b50, true);
    centralView.setUint16(4, 20, true);
    centralView.setUint16(6, 20, true);
    centralView.setUint32(16, crc, true);
    centralView.setUint32(20, file.data.length, true);
    centralView.setUint32(24, file.data.length, true);
    centralView.setUint16(28, name.length, true);
    centralView.setUint32(42, offset, true);
    central.set(name, 46);
    centralParts.push(central);
    offset += local.length;
  }
  const centralSize = centralParts.reduce((sum, part) => sum + part.length, 0);
  const end = new Uint8Array(22);
  const endView = new DataView(end.buffer);
  endView.setUint32(0, 0x06054b50, true);
  endView.setUint16(8, files.length, true);
  endView.setUint16(10, files.length, true);
  endView.setUint32(12, centralSize, true);
  endView.setUint32(16, offset, true);
  return concatBytes([...localParts, ...centralParts, end]);
}

function concatBytes(parts: Uint8Array[]) {
  const length = parts.reduce((sum, part) => sum + part.length, 0);
  const output = new Uint8Array(length);
  let offset = 0;
  for (const part of parts) {
    output.set(part, offset);
    offset += part.length;
  }
  return output;
}

function crc32(data: Uint8Array) {
  let crc = -1;
  for (const byte of data) {
    crc ^= byte;
    for (let bit = 0; bit < 8; bit++) {
      crc = (crc >>> 1) ^ (0xedb88320 & -(crc & 1));
    }
  }
  return (crc ^ -1) >>> 0;
}

function designVariables(settings: MaterialSettings): CSSProperties {
  const headingImage = settings.design.headingBackgroundImageUrl?.trim();
  return {
    '--page-bg': settings.design.pageBackgroundColor,
    '--page-text': settings.design.pageTextColor,
    '--header-bg': settings.design.headerBackgroundColor,
    '--header-text': settings.design.headerTextColor,
    '--header-border': settings.design.headerBorderColor,
    '--panel-bg': settings.design.panelBackgroundColor,
    '--panel-border': settings.design.panelBorderColor,
    '--heading-bg': settings.design.headingBackgroundColor,
    '--heading-image': headingImage ? `url("${headingImage.replace(/"/g, '\\"')}")` : 'none',
    '--heading-text': settings.design.headingTextColor,
    '--accent': settings.design.accentColor,
    '--button-bg': settings.design.buttonBackgroundColor,
    '--button-text': settings.design.buttonTextColor,
    '--secondary-button-bg': settings.design.secondaryButtonBackgroundColor,
    '--secondary-button-text': settings.design.secondaryButtonTextColor,
    '--danger-button-bg': settings.design.dangerButtonBackgroundColor,
    '--danger-button-text': settings.design.dangerButtonTextColor,
    '--input-bg': settings.design.inputBackgroundColor,
    '--input-text': settings.design.inputTextColor,
    '--form-label': settings.design.formLabelColor,
    '--editor-bg': settings.design.editorBackgroundColor,
    '--editor-text': settings.design.editorTextColor,
    '--editor-border': settings.design.editorBorderColor,
    '--toolbar-bg': settings.design.toolbarBackgroundColor,
    '--selection-bg': settings.design.selectionBackgroundColor,
    '--selection-hover-bg': settings.design.selectionHoverBackgroundColor,
    '--selection-text': settings.design.selectionTextColor,
    '--selection-meta': settings.design.selectionMetaColor,
    '--image-bg': settings.design.imageBackgroundColor,
    '--muted-text': settings.design.mutedTextColor,
    '--list-row-text': settings.design.listRowTextColor,
    '--list-row-meta': settings.design.listRowMetaColor,
    '--list-row-border': settings.design.listRowBorderColor,
    '--toc-group-title': settings.design.tocGroupTitleColor,
    '--positive': settings.design.positiveColor,
    '--negative': settings.design.negativeColor,
    '--unknown': settings.design.unknownColor,
  } as CSSProperties;
}

function materialDesignSkin(design: MaterialDesign): Record<string, string> {
  return Object.fromEntries(Object.entries(design).map(([key, value]) => [`materials${key[0].toUpperCase()}${key.slice(1)}`, value]));
}

function materialDesignFromSkin(skin: Record<string, unknown>): MaterialDesign {
  return Object.fromEntries(Object.entries(defaultMaterialDesign).map(([key, fallback]) => [
    key,
    String(skin[`materials${key[0].toUpperCase()}${key.slice(1)}`] ?? fallback),
  ])) as MaterialDesign;
}

const allDesignFields: Array<[keyof MaterialDesign, string]> = [
  ['pageBackgroundColor', 'ページ背景'],
  ['pageTextColor', '本文文字'],
  ['headerBackgroundColor', '上部メニュー背景'],
  ['headerTextColor', '上部メニュー文字'],
  ['headerBorderColor', '上部メニュー線'],
  ['panelBackgroundColor', 'パネル背景'],
  ['panelBorderColor', '枠線'],
  ['headingBackgroundColor', '見出しバー背景'],
  ['headingTextColor', '見出しバー文字'],
  ['accentColor', 'リンク/アクセント'],
  ['buttonBackgroundColor', 'ボタン背景'],
  ['buttonTextColor', 'ボタン文字'],
  ['secondaryButtonBackgroundColor', '補助ボタン背景'],
  ['secondaryButtonTextColor', '補助ボタン文字'],
  ['inputBackgroundColor', '入力欄背景'],
  ['inputTextColor', '入力欄文字'],
  ['formLabelColor', 'フォームラベル'],
  ['editorBackgroundColor', '本文エディタ背景'],
  ['editorTextColor', '本文エディタ文字'],
  ['editorBorderColor', '本文エディタ枠線'],
  ['toolbarBackgroundColor', 'ツールバー背景'],
  ['selectionBackgroundColor', '選択一覧背景'],
  ['selectionHoverBackgroundColor', '選択一覧ホバー背景'],
  ['selectionTextColor', '選択一覧文字'],
  ['selectionMetaColor', '選択一覧補助文字'],
  ['listRowTextColor', '一覧本文'],
  ['listRowMetaColor', '一覧補助文字'],
  ['listRowBorderColor', '一覧区切り線'],
  ['tocGroupTitleColor', '目次見出し文字'],
];

export default App;
