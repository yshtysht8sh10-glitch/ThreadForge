import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import SearchPage from './SearchPage';
import { api } from '../api';

vi.mock('../api', () => ({
  api: {
    search: vi.fn(),
  },
  mediaUrl: (path?: string | null) => path,
}));

const result = {
  id: 5,
  thread_id: 5,
  parent_id: 0,
  name: 'Alice',
  title: 'Dot picture',
  message: 'A searchable body',
  image_path: '/storage/data/5.png',
  created_at: '2026-05-17 10:00:00',
};

describe('SearchPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(api.search).mockResolvedValue([result as any]);
  });

  it('restores query params, shows images, and links back to the list position', async () => {
    renderSearch('/search?q=dot&scope=title');

    expect(await screen.findByDisplayValue('dot')).toBeInTheDocument();
    expect(api.search).toHaveBeenCalledWith('dot', 'title', 1, 50, 'all');
    expect(screen.getByRole('img', { name: 'Dot picture' })).toHaveAttribute('src', '/storage/data/5.png');
    for (const link of screen.getAllByRole('link', { name: 'Dot picture' })) {
      expect(link).toHaveAttribute('href', '/?target=5#post-5');
    }
  });

  it('stores the search condition in the URL when searching', async () => {
    renderSearch('/search');

    fireEvent.change(screen.getByLabelText('キーワード'), { target: { value: 'pixel' } });
    fireEvent.click(screen.getByRole('button', { name: '検索する' }));

    await waitFor(() => expect(api.search).toHaveBeenCalledWith('pixel', 'all', 1, 50, 'all'));
    expect(screen.getByTestId('location').textContent).toBe('/search?q=pixel&scope=all&posts=1&replies=1');
  });

  it('can filter search results to replies only', async () => {
    renderSearch('/search');

    fireEvent.change(screen.getByLabelText('キーワード'), { target: { value: 'reply' } });
    fireEvent.click(screen.getByLabelText('投稿'));
    fireEvent.click(screen.getByRole('button', { name: '検索する' }));

    await waitFor(() => expect(api.search).toHaveBeenCalledWith('reply', 'all', 1, 50, 'replies'));
    expect(screen.getByTestId('location').textContent).toBe('/search?q=reply&scope=all&posts=0&replies=1');
    fireEvent.click(screen.getByLabelText('投稿'));
    await waitFor(() => expect(api.search).toHaveBeenCalledWith('reply', 'all', 1, 50, 'all'));
  });
});

function renderSearch(initialEntry: string) {
  render(
    <MemoryRouter initialEntries={[initialEntry]}>
      <Routes>
        <Route
          path="/search"
          element={(
            <>
              <SearchPage />
              <LocationProbe />
            </>
          )}
        />
      </Routes>
    </MemoryRouter>,
  );
}

function LocationProbe() {
  const location = useLocation();
  return <span data-testid="location">{location.pathname}{location.search}</span>;
}
