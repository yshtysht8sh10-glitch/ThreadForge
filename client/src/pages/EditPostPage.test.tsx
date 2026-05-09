import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import EditPostPage from './EditPostPage';
import { api } from '../api';

vi.mock('../api', () => ({
  DEFAULT_PUBLIC_SETTINGS: {
    config: { tweetEnabled: true, blueskyEnabled: true, mastodonEnabled: false, misskeyEnabled: false, gdgdEnabled: true, gdgdLabel: 'gdgd投稿' },
  },
  api: {
    getPost: vi.fn(),
    updatePost: vi.fn(),
    deletePost: vi.fn(),
    publicSettings: vi.fn(),
  },
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
    vi.mocked(api.getPost).mockResolvedValue(replyPost);
    vi.mocked(api.updatePost).mockResolvedValue({ success: true, message: 'ok' });
    vi.mocked(api.publicSettings).mockResolvedValue({
      success: true,
      settings: { config: { tweetEnabled: true, blueskyEnabled: true, mastodonEnabled: false, misskeyEnabled: false, gdgdEnabled: true, gdgdLabel: 'gdgd投稿' } },
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

  it('shows social previews and sends the transfer switch when editing a thread', async () => {
    vi.mocked(api.getPost).mockResolvedValue(threadPost);
    renderEditPostPage('1');

    expect(await screen.findByLabelText('SNS投稿のプレビュー')).toBeInTheDocument();
    expect(screen.getByText('SNS投稿のプレビュー')).toBeInTheDocument();
    expect(screen.getByText('※この項目は編集できません。')).toBeInTheDocument();
    expect(screen.getByText('X')).toBeInTheDocument();
    expect(screen.getByText('Bluesky')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: '更新する' }));

    await waitFor(() => expect(api.updatePost).toHaveBeenCalled());
    const formData = vi.mocked(api.updatePost).mock.calls[0][0] as FormData;
    expect(formData.get('tweet_off')).toBe('0');
    expect(formData.get('gdgd')).toBe('1');
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
