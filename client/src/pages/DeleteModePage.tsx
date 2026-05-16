import { FormEvent, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../api';
import { Post } from '../types';
import SelectableThreadList from '../components/SelectableThreadList';
import { useAuth } from '../auth';

const DeleteModePage = () => {
  const navigate = useNavigate();
  const { token, user } = useAuth();
  const [password, setPassword] = useState('');
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [threads, setThreads] = useState<Post[]>([]);
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadThreads = async () => {
    setLoading(true);
    try {
      setThreads(await api.listThreads());
      setError(null);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadThreads();
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

  const onSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setStatus(null);

    if (selectedIds.length === 0) {
      setError('削除する投稿または返信を1件選択してください。');
      return;
    }
    if (!password.trim()) {
      setError('パスワードを入力してください。');
      return;
    }

    try {
      const targetId = selectedIds[0];
      const response = token ? await api.deletePost(targetId, password, token) : await api.deletePost(targetId, password);
      if (!response.success) {
        setError(`No.${targetId}: ${response.message}`);
        return;
      }
      setStatus('1件を削除しました。');
      setSelectedIds([]);
      await loadThreads();
    } catch (err) {
      setError((err as Error).message);
    }
  };

  return (
    <>
      <section className="mode-page">
        <h1>削除モード</h1>
        <p>削除する投稿または返信を1件選択し、投稿時のパスワードを入力してください。</p>
        <form onSubmit={onSubmit} className="mode-inline-form">
          <label>
            パスワード
            <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} />
          </label>
          <button type="submit" className="danger">チェックした項目を削除する</button>
          <button type="button" className="post-reset-button" onClick={() => navigate('/')}>戻る</button>
        </form>
        {selectedIds.length > 0 && <p className="status">選択中: No.{selectedIds[0]}</p>}
        {status && <p className="status">{status}</p>}
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

export default DeleteModePage;
