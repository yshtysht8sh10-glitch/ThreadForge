import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import LoginPage from './LoginPage';
import { api } from '../api';

const authMock = vi.hoisted(() => ({
  value: {
    token: '',
    user: null as any,
    loading: false,
    login: vi.fn(),
    register: vi.fn(),
    updateProfile: vi.fn(),
    logout: vi.fn(),
  },
}));

vi.mock('../auth', () => ({
  useAuth: () => authMock.value,
}));

vi.mock('../api', () => ({
  api: {
    checkLoginId: vi.fn(),
    listUserDashboard: vi.fn(),
    search: vi.fn(),
    claimUserPost: vi.fn(),
    unclaimUserPost: vi.fn(),
    deletePost: vi.fn(),
  },
  mediaUrl: (path?: string | null) => path,
}));

const ownPost = {
  id: 10,
  display_no: 90,
  thread_id: 10,
  parent_id: 0,
  name: 'Alice',
  title: 'Owned work',
  message: 'body',
  image_path: '/storage/data/10.png',
  created_at: '2026-05-01 10:00:00',
  can_manage: true,
  board_reactions: { views: 8, eejanaika: 2, omigoto: 1, goodjob: 0 },
  reply_count: 1,
};

const claimedPost = {
  id: 11,
  display_no: 91,
  thread_id: 11,
  parent_id: 0,
  name: 'Bob',
  title: 'Claimed work',
  message: 'body',
  image_path: null,
  created_at: '2026-05-02 10:00:00',
  can_manage: false,
  claimed_by_user: true,
  board_reactions: { views: 2, eejanaika: 0, omigoto: 0, goodjob: 0 },
  reply_count: 0,
};

