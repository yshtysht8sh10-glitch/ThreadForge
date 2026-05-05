import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import EditPostPage from './EditPostPage';
import { api } from '../api';

vi.mock('../api', () => ({
  api: {
    getPost: vi.fn(),
    updatePost: vi.fn(),
    deletePost: vi.fn(),
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

describe('EditPostPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(api.getPost).mockResolvedValue(replyPost);
    vi.mocked(api.updatePost).mockResolvedValue({ success: true, message: 'ok' });
  });

  it('hides tweet controls and image replacement when editing a reply', async () => {
    renderEditPostPage();

    await screen.findByDisplayValue('Reply body');

    expect(screen.queryByLabelText('Tweet OFF')).not.toBeInTheDocument();
    expect(screen.queryByText(/Tweet文言/)).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Tweet先 URL')).not.toBeInTheDocument();
    expect(screen.queryByLabelText(/画像置換/)).not.toBeInTheDocument();
    expect(document.querySelector('input[type="file"]')).not.toBeInTheDocument();
  });

  it('updates replies without tweet, image, or delete fields', async () => {
    renderEditPostPage();

    await screen.findByDisplayValue('Reply body');

    fireEvent.change(screen.getByLabelText('パスワード'), { target: { value: 'secret' } });
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
});

function renderEditPostPage() {
  render(
    <MemoryRouter initialEntries={['/edit/2']}>
      <Routes>
        <Route path="/edit/:id" element={<EditPostPage />} />
      </Routes>
    </MemoryRouter>,
  );
}
