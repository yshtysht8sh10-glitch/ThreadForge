import { FormEvent, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api, DEFAULT_PUBLIC_SETTINGS, PublicSettings } from '../api';
import { Post } from '../types';
import SelectableThreadList from '../components/SelectableThreadList';
import { useAuth } from '../auth';
import { eejanaikaOptionsFromSettings } from '../components/ThreadList';
import PeriodFilter, { Period, filterPostsByPeriods, periodsFromPosts } from '../components/PeriodFilter';

const MODE_THREAD_BATCH_SIZE = 20;

const EditModePage = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [password, setPassword] = useState('');
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [threads, setThreads] = useState<Post[]>([]);
  const [selectedYears, setSelectedYears] = useState<string[]>([]);
  const [selectedMonths, setSelectedMonths] = useState<string[]>([]);
  const [periods, setPeriods] = useState<Period[]>([]);
  const [filteredTotal, setFilteredTotal] = useState(0);
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadPeriodMeta = async (years = selectedYears, months = selectedMonths) => {
    if (api.listThreadArchiveMeta) {
      return api.listThreadArchiveMeta(years, months);
    }
    const allThreads = await api.listThreads();
    return {
      periods: periodsFromPosts(allThreads),
      total: filterPostsByPeriods(allThreads, years, months).length,
    };
  };

  const loadThreads = async (years = selectedYears, months = selectedMonths) => {
    setLoading(true);
    try {
      const [meta, items, settingResponse] = await Promise.all([
        loadPeriodMeta(years, months),
        api.listThreads(null, 1, MODE_THREAD_BATCH_SIZE, years, months),
        api.publicSettings(),
      ]);
        setPeriods(meta.periods);
        setFilteredTotal(meta.total);
        setThreads(items);
        if (settingResponse.success) setSettings(settingResponse.settings);
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

      {loading && threads.length === 0 ? (
        <div className="board-message">読み込み中...</div>
      ) : (
        <>
        <PeriodFilter
          periods={periods}
          selectedYears={selectedYears}
          selectedMonths={selectedMonths}
          total={filteredTotal}
          onChange={(years, months) => {
            setSelectedYears(years);
            setSelectedMonths(months);
            setSelectedIds([]);
            void loadThreads(years, months);
          }}
        />
        {loading && threads.length > 0 && <div className="board-message compact">表示期間を更新中...</div>}
        <SelectableThreadList
          threads={threads}
          selectedIds={selectedIds}
          onToggle={toggleSelected}
          isDisabled={(post) => isPresetComment(post, settings)}
          disabledLabel="定型コメントは編集不可"
        />
        </>
      )}
    </>
  );
};

function isPresetComment(post: Post, settings: PublicSettings): boolean {
  if (post.parent_id === 0) return false;
  return eejanaikaOptionsFromSettings(settings.config).some((option) => option.text === post.message);
}

export default EditModePage;
