import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import ThreadPage from './ThreadPage';
import { api } from '../api';

vi.mock('../api', () => ({
  api: {
    getThread: vi.fn(),
    createPost: vi.fn(),
  },
  mediaUrl: (path?: string | null) => path,
}));

const thread = {
  id: 1,
  thread_id: 1,
  parent_id: 0,
  name: 'Alice',
  url: null,
  title: 'Thread title',
  message: 'Thread body',
  image_path: null,
  created_at: '2026-05-04 10:00:00',
  tweet_off: false,
  tweet_text: '[DT000000: Thread title]\n作者： Alice\n\nThread body',
  tweet_url: 'https://example.com/tweet',
  tweet_like_count: 0,
  tweet_retweet_count: 0,
  tweet_comment_count: 0,
  tweet_impression_count: 0,
};

describe('ThreadPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    Element.prototype.scrollIntoView = vi.fn();
    vi.mocked(api.getThread).mockResolvedValue({ thread, replies: [] });
    vi.mocked(api.createPost).mockResolvedValue({ success: true, message: 'ok' });
  });

  it('does not show tweet controls in the reply form', async () => {
    renderThreadPage();

    await screen.findByRole('heading', { name: '返信を書く' });

    expect(screen.queryByLabelText('Tweet OFF')).not.toBeInTheDocument();
    expect(screen.queryByText(/Tweet文言/)).not.toBeInTheDocument();
  });

  it('does not show tweet text on the thread detail screen', async () => {
    renderThreadPage();

    await screen.findByRole('heading', { name: 'Thread title' });

    expect(screen.queryByText('Tweet文言')).not.toBeInTheDocument();
    expect(screen.queryByText(/\[DT000000/)).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Tweet先' })).not.toBeInTheDocument();
  });

  it('does not show image controls in the reply form', async () => {
    renderThreadPage();

    await screen.findByRole('heading', { name: '返信を書く' });

    expect(screen.queryByLabelText(/画像/)).not.toBeInTheDocument();
    expect(document.querySelector('input[type="file"]')).not.toBeInTheDocument();
  });

  it('does not show the eejanaika form on the regular comment screen', async () => {
    renderThreadPage();

    await screen.findByRole('heading', { name: '返信を書く' });

    expect(screen.queryByText('+ No.1へのええじゃないか +')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('お美事にございまする')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('いい仕事してますねぇ')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('ええじゃないか')).not.toBeInTheDocument();
  });

  it('does not render images attached to replies', async () => {
    vi.mocked(api.getThread).mockResolvedValue({
      thread,
      replies: [{
        ...thread,
        id: 2,
        thread_id: 1,
        parent_id: 1,
        title: 'Re: Thread title',
        message: 'Reply with hidden image',
        image_path: '/storage/data/2.png',
      }],
    });

    renderThreadPage();

    await screen.findByText('Reply with hidden image');

    expect(screen.queryByRole('img', { name: '返信画像' })).not.toBeInTheDocument();
    expect(document.querySelector('img[src="/storage/data/2.png"]')).not.toBeInTheDocument();
  });

  it('submits replies without tweet or image fields and returns to the parent post on top', async () => {
    renderThreadPage();

    await screen.findByRole('heading', { name: '返信を書く' });

    fireEvent.change(screen.getByLabelText('名前'), { target: { value: 'Bob' } });
    fireEvent.change(screen.getByLabelText('本文'), { target: { value: 'Reply body' } });
    fireEvent.change(screen.getByLabelText('パスワード'), { target: { value: 'secret' } });
    fireEvent.click(screen.getByRole('button', { name: '返信を投稿' }));

    await waitFor(() => expect(api.createPost).toHaveBeenCalled());
    expect(api.createPost).toHaveBeenCalledWith(expect.not.objectContaining({ tweet_off: expect.anything() }));
    expect(api.createPost).toHaveBeenCalledWith(expect.not.objectContaining({ file: expect.anything() }));
    expect(api.createPost).toHaveBeenCalledWith(expect.objectContaining({
      thread_id: 1,
      parent_id: 1,
      name: 'Bob',
      message: 'Reply body',
      password: 'secret',
    }));
    await screen.findByText('/#post-1');
  });

  it('submits the selected eejanaika comment as a reply and hides the regular reply form', async () => {
    renderThreadPage('/thread/1?mode=eejanaika');

    await screen.findByText('+ No.1へのええじゃないか +');

    expect(screen.queryByRole('heading', { name: '返信を書く' })).not.toBeInTheDocument();
    fireEvent.change(screen.getByLabelText('名前 (_/30文字)'), { target: { value: 'Carol' } });
    fireEvent.click(screen.getByLabelText('いい仕事してますねぇ'));
    fireEvent.click(screen.getByRole('button', { name: '送 信' }));

    await waitFor(() => expect(api.createPost).toHaveBeenCalledWith(expect.objectContaining({
      thread_id: 1,
      parent_id: 1,
      name: 'Carol',
      title: 'Re: Thread title',
      message: 'いい仕事してますねぇ',
      password: 'eejanaika',
    })));
    await screen.findByText('/#post-1');
  });

  it('colors eejanaika replies by selected message', async () => {
    vi.mocked(api.getThread).mockResolvedValue({
      thread,
      replies: [
        { ...thread, id: 2, thread_id: 1, parent_id: 1, message: 'お美事にございまする' },
        { ...thread, id: 3, thread_id: 1, parent_id: 1, message: 'いい仕事してますねぇ' },
        { ...thread, id: 4, thread_id: 1, parent_id: 1, message: 'ええじゃないか' },
      ],
    });

    renderThreadPage();

    expect((await screen.findAllByText('お美事にございまする'))[0].closest('p')).toHaveClass('eejanaika-reply-omigoto');
    expect(screen.getAllByText('いい仕事してますねぇ')[0].closest('p')).toHaveClass('eejanaika-reply-goodjob');
    expect(screen.getAllByText('ええじゃないか')[0].closest('p')).toHaveClass('eejanaika-reply-eejanaika');
  });
});

function LocationProbe() {
  const location = useLocation();
  return <div>{location.pathname + location.hash}</div>;
}

function renderThreadPage(initialEntry = '/thread/1') {
  render(
    <MemoryRouter initialEntries={[initialEntry]}>
      <LocationProbe />
      <Routes>
        <Route path="/" element={<div>Top</div>} />
        <Route path="/thread/:id" element={<ThreadPage />} />
      </Routes>
    </MemoryRouter>,
  );
}
