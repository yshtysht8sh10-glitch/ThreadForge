import { FormEvent, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../api';
import { Post } from '../types';
import SelectableThreadList from '../components/SelectableThreadList';
import { useAuth } from '../auth';

const EditModePage = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [password, setPassword] = useState('');
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [threads, setThreads] = useState<Post[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.listThreads()
      .then((items) => setThreads(items))
      .catch((err) => setError((err as Error).message))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (user?.post_password) {
      setPassword(user.post_password);
    }
  }, [user]);

  const toggleSelected = (targetId: string) => {
    setSelectedIds((current) => (
      current.includes(targetId) ? [] : [targetId]
    ));
  };

  const onSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);

    if (selectedIds.length !== 1) {
      setError('編集する投稿または返信を1件だけ選択してください。');
      return;
    }
    if (!password.trim()) {
      setError('パスワードを入力してください。');
      return;
    }

    navigate(`/edit/${selectedIds[0]}`, { state: { password } });
  };

  return (
    <>
      <section className="mode-page">
        <h1>編集モード</h1>
        <p>編集する投稿または返信を1件選択し、投稿時のパスワードを入力してください。</p>
        <form onSubmit={onSubmit} className="mode-inline-form">
          <label>
            パスワード
            <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} />
          </label>
          <button type="submit">チェックした項目を編集する</button>
          <button type="button" className="post-reset-button" onClick={() => navigate('/')}>戻る</button>
        </form>
        {selectedIds.length > 0 && <p className="status">選択中: No.{selectedIds[0]}</p>}
        {error && <p className="error">エラー: {error}</p>}
      </section>

      {loading ? (
        <div className="board-message">読み込み中...</div>
      ) : (
        <SelectableThreadList threads={threads} selectedIds={selectedIds} onToggle={toggleSelected} />
      )}
    </>
  );
};

export default EditModePage;
