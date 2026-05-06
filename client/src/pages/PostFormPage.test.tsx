import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import PostFormPage from './PostFormPage';
import { api } from '../api';

vi.mock('../api', () => ({
  DEFAULT_PUBLIC_SETTINGS: {
    config: {
      bbsTitle: 'ThreadForge',
      homePageUrl: '/',
      manualTitle: 'Manual',
      manualBody: '',
      tweetEnabled: true,
      gdgdEnabled: true,
      gdgdLabel: 'gdgd投稿',
    },
  },
  api: {
    publicSettings: vi.fn(),
    createPost: vi.fn(),
  },
}));

describe('PostFormPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(api.publicSettings).mockResolvedValue({
      success: true,
      settings: {
        config: {
          bbsTitle: 'ThreadForge',
          homePageUrl: '/',
          manualTitle: 'Manual',
          manualBody: '',
          tweetEnabled: true,
          gdgdEnabled: true,
          gdgdLabel: 'gdgd投稿',
        },
      },
    });
    vi.mocked(api.createPost).mockResolvedValue({ success: true, message: 'ok' });
  });

  it('does not expose a manual Tweet URL input', async () => {
    render(
      <MemoryRouter>
        <PostFormPage />
      </MemoryRouter>,
    );

    expect(await screen.findByText('通常投稿')).toBeInTheDocument();
    expect(screen.queryByLabelText('Tweet先 URL')).not.toBeInTheDocument();
    expect(screen.queryByPlaceholderText('https://x.com/...')).not.toBeInTheDocument();
  });

  it('does not send tweet_url when creating a post', async () => {
    render(
      <MemoryRouter>
        <PostFormPage />
      </MemoryRouter>,
    );

    fireEvent.change(screen.getByLabelText(/名前/), { target: { value: 'Alice' } });
    fireEvent.change(screen.getByLabelText(/タイトル/), { target: { value: 'Title' } });
    fireEvent.change(screen.getByLabelText(/メッセージ/), { target: { value: 'Body' } });
    fireEvent.change(screen.getByLabelText(/パスワード/), { target: { value: 'pass' } });
    fireEvent.click(screen.getByRole('button', { name: '送信' }));

    await waitFor(() => expect(api.createPost).toHaveBeenCalled());
    expect(api.createPost).toHaveBeenCalledWith(expect.not.objectContaining({ tweet_url: expect.anything() }));
  });

  it('switches the title band to gdgd styling when gdgd is checked', async () => {
    render(
      <MemoryRouter>
        <PostFormPage />
      </MemoryRouter>,
    );

    const formShell = (await screen.findByText('通常投稿')).closest('.post-form-page');
    expect(formShell).not.toHaveClass('post-form-gdgd');

    fireEvent.click(screen.getByLabelText('gdgd投稿'));

    expect(formShell).toHaveClass('post-form-gdgd');
  });

  it('keeps normal or gdgd title color when Tweet OFF is checked', async () => {
    render(
      <MemoryRouter>
        <PostFormPage />
      </MemoryRouter>,
    );

    const formShell = (await screen.findByText('通常投稿')).closest('.post-form-page');
    const title = screen.getByText('通常投稿');

    fireEvent.click(screen.getByLabelText('Tweet OFF'));
    expect(formShell).toHaveClass('post-form-tweet-off');
    expect(title).not.toHaveStyle({ background: '#555d68' });

    fireEvent.click(screen.getByLabelText('gdgd投稿'));
    expect(formShell).toHaveClass('post-form-gdgd');
    expect(formShell).toHaveClass('post-form-tweet-off');
  });
});
