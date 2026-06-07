import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import LoginPage from './LoginPage';
import { api } from '../api';

const authMock = vi.hoisted(() => ({
  value: {
    token: '',
    user: null as any,
    loading: false,
    login: vi.fn(),
    ssoLogin: vi.fn(),
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
    publicSettings: vi.fn(),
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
      ssoLogin: vi.fn(),
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
    vi.mocked(api.publicSettings).mockResolvedValue({
      success: true,
      settings: {
        config: {
          bbsTitle: 'ThreadForge',
          homePageUrl: '/',
          manualTitle: 'ThreadForge',
          manualBody: '',
          tweetEnabled: false,
          blueskyEnabled: false,
          mastodonEnabled: false,
          misskeyEnabled: false,
          gdgdEnabled: false,
          gdgdLabel: '特殊投稿',
          eejanaikaOmigotoText: 'お美事にございまする',
          eejanaikaOmigotoColor: '#ff72ff',
          eejanaikaGoodjobText: 'いい仕事してますねぇ',
          eejanaikaGoodjobColor: '#27a8ff',
          eejanaikaEejanaikaText: 'ええじゃないか',
          eejanaikaEejanaikaColor: '#fff200',
          socialHashtags: '#art',
          allowedImageTypes: ['gif', 'png'],
          ssoEnabled: false,
        },
      },
    });
  });

  it('hides local registration when SSO is enabled', async () => {
    vi.mocked(api.publicSettings).mockResolvedValueOnce({
      success: true,
      settings: {
        config: {
          bbsTitle: 'ThreadForge',
          homePageUrl: '/',
          manualTitle: 'ThreadForge',
          manualBody: '',
          tweetEnabled: false,
          blueskyEnabled: false,
          mastodonEnabled: false,
          misskeyEnabled: false,
          gdgdEnabled: false,
          gdgdLabel: '特殊投稿',
          eejanaikaOmigotoText: 'お美事にございまする',
          eejanaikaOmigotoColor: '#ff72ff',
          eejanaikaGoodjobText: 'いい仕事してますねぇ',
          eejanaikaGoodjobColor: '#27a8ff',
          eejanaikaEejanaikaText: 'ええじゃないか',
          eejanaikaEejanaikaColor: '#fff200',
          socialHashtags: '#art',
          allowedImageTypes: ['gif', 'png'],
          ssoEnabled: true,
        },
      },
    });

    render(
      <MemoryRouter>
        <LoginPage />
      </MemoryRouter>,
    );

    expect(await screen.findByText('SSOログインが有効です。アカウントの新規作成は親サイト側で行ってください。')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: '新規作成' })).not.toBeInTheDocument();
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

  it('shows the selected icon immediately while registering', async () => {
    const icon = new File(['icon'], 'icon.png', { type: 'image/png' });
    const createObjectUrl = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:selected-icon');
    const revokeObjectUrl = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);

    const { unmount } = render(
      <MemoryRouter>
        <LoginPage />
      </MemoryRouter>,
    );

    fireEvent.click(screen.getByRole('button', { name: '新規作成' }));
    fireEvent.change(screen.getByLabelText('アイコン'), { target: { files: [icon] } });

    expect(await screen.findByAltText('選択中のアイコン')).toHaveAttribute('src', 'blob:selected-icon');
    unmount();
    expect(createObjectUrl).toHaveBeenCalledWith(icon);
    expect(revokeObjectUrl).toHaveBeenCalledWith('blob:selected-icon');
  });

  it('shows a newly selected icon immediately while editing the profile', async () => {
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
      ssoLogin: vi.fn(),
      register: vi.fn(),
      updateProfile: vi.fn(),
      logout: vi.fn(),
    };
    vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:replacement-icon');
    vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);

    render(
      <MemoryRouter>
        <LoginPage />
      </MemoryRouter>,
    );

    const icon = new File(['replacement'], 'replacement.png', { type: 'image/png' });
    fireEvent.change(screen.getByLabelText('アイコン'), { target: { files: [icon] } });

    expect(await screen.findByAltText('現在のアイコン')).toHaveAttribute('src', 'blob:replacement-icon');
  });

  it('moves to the thread list after SSO login', async () => {
    authMock.value.ssoLogin = vi.fn().mockResolvedValue(undefined);
    window.history.replaceState(null, '', '/#/login?sso=payload.signature');

    render(
      <MemoryRouter initialEntries={['/login']}>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/" element={<LocationView />} />
        </Routes>
      </MemoryRouter>,
    );

    await waitFor(() => expect(authMock.value.ssoLogin).toHaveBeenCalledWith('payload.signature'));
    expect(await screen.findByTestId('location')).toHaveTextContent('/');
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

function LocationView() {
  const location = useLocation();
  return <div data-testid="location">{location.pathname}</div>;
}
