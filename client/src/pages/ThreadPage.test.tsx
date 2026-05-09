import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
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
  display_no: 1,
  thread_id: 1,
  parent_id: 0,
  name: 'Alice',
  url: null,
  title: 'Thread title',
  message: 'Thread body',
  image_path: null,
  created_at: '2026-05-04 10:00:00',
  tweet_off: false,
  tweet_text: '[DT000000: Thread title]\n作者：Alice\n\nThread body',
  tweet_url: 'https://example.com/tweet',
};

describe('ThreadPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    Element.prototype.scrollIntoView = vi.fn();
    vi.mocked(api.getThread).mockResolvedValue({ thread, replies: [] });
    vi.mocked(api.createPost).mockResolvedValue({ success: true, message: 'ok' });
  });

  it('uses the board list layout for the parent post and all replies', async () => {
    vi.mocked(api.getThread).mockResolvedValue({
      thread,
      replies: [
        { ...thread, id: 2, parent_id: 1, message: 'Reply 1', reply_no: 1 },
        { ...thread, id: 3, parent_id: 1, message: 'Reply 2', reply_no: 2 },
      ],
    });

    renderThreadPage();

    expect(await screen.findByText('[No・1] Thread title')).toBeInTheDocument();
    expect(screen.getByText('Thread body')).toBeInTheDocument();
    expect(screen.getByText('Reply 1')).toBeInTheDocument();
    expect(screen.getByText('Reply 2')).toBeInTheDocument();
    expect(screen.queryByText(/省略されています/)).not.toBeInTheDocument();
  });

  it('does not show tweet controls or tweet text on the thread detail screen', async () => {
    renderThreadPage();

    await screen.findByText('[No・1] Thread title');

    expect(screen.queryByText('Tweet文言')).not.toBeInTheDocument();
    expect(screen.queryByText(/\[DT000000/)).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Tweet先' })).not.toBeInTheDocument();
  });

  it('does not show tweet or image controls in the reply form', async () => {
    renderThreadPage();

    const replyForm = await screen.findByRole('form', { name: '返信フォーム' });

    expect(within(replyForm).queryByLabelText('Tweet OFF')).not.toBeInTheDocument();
    expect(within(replyForm).queryByText(/Tweet文言/)).not.toBeInTheDocument();
    expect(within(replyForm).queryByLabelText(/画像/)).not.toBeInTheDocument();
    expect(replyForm.querySelector('input[type="file"]')).not.toBeInTheDocument();
  });

  it('shows the reply form and eejanaika form side by side on the same screen', async () => {
    renderThreadPage();

    expect(await screen.findByRole('heading', { name: 'コメント' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'ええじゃないか' })).toBeInTheDocument();
    expect(screen.getByRole('form', { name: '返信フォーム' })).toBeInTheDocument();
    expect(screen.getByRole('form', { name: 'ええじゃないかフォーム' })).toBeInTheDocument();
    expect(screen.getAllByText('名前（/30文字）')).toHaveLength(2);
    expect(screen.getByText('本文（/100000文字）')).toBeInTheDocument();
    expect(screen.getByText('※半角英数字8文字まで有効です。')).toBeInTheDocument();
    expect(within(screen.getByRole('form', { name: 'ええじゃないかフォーム' })).getByLabelText('名前')).toHaveAttribute('maxlength', '30');
    expect(within(screen.getByRole('form', { name: 'ええじゃないかフォーム' })).getByLabelText('名前')).toBeRequired();
    expect(screen.getByRole('button', { name: '一覧に戻る' })).toBeInTheDocument();
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

  it('submits replies without tweet or image fields and stays on the thread detail screen', async () => {
    renderThreadPage();

    const replyForm = await screen.findByRole('form', { name: '返信フォーム' });

    fireEvent.change(within(replyForm).getByLabelText('名前'), { target: { value: 'Bob' } });
    fireEvent.change(within(replyForm).getByLabelText('本文'), { target: { value: 'Reply body' } });
    fireEvent.change(within(replyForm).getByLabelText('パスワード'), { target: { value: 'secret' } });
    fireEvent.click(within(replyForm).getByRole('button', { name: '送信' }));

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
    await screen.findByText('/thread/1');
    expect(screen.queryByText('/#post-1')).not.toBeInTheDocument();
  });

  it('returns to the list from the thread detail form area', async () => {
    renderThreadPage();

    await screen.findByText('[No・1] Thread title');
    fireEvent.click(screen.getByRole('button', { name: '一覧に戻る' }));

    await screen.findByText('/');
  });

  it('submits the selected eejanaika comment as a reply while the regular reply form remains available', async () => {
    renderThreadPage('/thread/1?mode=eejanaika');

    const eejanaikaForm = await screen.findByRole('form', { name: 'ええじゃないかフォーム' });

    expect(screen.getByRole('form', { name: '返信フォーム' })).toBeInTheDocument();
    fireEvent.change(within(eejanaikaForm).getByLabelText('名前'), { target: { value: 'Carol' } });
    fireEvent.click(within(eejanaikaForm).getByLabelText('いい仕事してますねぇ'));
    fireEvent.click(within(eejanaikaForm).getByRole('button', { name: '送信' }));

    await waitFor(() => expect(api.createPost).toHaveBeenCalledWith(expect.objectContaining({
      thread_id: 1,
      parent_id: 1,
      name: 'Carol',
      title: 'Re: Thread title',
      message: 'いい仕事してますねぇ',
      password: 'eejanaika',
    })));
    await screen.findByText('/thread/1');
    expect(screen.queryByText('/#post-1')).not.toBeInTheDocument();
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

    expect((await screen.findAllByText('お美事にございまする'))[0].closest('.board-reply-text')).toHaveClass('eejanaika-reply-omigoto');
    expect(screen.getAllByText('いい仕事してますねぇ')[0].closest('.board-reply-text')).toHaveClass('eejanaika-reply-goodjob');
    expect(screen.getAllByText('ええじゃないか')[0].closest('.board-reply-text')).toHaveClass('eejanaika-reply-eejanaika');
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
