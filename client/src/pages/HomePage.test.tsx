import { describe, it, expect, vi } from 'vitest';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import HomePage from './HomePage';

describe('HomePage', () => {
  it('renders thread blocks', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const threadLinks = await screen.findAllByRole('link', { name: /\[No/ });
    expect(threadLinks.length).toBeGreaterThan(0);
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

  it('hides social destination rows while social posting is disabled', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    await screen.findAllByRole('link', { name: /\[No/ });
    expect(screen.queryByLabelText('X先')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('X先未登録')).not.toBeInTheDocument();
  });

  it('adds visual classes for gdgd but not Tweet OFF posts', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const articles = await screen.findAllByRole('article');
    expect(articles[0]).toHaveClass('board-thread-gdgd');
    expect(articles[1]).not.toHaveClass('board-thread-tweet-off');
  });

  it('hides inline action buttons while a comment form is open and closes empty drafts immediately', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const articles = await screen.findAllByRole('article');
    const firstArticle = articles[0];
    fireEvent.click(within(firstArticle).getByRole('button', { name: 'コメント' }));

    expect(within(firstArticle).getByRole('heading', { name: 'コメント' })).toBeInTheDocument();
    expect(within(firstArticle).getByText('名前（/30文字）')).toBeInTheDocument();
    expect(within(firstArticle).getByText('本文（/100000文字）')).toBeInTheDocument();
    expect(within(firstArticle).getByText('※半角英数字8文字まで有効です。')).toBeInTheDocument();
    expect(within(firstArticle).getByLabelText('名前')).toHaveAttribute('maxlength', '30');
    expect(within(firstArticle).getByLabelText('本文')).toHaveAttribute('maxlength', '100000');
    expect(within(firstArticle).getByLabelText('パスワード')).toHaveAttribute('maxlength', '8');
    expect(within(firstArticle).queryByRole('button', { name: 'コメント' })).not.toBeInTheDocument();
    expect(within(firstArticle).queryByRole('button', { name: 'ええじゃないか' })).not.toBeInTheDocument();

    fireEvent.click(within(firstArticle).getByRole('button', { name: 'コメントフォームを閉じる' }));

    expect(within(firstArticle).queryByRole('heading', { name: 'コメント' })).not.toBeInTheDocument();
    expect(within(firstArticle).getByRole('button', { name: 'コメント' })).toBeInTheDocument();
  });

  it('confirms before closing an inline comment draft', async () => {
    const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);

    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const firstArticle = (await screen.findAllByRole('article'))[0];
    fireEvent.click(within(firstArticle).getByRole('button', { name: 'コメント' }));
    fireEvent.change(within(firstArticle).getByLabelText('本文'), { target: { value: '書きかけ' } });
    fireEvent.click(within(firstArticle).getByRole('button', { name: 'コメントフォームを閉じる' }));

    expect(confirm).toHaveBeenCalledWith('入力内容は破棄されます。閉じますか？');
    expect(within(firstArticle).getByRole('heading', { name: 'コメント' })).toBeInTheDocument();
  });

  it('uses the same required name style in the inline eejanaika form', async () => {
    render(
      <BrowserRouter>
        <HomePage />
      </BrowserRouter>,
    );

    const firstArticle = (await screen.findAllByRole('article'))[0];
    fireEvent.click(within(firstArticle).getByRole('button', { name: 'ええじゃないか' }));

    expect(within(firstArticle).getByText('名前（/30文字）')).toBeInTheDocument();
    expect(within(firstArticle).getByLabelText('名前')).toHaveAttribute('maxlength', '30');
    expect(within(firstArticle).getByLabelText('名前')).toBeRequired();
  });
});
