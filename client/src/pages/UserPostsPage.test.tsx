import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import UserPostsPage from './UserPostsPage';
import { api } from '../api';

vi.mock('../api', () => ({
  api: {
    listUserPosts: vi.fn(),
    publicSettings: vi.fn(),
    recordPostView: vi.fn(),
  },
  mediaUrl: (path?: string | null) => path,
}));

const publicSettings = {
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
      gdgdEnabled: true,
      gdgdLabel: '特殊投稿',
      eejanaikaOmigotoText: 'お美事にございまする',
      eejanaikaOmigotoColor: '#ff72ff',
      eejanaikaGoodjobText: 'いい仕事してますねぇ',
      eejanaikaGoodjobColor: '#27a8ff',
      eejanaikaEejanaikaText: 'ええじゃないか',
      eejanaikaEejanaikaColor: '#fff200',
      socialHashtags: '#ドット絵 #pixelart',
      allowedImageTypes: ['gif', 'png', 'jpeg', 'jpg', 'bmp'],
    },
  },
};

describe('UserPostsPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(api.publicSettings).mockResolvedValue(publicSettings as any);
    vi.mocked(api.recordPostView).mockResolvedValue({ success: true, view_count: 1 });
    vi.mocked(api.listUserPosts).mockResolvedValue({
      success: true,
      user: {
        id: 7,
        login_id: 'alice',
        display_name: 'Alice',
        post_password: '',
        home_url: 'https://example.com',
        icon_path: '/storage/data/user_7.png',
      },
      posts: [
        {
          id: 10,
          display_no: 90,
          thread_id: 10,
          parent_id: 0,
          name: 'Alice',
          title: 'Owned work',
          message: 'body',
          image_path: '/storage/data/10.png',
          created_at: '2026-05-01 10:00:00',
          user_id: 7,
          user_icon_path: '/storage/data/user_7.png',
          user_display_name: 'Alice',
          replies: [
            {
              id: 11,
              thread_id: 10,
              parent_id: 10,
              name: 'Reply',
              title: 'Re: Owned work',
              message: 'hidden reply',
              image_path: null,
              created_at: '2026-05-01 11:00:00',
              reply_no: 1,
            },
          ],
        },
      ],
    } as any);
  });

  it('loads the selected user profile and shows only that user work list', async () => {
    render(
      <MemoryRouter initialEntries={['/user/7']}>
        <Routes>
          <Route path="/user/:id" element={<UserPostsPage />} />
        </Routes>
      </MemoryRouter>,
    );

    expect(await screen.findByText('Alice の作品')).toBeInTheDocument();
    expect(api.listUserPosts).toHaveBeenCalledWith('7');
    const heading = screen.getByText('Alice の作品').closest('h1') as HTMLElement;
    expect(heading.querySelector('img')).toHaveAttribute('src', '/storage/data/user_7.png');
    expect(screen.getByRole('link', { name: '一覧に戻る' })).toHaveAttribute('href', '/');
    expect(screen.getByText('[No・90] Owned work')).toBeInTheDocument();
    expect(screen.queryByText('hidden reply')).not.toBeInTheDocument();
    expect(screen.queryByText('このユーザーの作品を見ますか？')).not.toBeInTheDocument();
  });
});
