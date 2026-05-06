import { useEffect, useState } from 'react';
import { Link, Route, Routes } from 'react-router-dom';
import { api, DEFAULT_PUBLIC_SETTINGS, PublicSettings } from './api';
import HomePage from './pages/HomePage';
import ThreadPage from './pages/ThreadPage';
import PostFormPage from './pages/PostFormPage';
import SearchPage from './pages/SearchPage';
import EditPostPage from './pages/EditPostPage';
import AdminPage from './pages/AdminPage';
import DeleteModePage from './pages/DeleteModePage';
import EditModePage from './pages/EditModePage';
import ManualPage from './pages/ManualPage';
import { APP_NAME, APP_VERSION } from './version';

const App = () => {
  const [publicSettings, setPublicSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);

  useEffect(() => {
    let ignore = false;
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
    <div className="app-shell">
      <header className="app-header">
        <nav className="board-nav" aria-label="メインメニュー">
          <span className="nav-square" aria-hidden="true">■</span>
          <span className="nav-separator">|</span>
          <a href={homeHref(publicSettings.config.homePageUrl)}>HOME</a>
          <span className="nav-separator">|</span>
          <Link to="/" reloadDocument>一覧</Link>
          <span className="nav-separator">|</span>
          <Link to="/post">投稿</Link>
          <span className="nav-separator">|</span>
          <Link to="/delete">削除</Link>
          <span className="nav-separator">|</span>
          <Link to="/edit">編集</Link>
          <span className="nav-separator">|</span>
          <Link to="/search">検索</Link>
          <span className="nav-separator">|</span>
          <Link to="/manual">取説</Link>
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
          <Route path="/admin" element={<AdminPage />} />
        </Routes>
      </main>

      <footer className="app-footer">
        <p>{APP_NAME} v{APP_VERSION} / Powered by React + PHP API</p>
      </footer>
    </div>
  );
};

export function homeHref(value?: string): string {
  const raw = (value ?? '').trim();
  if (raw === '') {
    return '/';
  }
  if (/^https?:\/\//i.test(raw) || raw.startsWith('/')) {
    return raw;
  }
  return `https://${raw}`;
}

export default App;
