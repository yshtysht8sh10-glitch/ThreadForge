import { Link, Route, Routes } from 'react-router-dom';
import HomePage from './pages/HomePage';
import ThreadPage from './pages/ThreadPage';
import PostFormPage from './pages/PostFormPage';
import SearchPage from './pages/SearchPage';
import EditPostPage from './pages/EditPostPage';
import AdminPage from './pages/AdminPage';
import DeleteModePage from './pages/DeleteModePage';
import EditModePage from './pages/EditModePage';
import ManualPage from './pages/ManualPage';

const App = () => {
  return (
    <div className="app-shell">
      <header className="app-header">
        <nav className="board-nav" aria-label="メインメニュー">
          <span className="nav-square" aria-hidden="true">■</span>
          <span className="nav-separator">|</span>
          <Link to="/">HOME</Link>
          <span className="nav-separator">|</span>
          <Link to="/" reloadDocument>更新</Link>
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
        <p>Powered by React + PHP API</p>
      </footer>
    </div>
  );
};

export default App;
