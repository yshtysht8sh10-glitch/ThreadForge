import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import ManualPage from './ManualPage';

describe('ManualPage', () => {
  it('renders the embedded board manual content inside the SPA', () => {
    render(<ManualPage />);

    expect(screen.getByRole('heading', { name: 'MUGEN ドット絵板の説明書' })).toBeInTheDocument();
    expect(screen.getByText('ジャンル不問のドット絵投稿掲示板です。')).toBeInTheDocument();
    expect(screen.getByText('返信では画像投稿はできません。')).toBeInTheDocument();
  });
});
