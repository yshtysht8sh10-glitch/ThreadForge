import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import HomePage from './HomePage';

describe('HomePage', () => {
  it('renders thread blocks', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const threadLink = await screen.findByRole('link', { name: /\[No・1\]/ });
    expect(threadLink).toBeInTheDocument();
  });

  it('renders uploaded images from the API origin', async () => {
    import.meta.env.VITE_API_BASE_URL = 'http://127.0.0.1:8000/api.php';

    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const image = await screen.findByRole('img');
    expect(image).toHaveAttribute('src', 'http://127.0.0.1:8000/storage/data/mock.png');
  });

  it('shows replies inline on the thread list', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    expect((await screen.findAllByText(/一覧|返信/)).length).toBeGreaterThan(0);
  });

  it('links to the stored Tweet URL from the thread list', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const tweetLink = await screen.findByRole('link', { name: 'Tweet先' });
    expect(tweetLink).toHaveAttribute('href', 'https://x.com/threadforge/status/1');
  });

  it('shows a Tweet destination placeholder when the Tweet URL is not registered yet', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    expect(await screen.findByLabelText('Tweet先未登録')).toBeInTheDocument();
  });

  it('adds visual classes for gdgd and Tweet OFF posts', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const gdgdThread = (await screen.findByRole('link', { name: /\[No・1\]/ })).closest('article');
    const tweetOffThread = (await screen.findByRole('link', { name: /\[No・2\]/ })).closest('article');

    expect(gdgdThread).toHaveClass('board-thread-gdgd');
    expect(tweetOffThread).toHaveClass('board-thread-tweet-off');
  });
});
