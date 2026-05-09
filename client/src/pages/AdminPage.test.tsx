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
    getSettings: vi.fn(),
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

describe('AdminPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.localStorage.clear();
    vi.mocked(api.listThreads).mockResolvedValue([thread as any]);
    vi.mocked(api.listDeletedPosts).mockResolvedValue([deletedReply as any]);
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
          gdgdLabel: 'gdgd投稿',
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
    expect(await screen.findByRole('button', { name: '投稿管理' })).toBeInTheDocument();
    expect(api.listDeletedPosts).toHaveBeenCalledWith('admin');
    expect(api.getSettings).toHaveBeenCalledWith('admin');
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

    await waitFor(() => expect(api.adminDeletePosts).toHaveBeenCalledWith(['1', '2'], 'admin'));
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
    fireEvent.change(within(settingsPanel).getByLabelText('取説本文'), { target: { value: '管理画面で編集した取説です。' } });
    fireEvent.click(within(settingsPanel).getByRole('button', { name: '設定を保存' }));

    await waitFor(() => expect(api.updateSettings).toHaveBeenCalledWith(
      expect.objectContaining({
        config: expect.objectContaining({
          homePageUrl: 'https://threadforge.example/',
          manualBody: '管理画面で編集した取説です。',
        }),
      }),
      'admin',
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
    });

    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    const settingsTab = (await screen.findAllByRole('button'))[3];
    fireEvent.click(settingsTab);

    const settingsPanel = screen.getByDisplayValue('https://twitter.com/example/status/').closest('section')!;
    const tweetEnabledSelect = within(settingsPanel)
      .getAllByRole('combobox')
      .find((element) => (element as HTMLSelectElement).value === 'false');

    expect(tweetEnabledSelect).toBeDefined();
    expect(tweetEnabledSelect).not.toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('https://twitter.com/example/status/')).toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('key')).toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('secret')).toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('token')).toBeDisabled();
    expect(within(settingsPanel).getByDisplayValue('token-secret')).toBeDisabled();
  });

  it('does not expose legacy BBSnote import in the backup tab', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: 'バックアップ' }));

    expect(screen.queryByText('旧BBSnoteログ追加インポート')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('旧BBSnoteログディレクトリ')).not.toBeInTheDocument();
  });

  it('shows board display numbers for deleted replies instead of raw database ids', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: '削除済み' }));

    expect(screen.getByText(/No\.12-2/)).toBeInTheDocument();
    expect(screen.queryByText(/No\.514/)).not.toBeInTheDocument();
  });
});
