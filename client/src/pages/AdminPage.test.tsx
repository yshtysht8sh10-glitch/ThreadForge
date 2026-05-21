import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import AdminPage from './AdminPage';
import { api } from '../api';

vi.mock('../api', () => ({
  apiBase: () => '/api.php',
  mediaUrl: (path?: string | null) => path,
  api: {
    listThreads: vi.fn(),
    listDeletedPosts: vi.fn(),
    listAnalyticsPosts: vi.fn(),
    getSettings: vi.fn(),
    adminStatus: vi.fn(),
    initializeAdminPassword: vi.fn(),
    adminDeletePosts: vi.fn(),
    importBackup: vi.fn(),
    updateSettings: vi.fn(),
    restorePost: vi.fn(),
    changeAdminPassword: vi.fn(),
    refreshSocialReactions: vi.fn(),
  },
}));

const thread = {
  id: 1,
  display_no: 1,
  thread_id: 1,
  parent_id: 0,
  name: 'Alice',
  url: null,
  title: 'Root',
  message: 'Body',
  image_path: null,
  created_at: '2026-05-05 10:00:00',
  replies: [{
    id: 2,
    thread_id: 1,
    parent_id: 1,
    name: 'Bob',
    title: 'Re: Root',
    message: 'Reply',
    image_path: null,
    created_at: '2026-05-05 10:01:00',
    reply_no: 1,
  }],
};

const deletedReply = {
  id: 514,
  display_no: 12,
  reply_no: 2,
  thread_id: 500,
  parent_id: 500,
  name: 'Deleted Bob',
  title: 'Re: Root',
  message: 'Deleted Reply',
  image_path: null,
  created_at: '2026-05-05 10:02:00',
  deleted_at: '2026-05-06 22:26:16',
};

const analyticsThreads = [
  {
    ...thread,
    created_at: '2026-05-05 10:00:00',
    social_reactions: {
      x: { likes: 10, reposts: 2, impressions: 100 },
      bluesky: { likes: 4, reposts: 1, quotes: 0 },
      mastodon: { boosts: 3, favs: 7 },
      misskey: { fire: 1, eyes: 2, cry: 0, thinking: 0, party: 1, other: 1 },
    },
  },
  {
    ...thread,
    id: 10,
    thread_id: 10,
    display_no: 2,
    created_at: '2026-06-01 10:00:00',
    social_reactions: {
      x: { likes: 5, reposts: 1, impressions: 40 },
      mastodon: { boosts: 0, favs: 2 },
    },
  },
];

