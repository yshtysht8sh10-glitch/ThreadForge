import { CSSProperties, useEffect, useState } from 'react';
import { Link, Route, Routes } from 'react-router-dom';
import { api, DEFAULT_PUBLIC_SETTINGS, PublicSettings } from './api';
import { AuthProvider, useAuth } from './auth';
import HomePage from './pages/HomePage';
import ThreadPage from './pages/ThreadPage';
import PostFormPage from './pages/PostFormPage';
import SearchPage from './pages/SearchPage';
import EditPostPage from './pages/EditPostPage';
import AdminPage from './pages/AdminPage';
import DeleteModePage from './pages/DeleteModePage';
import EditModePage from './pages/EditModePage';
import ManualPage from './pages/ManualPage';
import LoginPage from './pages/LoginPage';
import BoardAnalyticsPage from './pages/BoardAnalyticsPage';
import RankingPage from './pages/RankingPage';
import UserPostsPage from './pages/UserPostsPage';
import { APP_NAME, APP_VERSION } from './version';

const AppShell = () => {
  const [publicSettings, setPublicSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);
  const { user } = useAuth();

  useEffect(() => {
    let ignore = false;
    api.recordAccess().catch(() => undefined);
    api.publicSettings()
      .then((response) => {
        if (!ignore && response.success) {
          setPublicSettings(response.settings);
        }
      })
      .catch(() => {
        setPublicSettings(DEFAULT_PUBLIC_SETTINGS);
      });

    return () => {
      ignore = true;
    };
  }, []);

  return (
    <div className="app-shell" style={boardDesignStyle(publicSettings.skin ?? {})}>
      <header className="app-header">
        <nav className="board-nav" aria-label="メインメニュー">
          <span className="nav-square" aria-hidden="true">■</span>
          <span className="nav-separator">|</span>
          <a href={homeHref(publicSettings.config.homePageUrl)}>HOME</a>
          <span className="nav-separator">|</span>
          <Link to="/">一覧</Link>
          <span className="nav-separator">|</span>
          <Link to="/post">投稿</Link>
          <span className="nav-separator">|</span>
          <Link to="/delete">削除</Link>
          <span className="nav-separator">|</span>
          <Link to="/edit">編集</Link>
          <span className="nav-separator">|</span>
          <Link to="/search">検索</Link>
          <span className="nav-separator">|</span>
          <Link to="/ranking">順位</Link>
          <span className="nav-separator">|</span>
          <Link to="/manual">取説</Link>
          <span className="nav-separator">|</span>
          <Link to="/login">{user ? user.display_name : 'ログイン'}</Link>
          <span className="nav-separator">|</span>
          <Link to="/admin" className="nav-square nav-admin-link" aria-label="管理者モード">■</Link>
        </nav>
      </header>

      <main className="app-main">
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/thread/:id" element={<ThreadPage />} />
          <Route path="/post" element={<PostFormPage />} />
          <Route path="/search" element={<SearchPage />} />
          <Route path="/delete" element={<DeleteModePage />} />
          <Route path="/edit" element={<EditModePage />} />
          <Route path="/edit/:id" element={<EditPostPage />} />
          <Route path="/manual" element={<ManualPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/user/:id" element={<UserPostsPage />} />
          <Route path="/analytics" element={<BoardAnalyticsPage />} />
          <Route path="/ranking" element={<RankingPage />} />
          <Route path="/admin" element={<AdminPage />} />
        </Routes>
      </main>

      <footer className="app-footer">
        <p>{APP_NAME} v{APP_VERSION} / Powered by React + PHP API</p>
      </footer>
    </div>
  );
};

function boardDesignStyle(skin: Record<string, string>): CSSProperties {
  const value = { ...DEFAULT_PUBLIC_SETTINGS.skin, ...skin } as Record<string, string>;
  return {
    '--board-bg': value.backgroundColor,
    '--board-text': value.pageTextColor,
    '--board-link': value.linkColor,
    '--panel-bg': value.panelBackgroundColor,
    '--panel-title-bg': value.panelTitleBackgroundColor,
    '--panel-border': value.panelBorderColor,
    '--panel-label': value.labelColor,
    '--panel-field-bg': value.inputBackgroundColor,
    '--board-input-text': value.inputTextColor,
    '--primary-button-bg': value.buttonBackgroundColor,
    '--primary-button-border': value.buttonBorderColor,
    '--board-button-text': value.buttonTextColor,
    '--secondary-button-bg': value.secondaryButtonBackgroundColor,
    '--secondary-button-text': value.secondaryButtonTextColor,
    '--secondary-button-border': value.secondaryButtonBorderColor,
    '--normal-frame': value.normalFrameColor,
    '--normal-header': value.normalHeaderColor,
    '--normal-text': value.normalTextColor,
    '--gdgd-frame': value.gdgdFrameColor,
    '--gdgd-header': value.gdgdHeaderColor,
    '--gdgd-text': value.gdgdTextColor,
    '--reply-border': value.replyBorderColor,
    '--quick-reaction-bg': value.quickReactionButtonBackgroundColor,
    '--danger-color': value.dangerColor,
    '--warning-color': value.warningColor,
    '--success-color': value.successColor,
  } as CSSProperties;
}

const App = () => (
  <AuthProvider>
    <AppShell />
  </AuthProvider>
);

export function homeHref(value?: string): string {
  const raw = (value ?? '').trim();
  if (raw === '' || raw === '/' || raw === './') {
    return appHomeHref();
  }
  if (/^https?:\/\//i.test(raw) || raw.startsWith('/')) {
    return raw;
  }
  return `https://${raw}`;
}

function appHomeHref(): string {
  const pathname = window.location.pathname;
  const basePath = pathname.endsWith('/index.html')
    ? pathname.slice(0, -'index.html'.length)
    : pathname.endsWith('/')
      ? pathname
      : `${pathname.replace(/\/[^/]*$/, '')}/`;
  return `${basePath}#/`;
}

export default App;
