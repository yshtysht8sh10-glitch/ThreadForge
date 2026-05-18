import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import ManualPage from './ManualPage';
import { api, DEFAULT_PUBLIC_SETTINGS } from '../api';

vi.mock('../api', async () => {
  const actual = await vi.importActual<typeof import('../api')>('../api');
  return {
    ...actual,
    api: {
      ...actual.api,
      publicSettings: vi.fn(),
    },
  };
});

describe('ManualPage', () => {
  it('uses the configured special post label in the manual body', async () => {
    vi.mocked(api.publicSettings).mockResolvedValue({
      success: true,
      settings: {
        config: {
          ...DEFAULT_PUBLIC_SETTINGS.config,
          gdgdLabel: '気楽投稿',
        },
      },
    });

    render(<ManualPage />);

    expect(await screen.findByText('気楽投稿が有効なサイトでは、通常投稿とは別の枠色で投稿できます。')).toBeInTheDocument();
  });

  it('renders the configurable manual content inside the SPA', async () => {
    vi.mocked(api.publicSettings).mockRejectedValue(new Error('offline'));
    render(<ManualPage />);

    expect(await screen.findByRole('heading', { name: 'ThreadForge' })).toBeInTheDocument();
    expect(screen.getByText('この取説は、このサイトを利用する方向けの案内です。')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '【HOME】' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '【ログイン】' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: '【ユーザーページ】' })).toBeInTheDocument();
    expect(screen.getByText('自分の作品として紐づけた投稿は作品一覧や統計に含まれます。')).toBeInTheDocument();
    expect(screen.queryByText('同じ数の投稿は同じ順位になります。')).not.toBeInTheDocument();
  });
});