describe('LoginPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    authMock.value = {
      token: '',
      user: null,
      loading: false,
      login: vi.fn(),
      register: vi.fn(),
      updateProfile: vi.fn(),
      logout: vi.fn(),
    };
    vi.mocked(api.checkLoginId).mockResolvedValue({ success: true, available: true });
    vi.mocked(api.listUserDashboard).mockResolvedValue({ success: true, posts: [], analytics_posts: [] });
    vi.mocked(api.search).mockResolvedValue([]);
    vi.mocked(api.claimUserPost).mockResolvedValue({ success: true, message: '自分の作品として登録しました。' });
    vi.mocked(api.unclaimUserPost).mockResolvedValue({ success: true, message: '登録を解除しました。' });
    vi.mocked(api.deletePost).mockResolvedValue({ success: true, message: '削除しました。' });
  });

  it('checks duplicate IDs before registration and submits the new user profile', async () => {
    render(
      <MemoryRouter>
        <LoginPage />
      </MemoryRouter>,
    );

    fireEvent.click(screen.getByRole('button', { name: '新規作成' }));
    fireEvent.change(screen.getByLabelText(/^ID/), { target: { value: 'alice' } });
    fireEvent.blur(screen.getByLabelText(/^ID/));

    expect(await screen.findByText('このIDは使用できます。')).toBeInTheDocument();
    const loginPasswordInput = screen.getByLabelText(/^ログインパスワード\*/);
    const loginPasswordConfirmInput = screen.getByLabelText(/^ログインパスワード（確認）/);
    const postPasswordInput = screen.getByLabelText(/^投稿パスワード\*/);
    const postPasswordConfirmInput = screen.getByLabelText(/^投稿パスワード（確認）/);
    expect(loginPasswordInput).toHaveAttribute('type', 'password');
    expect(postPasswordInput).toHaveAttribute('type', 'password');
    fireEvent.change(loginPasswordInput, { target: { value: 'login-secret' } });
    fireEvent.change(loginPasswordConfirmInput, { target: { value: 'login-secret' } });
    fireEvent.click(screen.getByLabelText('ログインパスワードを表示'));
    expect(loginPasswordInput).toHaveAttribute('type', 'text');
    fireEvent.change(screen.getByLabelText(/^名前（\/30文字）/), { target: { value: 'Alice' } });
    fireEvent.change(postPasswordInput, { target: { value: 'postpass' } });
    fireEvent.change(postPasswordConfirmInput, { target: { value: 'postpass' } });
    fireEvent.click(screen.getByLabelText('投稿パスワードを表示'));
    expect(postPasswordInput).toHaveAttribute('type', 'text');
    fireEvent.change(screen.getByLabelText('URL / HOME'), { target: { value: 'https://example.com' } });
    fireEvent.click(screen.getByRole('button', { name: '作成してログイン' }));

    await waitFor(() => expect(authMock.value.register).toHaveBeenCalledWith(expect.objectContaining({
      login_id: 'alice',
      password: 'login-secret',
      display_name: 'Alice',
      post_password: 'postpass',
      home_url: 'https://example.com',
    })));
  });

  it('shows user settings, analytics, own posts, claim search, and claim actions while logged in', async () => {
    authMock.value = {
      token: 'user-token',
      user: {
        id: 7,
        login_id: 'alice-id',
        display_name: 'Alice',
        post_password: 'postpass',
        home_url: 'https://example.com',
        icon_path: '/storage/data/user_7.png',
      },
      loading: false,
      login: vi.fn(),
      register: vi.fn(),
      updateProfile: vi.fn(),
      logout: vi.fn(),
    };
    vi.mocked(api.listUserDashboard).mockResolvedValue({
      success: true,
      posts: [ownPost, claimedPost] as any,
      analytics_posts: [ownPost, claimedPost] as any,
    });
    const firstPage = Array.from({ length: 50 }, (_, index) => ({
      id: 1000 + index,
      display_no: 1000 + index,
      thread_id: 1000 + index,
      parent_id: 0,
      name: 'Candidate',
      title: `Candidate work ${index}`,
      message: 'needle body',
      image_path: null,
      created_at: '2026-05-03 10:00:00',
    }));
    vi.mocked(api.search)
      .mockResolvedValueOnce(firstPage as any)
      .mockResolvedValueOnce([
      {
        id: 40,
        display_no: 40,
        thread_id: 40,
        parent_id: 0,
        name: 'Candidate',
        title: 'Candidate work',
        message: 'needle body',
        image_path: '/storage/data/40.png',
        created_at: '2026-05-03 10:00:00',
      },
    ] as any)
      .mockResolvedValueOnce([] as any);

    render(
      <MemoryRouter>
        <LoginPage />
      </MemoryRouter>,
    );

    expect(await screen.findByRole('heading', { name: 'ユーザー設定' })).toBeInTheDocument();
    expect(api.listUserDashboard).toHaveBeenCalledWith('user-token');
    expect(screen.getByDisplayValue('Alice')).toHaveAttribute('maxlength', '30');
    expect(screen.getByRole('heading', { name: 'アナリティクス' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '登録作品数' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '総計' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '各投稿内訳' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '推移（累積）' })).toBeInTheDocument();

    const postsSection = screen.getByRole('heading', { name: '自分の投稿/返信' }).closest('section') as HTMLElement;
    expect(within(postsSection).getAllByRole('link', { name: '表示' })[0]).toHaveAttribute('href', '/?target=10#post-10');
    expect(within(postsSection).getByText('自分の作品として紐づけ済み')).toBeInTheDocument();

    const claimSection = screen.getByRole('heading', { name: '自分の作品として登録' }).closest('section') as HTMLElement;
    fireEvent.change(within(claimSection).getByLabelText('キーワード検索'), { target: { value: 'needle' } });
    fireEvent.change(within(claimSection).getByLabelText('検索対象'), { target: { value: 'title' } });
    fireEvent.click(within(claimSection).getByRole('button', { name: '検索' }));

    await waitFor(() => expect(api.search).toHaveBeenCalledWith('needle', 'title', 1, 50, 'posts', 'oldest'));
    await waitFor(() => expect(api.search).toHaveBeenCalledWith('needle', 'title', 2, 50, 'posts', 'oldest'));
    expect(within(claimSection).queryByLabelText('返信')).not.toBeInTheDocument();
    const candidate = await screen.findByText('No.40 Candidate work');
    const candidateRow = candidate.closest('.account-claim-result') as HTMLElement;
    expect(candidateRow.querySelector('img')).toHaveAttribute('src', '/storage/data/40.png');
    fireEvent.click(within(candidateRow).getByRole('button', { name: '登録' }));

    await waitFor(() => expect(api.claimUserPost).toHaveBeenCalledWith('user-token', '40'));
  });
});