describe('AdminPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.localStorage.clear();
    window.localStorage.setItem('threadforgeAdminPassword', 'admin-secret');
    vi.mocked(api.listThreads).mockResolvedValue([thread as any]);
    vi.mocked(api.listDeletedPosts).mockResolvedValue([deletedReply as any]);
    vi.mocked(api.listAnalyticsPosts).mockResolvedValue(analyticsThreads as any);
    vi.mocked(api.adminStatus).mockResolvedValue({ success: true, adminPasswordConfigured: true });
    vi.mocked(api.initializeAdminPassword).mockResolvedValue({ success: true, message: '管理者パスワードを設定しました。' });
    vi.mocked(api.getSettings).mockResolvedValue({
      success: true,
      settings: {
        config: {
          bbsTitle: 'ThreadForge',
          homePageUrl: 'https://example.com/home',
          manualTitle: '取説タイトル',
          manualBody: '取説本文',
          tweetEnabled: true,
          gdgdEnabled: true,
          gdgdLabel: '特殊投稿',
          socialHashtags: '#art',
          logView: 20,
          maxUploadBytes: 5100000,
        },
        skin: { normalFrameColor: '#a23dff' },
      },
    });
    vi.mocked(api.adminDeletePosts).mockResolvedValue({ success: true, message: '2件を削除しました。' });
    vi.mocked(api.refreshSocialReactions).mockResolvedValue({
      success: true,
      message: 'SNSリアクションを更新しました。',
      updated: 0,
      errors: [],
    });
  });

  it('loads admin data automatically without showing a password form', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    expect(screen.getByRole('heading', { name: '管理' })).toBeInTheDocument();
    expect(screen.queryByLabelText('管理パスワード')).not.toBeInTheDocument();
    expect(await screen.findByRole('button', { name: '一括削除' })).toBeInTheDocument();
    expect(api.listDeletedPosts).toHaveBeenCalledWith('admin-secret');
    expect(api.getSettings).toHaveBeenCalledWith('admin-secret');
  });

  it('shows the requested admin tab order and can exit the admin screen', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    const nav = await screen.findByRole('navigation', { name: '管理メニュー' });
    expect(within(nav).getAllByRole('button').map((button) => button.textContent)).toEqual([
      '一括削除',
      '投稿復元',
      '保守',
      'アナリティクス',
      '掲示板設定',
      '掲示板デザイン',
    ]);

    fireEvent.click(screen.getByRole('button', { name: '管理画面から出る' }));

    expect(screen.getByRole('button', { name: '管理画面に入る' })).toBeInTheDocument();
    expect(screen.queryByRole('navigation', { name: '管理メニュー' })).not.toBeInTheDocument();
    expect(window.localStorage.getItem('threadforgeAdminPassword')).toBeNull();
  });

  it('imports design JSON and saves it as skin settings', async () => {
    vi.mocked(api.updateSettings).mockResolvedValue({ success: true, message: '設定を保存しました。' });
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: '掲示板デザイン' }));
    const designPanel = screen.getByRole('heading', { name: '掲示板デザイン インポート/エクスポート' }).closest('section')!;
    const file = new File([JSON.stringify({ normalFrameColor: '#112233' })], 'threadforge-design.json', { type: 'application/json' });
    fireEvent.change(within(designPanel).getByLabelText('掲示板デザインJSONファイル'), { target: { files: [file] } });

    expect(await screen.findByText('掲示板デザインJSONを読み込みました。保存すると反映されます。')).toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: '設定を保存' }));

    await waitFor(() => expect(api.updateSettings).toHaveBeenCalledWith(
      expect.objectContaining({
        skin: expect.objectContaining({ normalFrameColor: '#112233' }),
      }),
      'admin-secret',
    ));
  });
  it('sets the first admin password from the admin screen', async () => {
    window.localStorage.clear();
    vi.mocked(api.adminStatus).mockResolvedValue({ success: true, adminPasswordConfigured: false });

    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    expect(await screen.findByText(/初期設定が未完了です/)).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText('初期管理者パスワード'), { target: { value: 'first-secret' } });
    fireEvent.change(screen.getByLabelText('確認'), { target: { value: 'first-secret' } });
    fireEvent.click(screen.getByRole('button', { name: '管理者パスワードを設定' }));

    await waitFor(() => expect(api.initializeAdminPassword).toHaveBeenCalledWith('first-secret'));
    await waitFor(() => expect(api.getSettings).toHaveBeenCalledWith('first-secret'));
    expect(window.localStorage.getItem('threadforgeAdminPassword')).toBe('first-secret');
  });

  it('bulk deletes multiple checked posts without post passwords', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    expect(await screen.findByText('Body')).toBeInTheDocument();
    fireEvent.click(screen.getByLabelText('No.1 を選択'));
    fireEvent.click(screen.getByLabelText('返信No.1-1 を選択'));
    fireEvent.click(screen.getByRole('button', { name: 'チェックした項目を一括削除' }));

    await waitFor(() => expect(api.adminDeletePosts).toHaveBeenCalledWith(['1', '2'], 'admin-secret'));
  });

  it('edits HOME and manual settings from the settings tab', async () => {
    vi.mocked(api.updateSettings).mockResolvedValue({ success: true, message: '設定を保存しました。' });
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: '掲示板設定' }));

    const settingsPanel = screen.getByRole('heading', { name: '掲示板設定' }).closest('section')!;
    fireEvent.change(within(settingsPanel).getByLabelText('HOMEリンク先'), { target: { value: 'https://threadforge.example/' } });
    fireEvent.change(within(settingsPanel).getByLabelText('掲示板/取説タイトル'), { target: { value: 'サイトの使い方' } });
    fireEvent.change(within(settingsPanel).getByLabelText('取説本文'), { target: { value: '管理画面で編集した取説です。' } });
    fireEvent.click(within(settingsPanel).getByRole('button', { name: '設定を保存' }));

    await waitFor(() => expect(api.updateSettings).toHaveBeenCalledWith(
      expect.objectContaining({
        config: expect.objectContaining({
          homePageUrl: 'https://threadforge.example/',
          manualTitle: 'サイトの使い方',
          manualBody: '管理画面で編集した取説です。',
        }),
      }),
      'admin-secret',
    ));
  });

  it('shows max upload size in KB while saving bytes', async () => {
    vi.mocked(api.updateSettings).mockResolvedValue({ success: true, message: '設定を保存しました。' });
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: '掲示板設定' }));

    const settingsPanel = screen.getByRole('heading', { name: '掲示板設定' }).closest('section')!;
    const uploadSize = within(settingsPanel).getByLabelText('最大アップロードサイズ(KB)');
    expect(uploadSize).toHaveValue('5100');
    fireEvent.change(uploadSize, { target: { value: '2048' } });
    fireEvent.click(within(settingsPanel).getByRole('button', { name: '設定を保存' }));

    await waitFor(() => expect(api.updateSettings).toHaveBeenCalledWith(
      expect.objectContaining({
        config: expect.objectContaining({
          maxUploadBytes: 2048000,
        }),
      }),
      'admin-secret',
    ));
  });

  it('disables tweet settings while the tweet feature is off', async () => {
    vi.mocked(api.getSettings).mockResolvedValue({
      success: true,
      settings: {
        config: {
          bbsTitle: 'ThreadForge',
          homePageUrl: 'https://example.com/home',
          manualTitle: 'Manual',
          manualBody: 'Manual body',
          tweetEnabled: false,
          tweetBaseUrl: 'https://twitter.com/example/status/',
          tweetConsumerKey: 'key',
          tweetConsumerSecret: 'secret',
          tweetAccessToken: 'token',
          tweetAccessTokenSecret: 'token-secret',
          gdgdEnabled: true,
          gdgdLabel: 'gdgd',
        },
        skin: {
          normalFrameColor: '#a23dff',
        },
      },
      system: { adminPasswordConfigured: true },
    });

    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    const settingsTab = await screen.findByRole('button', { name: '掲示板設定' });
    fireEvent.click(settingsTab);

    const settingsPanel = screen.getByDisplayValue('https://twitter.com/example/status/').closest('section')!;
    const tweetEnabledSelect = within(settingsPanel)
      .getAllByRole('combobox')
      .find((element) => (element as HTMLSelectElement).value === 'false');

    expect(tweetEnabledSelect).toBeDefined();
    expect(screen.getByText('X連携は未デバッグです。現時点では動作を保証しません。')).toBeInTheDocument();
    expect(tweetEnabledSelect).not.toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('https://twitter.com/example/status/')).toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('key')).toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('secret')).toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('token')).toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('token-secret')).toBeDisabled();
  });

  it('keeps backup controls in maintenance without exposing local archive import', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    expect(screen.queryByRole('button', { name: 'バックアップ' })).not.toBeInTheDocument();
    fireEvent.click(await screen.findByRole('button', { name: '保守' }));

    expect(screen.getByText('運用中のデータ保全、復元、外部SNS情報の更新、管理者パスワード変更を行います。')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '管理者パスワード変更' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'SNSリアクション更新' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'フルバックアップ インポート/エクスポート' })).toBeInTheDocument();
    expect(screen.getByText('投稿、返信、画像、ユーザー、作品登録、アクセス履歴、設定をフルバックアップZIPとして保存、またはフルバックアップZIPから復元します。ログインセッションは含めません。')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'エクスポート' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'インポート' })).toBeInTheDocument();
    expect(screen.queryByText('ローカルアーカイブログ追加インポート')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('ローカルアーカイブログディレクトリ')).not.toBeInTheDocument();
  });

  it('requires confirmation when changing the admin password', async () => {
    vi.mocked(api.changeAdminPassword).mockResolvedValue({ success: true, message: '管理者パスワードを変更しました。' });
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: '保守' }));
    const maintenancePanel = screen.getByRole('heading', { name: '保守' }).closest('section')!;
    const changePasswordSection = within(maintenancePanel).getByRole('heading', { name: '管理者パスワード変更' }).closest('section')!;

    fireEvent.change(within(changePasswordSection).getByLabelText('新しい管理者パスワード'), { target: { value: 'next-secret' } });
    fireEvent.change(within(changePasswordSection).getByLabelText('確認'), { target: { value: 'wrong-secret' } });
    fireEvent.click(within(changePasswordSection).getByRole('button', { name: '変更' }));

    expect(await screen.findByText('エラー: 確認用パスワードが一致しません。')).toBeInTheDocument();
    expect(api.changeAdminPassword).not.toHaveBeenCalled();

    fireEvent.change(within(changePasswordSection).getByLabelText('確認'), { target: { value: 'next-secret' } });
    fireEvent.click(within(changePasswordSection).getByRole('button', { name: '変更' }));

    await waitFor(() => expect(api.changeAdminPassword).toHaveBeenCalledWith('admin-secret', 'next-secret'));
    expect(window.localStorage.getItem('threadforgeAdminPassword')).toBe('next-secret');
  });

  it('shows analytics chart and aggregates selected metrics by unit', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: 'アナリティクス' }));

    expect(screen.getByRole('heading', { name: 'アナリティクス' })).toBeInTheDocument();
    expect(screen.getByRole('img', { name: '投稿作品の推移グラフ' })).toBeInTheDocument();
    expect(screen.getByRole('img', { name: '投稿作品の統計グラフ' })).toBeInTheDocument();
    expect(screen.getAllByText('2026-05').length).toBeGreaterThan(0);
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
    expect(screen.getAllByText('1').length).toBeGreaterThan(0);

    fireEvent.change(screen.getByLabelText('表示データ'), { target: { value: 'mastodonFavs' } });
    fireEvent.change(screen.getByLabelText('単位'), { target: { value: 'yearTotal' } });

    expect(screen.getAllByText('2026').length).toBeGreaterThan(0);
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
    expect(screen.getAllByText('9').length).toBeGreaterThan(0);

    fireEvent.change(screen.getByLabelText('表示データ'), { target: { value: 'postCount' } });
    fireEvent.change(screen.getByLabelText('単位'), { target: { value: 'monthCumulative' } });

    expect(screen.getAllByText('2').length).toBeGreaterThan(0);
  });

  it('shows board display numbers for deleted replies instead of raw database ids', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: '投稿復元' }));

    expect(screen.getByText(/No\.12-2/)).toBeInTheDocument();
    expect(screen.queryByText(/No\.514/)).not.toBeInTheDocument();
  });
});
