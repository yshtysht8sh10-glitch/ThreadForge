import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import BoardAnalyticsPage from './BoardAnalyticsPage';
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
    listBoardAnalyticsPosts: vi.fn(),
  },
}));

const posts = [
  {
    id: 1,
    display_no: 1,
    thread_id: 1,
    parent_id: 0,
    name: 'Alice',
    title: 'First',
    message: 'body',
    image_path: null,
    created_at: '2026-05-01 10:00:00',
    board_reactions: { views: 10, eejanaika: 2, omigoto: 1, goodjob: 0 },
    reply_count: 3,
  },
  {
    id: 2,
    display_no: 2,
    thread_id: 2,
    parent_id: 0,
    name: 'Bob',
    title: 'Second',
    message: 'body',
    image_path: null,
    created_at: '2026-05-02 10:00:00',
    board_reactions: { views: 5, eejanaika: 1, omigoto: 0, goodjob: 1 },
    reply_count: 1,
  },
];

describe('BoardAnalyticsPage', () => {
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
    vi.mocked(api.listBoardAnalyticsPosts).mockResolvedValue(posts as any);
  });

  it('asks anonymous visitors to log in', () => {
    render(
      <MemoryRouter>
        <BoardAnalyticsPage />
      </MemoryRouter>,
    );

    expect(screen.getByText('ログインすると表示できます。')).toBeInTheDocument();
    expect(api.listBoardAnalyticsPosts).not.toHaveBeenCalled();
  });

  it('shows board totals and cumulative chart for logged-in users', async () => {
    authMock.value = {
      token: 'user-token',
      user: { id: 1, login_id: 'alice', display_name: 'Alice', post_password: 'secret', home_url: null, icon_path: null },
      loading: false,
      login: vi.fn(),
      register: vi.fn(),
      updateProfile: vi.fn(),
      logout: vi.fn(),
    };

    render(
      <MemoryRouter>
        <BoardAnalyticsPage />
      </MemoryRouter>,
    );

    await waitFor(() => expect(api.listBoardAnalyticsPosts).toHaveBeenCalled());
    expect(screen.getByText('閲覧数: 15')).toBeInTheDocument();
    expect(screen.getByText('ええじゃ数: 3')).toBeInTheDocument();
    expect(screen.getByText('お美事数: 1')).toBeInTheDocument();
    expect(screen.getByText('いい仕事数: 1')).toBeInTheDocument();
    expect(screen.getByRole('img', { name: '投稿No順の累積折れ線グラフ' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /No\.1/ })).toHaveAttribute('href', '/thread/1');

    fireEvent.change(screen.getByLabelText('内訳'), { target: { value: 'comments' } });

    expect(screen.getByText('4')).toBeInTheDocument();
  });
});
