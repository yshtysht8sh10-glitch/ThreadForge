import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import AdminPage from './AdminPage';
import { api } from '../api';

vi.mock('../api', () => ({
  apiBase: () => '/api.php',
  api: {
    listThreads: vi.fn(),
    listDeletedPosts: vi.fn(),
    getSettings: vi.fn(),
    adminDeletePosts: vi.fn(),
    adminCheckIntegrity: vi.fn(),
    importBackup: vi.fn(),
    updateSettings: vi.fn(),
    restorePost: vi.fn(),
  },
}));

const thread = {
  id: 1,
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
  }],
};

describe('AdminPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(api.listThreads).mockResolvedValue([thread as any]);
    vi.mocked(api.listDeletedPosts).mockResolvedValue([]);
    vi.mocked(api.getSettings).mockResolvedValue({
      success: true,
      settings: {
        config: { bbsTitle: 'DotEita Board' },
        skin: { normalFrameColor: '#a23dff' },
      },
    });
    vi.mocked(api.adminDeletePosts).mockResolvedValue({ success: true, message: '2件を削除しました。' });
  });

  it('renders the admin operations', () => {
    render(<AdminPage />);

    expect(screen.getByRole('heading', { name: '管理' })).toBeInTheDocument();
    expect(screen.getByLabelText('管理パスワード')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '投稿管理' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'バックアップ / インポート' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'config.cgi 相当' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'skincfg.cgi 相当' })).toBeInTheDocument();
  });

  it('loads posts and deletes checked items without post passwords', async () => {
    render(<AdminPage />);

    fireEvent.change(screen.getByLabelText('管理パスワード'), { target: { value: 'admin' } });
    fireEvent.click(screen.getByRole('button', { name: '管理データを読み込む' }));

    expect(await screen.findByText('Root')).toBeInTheDocument();
    expect(screen.getByText('返信: Reply')).toBeInTheDocument();

    fireEvent.click(screen.getByLabelText(/No\.1/));
    fireEvent.click(screen.getByLabelText(/No\.2/));
    fireEvent.click(screen.getByRole('button', { name: 'チェックした項目を一括削除' }));

    await waitFor(() => expect(api.adminDeletePosts).toHaveBeenCalledWith(['1', '2'], 'admin'));
  });
});
