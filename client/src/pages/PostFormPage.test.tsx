import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
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
      blueskyEnabled: true,
      mastodonEnabled: false,
      misskeyEnabled: false,
      gdgdEnabled: true,
      gdgdLabel: 'gdgd投稿',
    },
  },
  api: {
    publicSettings: vi.fn(),
    createPost: vi.fn(),
  },
}));

const defaultSettings = {
  config: {
    bbsTitle: 'ThreadForge',
    homePageUrl: '/',
    manualTitle: 'Manual',
    manualBody: '',
    tweetEnabled: true,
    blueskyEnabled: true,
    mastodonEnabled: false,
    misskeyEnabled: false,
    gdgdEnabled: true,
    gdgdLabel: 'gdgd投稿',
  },
};

describe('PostFormPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(api.publicSettings).mockResolvedValue({ success: true, settings: defaultSettings });
    vi.mocked(api.createPost).mockResolvedValue({ success: true, message: 'ok' });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('does not expose a manual social URL input', async () => {
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

    fireEvent.change(await screen.findByLabelText(/名前/), { target: { value: 'Alice' } });
    fireEvent.change(screen.getByLabelText(/タイトル/), { target: { value: 'Title' } });
    fireEvent.change(screen.getByLabelText(/メッセージ/), { target: { value: 'Body' } });
    fireEvent.change(screen.getByLabelText(/パスワード/), { target: { value: 'pass' } });
    fireEvent.click(screen.getByRole('button', { name: '送信' }));

    await waitFor(() => expect(api.createPost).toHaveBeenCalled());
    expect(api.createPost).toHaveBeenCalledWith(expect.not.objectContaining({ tweet_url: expect.anything() }));
  });

  it('uses SNS transfer OFF as the combined posting switch', async () => {
    render(
      <MemoryRouter>
        <PostFormPage />
      </MemoryRouter>,
    );

    fireEvent.change(await screen.findByLabelText(/名前/), { target: { value: 'Alice' } });
    fireEvent.change(screen.getByLabelText(/タイトル/), { target: { value: 'Title' } });
    fireEvent.change(screen.getByLabelText(/メッセージ/), { target: { value: 'Body' } });
    fireEvent.change(screen.getByLabelText(/パスワード/), { target: { value: 'pass' } });
    fireEvent.click(screen.getByLabelText('SNS転記OFF'));
    fireEvent.click(screen.getByRole('button', { name: '送信' }));

    await waitFor(() => expect(api.createPost).toHaveBeenCalled());
    expect(api.createPost).toHaveBeenCalledWith(expect.objectContaining({ tweet_off: true }));
  });

  it('shows transfer previews for enabled social platforms', async () => {
    render(
      <MemoryRouter>
        <PostFormPage />
      </MemoryRouter>,
    );

    expect(await screen.findByLabelText('SNS投稿のプレビュー')).toBeInTheDocument();
    expect(screen.getByText('SNS投稿のプレビュー')).toBeInTheDocument();
    expect(screen.getByText('※この項目は編集できません。')).toBeInTheDocument();
    expect(screen.getByText('X')).toBeInTheDocument();
    expect(screen.getByText('Bluesky')).toBeInTheDocument();
    expect(screen.queryByText('Mastodon')).not.toBeInTheDocument();
    expect(screen.queryByText('Misskey')).not.toBeInTheDocument();
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

  it('does not change frame styling when SNS transfer is off', async () => {
    render(
      <MemoryRouter>
        <PostFormPage />
      </MemoryRouter>,
    );

    const formShell = (await screen.findByText('通常投稿')).closest('.post-form-page');

    fireEvent.click(screen.getByLabelText('SNS転記OFF'));
    expect(formShell).not.toHaveClass('post-form-tweet-off');
    expect(screen.queryByLabelText('SNS投稿のプレビュー')).not.toBeInTheDocument();
  });

  it('confirms before closing when the form has input', async () => {
    const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);

    render(
      <MemoryRouter>
        <PostFormPage />
      </MemoryRouter>,
    );

    fireEvent.change(await screen.findByLabelText(/名前/), { target: { value: 'Alice' } });
    fireEvent.click(screen.getByRole('button', { name: '投稿フォームを閉じる' }));

    expect(confirm).toHaveBeenCalledWith('入力内容は破棄されます。一覧画面に戻りますか？');
  });
});
