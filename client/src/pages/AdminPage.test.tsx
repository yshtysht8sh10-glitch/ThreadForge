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
    importLegacyBbsnote: vi.fn(),
    updateSettings: vi.fn(),
    restorePost: vi.fn(),
    changeAdminPassword: vi.fn(),
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
    vi.mocked(api.importLegacyBbsnote).mockResolvedValue({
      success: true,
      message: '旧BBSnoteログをインポートしました。',
      imported_threads: 1,
      imported_replies: 2,
      skipped_threads: 0,
      skipped_replies: 0,
      missing_images: [],
      files: 1,
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

  it('imports legacy BBSnote logs from the backup tab', async () => {
    render(
      <MemoryRouter>
        <AdminPage />
      </MemoryRouter>,
    );

    fireEvent.click(await screen.findByRole('button', { name: 'バックアップ' }));
    fireEvent.change(screen.getByLabelText('旧BBSnoteログディレクトリ'), { target: { value: 'data' } });
    fireEvent.click(screen.getByRole('button', { name: '旧BBSnoteログを追加インポート' }));

    await waitFor(() => expect(api.importLegacyBbsnote).toHaveBeenCalledWith('data', 'admin'));
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
