import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import ManualPage from './ManualPage';

describe('ManualPage', () => {
  it('renders the configurable manual content inside the SPA', async () => {
    render(<ManualPage />);

    expect(await screen.findByRole('heading', { name: 'ThreadForge 取扱説明書' })).toBeInTheDocument();
    expect(screen.getByText('ThreadForge は、スレッド形式で作品や記事を投稿できる掲示板です。')).toBeInTheDocument();
    expect(screen.getByText('返信に画像投稿はありません。')).toBeInTheDocument();
  });
});
