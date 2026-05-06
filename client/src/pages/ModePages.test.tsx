import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import DeleteModePage from './DeleteModePage';
import EditModePage from './EditModePage';
import { api } from '../api';

vi.mock('../api', () => ({
  api: {
    listThreads: vi.fn(),
    deletePost: vi.fn(),
    publicSettings: vi.fn(),
  },
  mediaUrl: (path?: string | null) => path,
}));

const threads = [{
  id: 7,
  display_no: 7,
  thread_id: 7,
  parent_id: 0,
  name: 'Alice',
  url: null,
  title: '投稿タイトル',
  message: '投稿本文',
  image_path: null,
  created_at: '2026-05-05 02:00:00',
  gdgd: false,
  tweet_off: false,
  tweet_text: null,
  tweet_url: null,
  replies: [{
    id: 8,
    thread_id: 7,
    parent_id: 7,
    name: 'Bob',
    url: null,
    title: 'Re: 投稿タイトル',
    message: 'ええじゃないか',
    image_path: null,
    created_at: '2026-05-05 02:05:00',
    gdgd: false,
    tweet_off: true,
    tweet_text: null,
    tweet_url: null,
    reply_no: 1,
  }],
  reply_count: 1,
}];

describe('mode pages', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(api.listThreads).mockResolvedValue(threads as any);
    vi.mocked(api.deletePost).mockResolvedValue({ success: true, message: 'ok' });
  });

  it('shows posts and replies with checkboxes on the delete mode page', async () => {
    render(
      <MemoryRouter>
        <DeleteModePage />
      </MemoryRouter>,
    );

    expect(await screen.findByRole('link', { name: /\[No・7\]/ })).toBeInTheDocument();
    expect(screen.getByText('投稿本文')).toBeInTheDocument();
    expect(screen.getByText('ええじゃないか').closest('.board-reply-text')).toHaveClass('eejanaika-reply-eejanaika');
    expect(screen.getByLabelText('No.7 を選択')).toBeInTheDocument();
    expect(screen.getByLabelText('返信No.7-1 を選択')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /^削除$/ })).not.toBeInTheDocument();
  });

  it('deletes the checked item using the shared password field', async () => {
    render(
      <MemoryRouter>
        <DeleteModePage />
      </MemoryRouter>,
    );

    await screen.findByRole('link', { name: /\[No・7\]/ });
    fireEvent.click(screen.getByLabelText('返信No.7-1 を選択'));
    fireEvent.change(screen.getByLabelText('パスワード'), { target: { value: 'secret' } });
    fireEvent.click(screen.getByRole('button', { name: 'チェックした項目を削除する' }));

    await waitFor(() => expect(api.deletePost).toHaveBeenCalledWith('8', 'secret'));
    expect(api.deletePost).toHaveBeenCalledTimes(1);
  });

  it('shows posts and replies with checkboxes on the edit mode page', async () => {
    renderEditMode();

    expect(await screen.findByRole('link', { name: /\[No・7\]/ })).toBeInTheDocument();
    expect(screen.getByText('ええじゃないか').closest('.board-reply-text')).toHaveClass('eejanaika-reply-eejanaika');
    expect(screen.getByLabelText('No.7 を選択')).toBeInTheDocument();
    expect(screen.getByLabelText('返信No.7-1 を選択')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /^編集$/ })).not.toBeInTheDocument();
  });

  it('opens the checked reply edit page with the shared password', async () => {
    renderEditMode();

    await screen.findByRole('link', { name: /\[No・7\]/ });
    fireEvent.click(screen.getByLabelText('返信No.7-1 を選択'));
    fireEvent.change(screen.getByLabelText('パスワード'), { target: { value: 'secret' } });
    fireEvent.click(screen.getByRole('button', { name: 'チェックした項目を編集する' }));

    await screen.findByText('/edit/8');
  });
});

function LocationProbe() {
  const location = useLocation();
  return <div>{location.pathname}</div>;
}

function renderEditMode() {
  render(
    <MemoryRouter initialEntries={['/edit']}>
      <LocationProbe />
      <Routes>
        <Route path="/edit" element={<EditModePage />} />
        <Route path="/edit/:id" element={<div>edit target</div>} />
      </Routes>
    </MemoryRouter>,
  );
}
