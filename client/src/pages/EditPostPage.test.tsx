import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import EditPostPage from './EditPostPage';
import { api } from '../api';
import { useAuth } from '../auth';

vi.mock('../api', () => ({
  DEFAULT_PUBLIC_SETTINGS: {
    config: { tweetEnabled: true, blueskyEnabled: true, mastodonEnabled: false, misskeyEnabled: false, gdgdEnabled: true, gdgdLabel: '特殊投稿' },
  },
  api: {
    getPost: vi.fn(),
    updatePost: vi.fn(),
    deletePost: vi.fn(),
    publicSettings: vi.fn(),
  },
  mediaUrl: (path?: string | null) => path,
}));

vi.mock('../auth', () => ({
  useAuth: vi.fn(),
}));

const replyPost = {
  id: 2,
  thread_id: 1,
  parent_id: 1,
  name: 'Bob',
  url: null,
  title: 'Re: Thread title',
  message: 'Reply body',
  image_path: null,
  created_at: '2026-05-04 10:05:00',
  tweet_off: true,
  gdgd: false,
  tweet_text: null,
  tweet_url: null,
  tweet_like_count: 0,
  tweet_retweet_count: 0,
  tweet_comment_count: 0,
  tweet_impression_count: 0,
};

const threadPost = {
  ...replyPost,
  id: 1,
  thread_id: 1,
  parent_id: 0,
  name: 'Alice',
  title: 'Thread title',
  message: 'Thread body',
  tweet_off: false,
  gdgd: true,
};

describe('EditPostPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    URL.createObjectURL = vi.fn(() => 'blob:preview-image');
    URL.revokeObjectURL = vi.fn();
    vi.mocked(useAuth).mockReturnValue({
      token: '',
      user: null,
      loading: false,
      login: vi.fn(),
      register: vi.fn(),
      updateProfile: vi.fn(),
      logout: vi.fn(),
    });
    vi.mocked(api.getPost).mockResolvedValue(replyPost);
    vi.mocked(api.updatePost).mockResolvedValue({ success: true, message: 'ok' });
    vi.mocked(api.publicSettings).mockResolvedValue({
      success: true,
      settings: { config: { tweetEnabled: true, blueskyEnabled: true, mastodonEnabled: false, misskeyEnabled: false, gdgdEnabled: true, gdgdLabel: '特殊投稿' } },
    } as any);
  });

  it('hides tweet controls and image replacement when editing a reply', async () => {
    renderEditPostPage();

    await screen.findByDisplayValue('Reply body');

    expect(screen.queryByLabelText('SNS転記OFF')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('SNS投稿のプレビュー')).not.toBeInTheDocument();
    expect(screen.queryByLabelText(/画像置換/)).not.toBeInTheDocument();
    expect(document.querySelector('input[type="file"]')).not.toBeInTheDocument();
  });

  it('updates replies without tweet, image, or delete fields', async () => {
    renderEditPostPage();

    await screen.findByDisplayValue('Reply body');

    fireEvent.click(screen.getByRole('button', { name: '更新する' }));

    await waitFor(() => expect(api.updatePost).toHaveBeenCalled());
    expect(screen.queryByRole('button', { name: '削除する' })).not.toBeInTheDocument();
    const formData = vi.mocked(api.updatePost).mock.calls[0][0] as FormData;
    expect(formData.has('tweet_off')).toBe(false);
    expect(formData.has('gdgd')).toBe(false);
    expect(formData.has('tweet_url')).toBe(false);
    expect(formData.has('tweet_like_count')).toBe(false);
    expect(formData.has('tweet_retweet_count')).toBe(false);
    expect(formData.has('tweet_comment_count')).toBe(false);
    expect(formData.has('tweet_impression_count')).toBe(false);
    expect(formData.has('file')).toBe(false);
  });

  it('hides social transfer controls and warns that SNS posts are not edited', async () => {
    vi.mocked(api.getPost).mockResolvedValue(threadPost);
    renderEditPostPage('1');

    await screen.findByDisplayValue('Thread body');
    expect(screen.queryByLabelText('SNS転記OFF')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('SNS投稿のプレビュー')).not.toBeInTheDocument();
    expect(screen.getByText(/投稿内容を編集してもSNS側に転記された内容/)).toBeInTheDocument();
    expect(screen.getByText(/もしどうしても反映させたい場合/)).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: '更新する' }));

    await waitFor(() => expect(api.updatePost).toHaveBeenCalled());
    const formData = vi.mocked(api.updatePost).mock.calls[0][0] as FormData;
    expect(formData.has('tweet_off')).toBe(false);
    expect(formData.get('gdgd')).toBe('1');
  });

  it('shows the current image and swaps the preview after selecting a file', async () => {
    vi.mocked(api.getPost).mockResolvedValue({ ...threadPost, image_path: '/storage/data/1.png' });
    renderEditPostPage('1');

    expect(await screen.findByRole('img', { name: 'Thread title' })).toHaveAttribute('src', '/storage/data/1.png');

    const file = new File(['new-image'], 'new.png', { type: 'image/png' });
    fireEvent.change(document.querySelector('input[type="file"]') as HTMLInputElement, { target: { files: [file] } });

    await waitFor(() => expect(screen.getByRole('img', { name: 'Thread title' })).toHaveAttribute('src', 'blob:preview-image'));
  });

  it('uses a short note field instead of the full name while logged in', async () => {
    vi.mocked(api.getPost).mockResolvedValue({ ...threadPost, name: 'Yes@作業中' });
    vi.mocked(useAuth).mockReturnValue({
      token: 'token',
      user: {
        id: 1,
        login_id: 'yes',
        display_name: 'Yes',
        post_password: 'secret',
        home_url: 'https://example.com',
        icon_path: null,
      },
      loading: false,
      login: vi.fn(),
      register: vi.fn(),
      updateProfile: vi.fn(),
      logout: vi.fn(),
    });
    renderEditPostPage('1');

    expect(await screen.findByLabelText(/ひとこと/)).toHaveValue('作業中');
    expect(screen.getByText('ひとこと（任意 / 26文字まで）')).toBeInTheDocument();
    expect(screen.queryByLabelText('名前')).not.toBeInTheDocument();
    expect(screen.getByText(/NAME: Yes@作業中/)).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText(/ひとこと/), { target: { value: '修正後' } });
    fireEvent.click(screen.getByRole('button', { name: '更新する' }));

    await waitFor(() => expect(api.updatePost).toHaveBeenCalled());
    const formData = vi.mocked(api.updatePost).mock.calls[0][0] as FormData;
    expect(formData.get('name')).toBe('Yes@修正後');
    expect(formData.get('auth_token')).toBe('token');
  });
});

function renderEditPostPage(id = '2') {
  render(
    <MemoryRouter initialEntries={[{ pathname: `/edit/${id}`, state: { password: 'secret' } }]}>
      <Routes>
        <Route path="/edit/:id" element={<EditPostPage />} />
      </Routes>
    </MemoryRouter>,
  );
}
