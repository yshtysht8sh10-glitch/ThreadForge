import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import RankingPage from './RankingPage';
import { api } from '../api';

vi.mock('../api', () => ({
  api: {
    listRankingPosts: vi.fn(),
  },
  mediaUrl: (path?: string | null) => path,
}));

const posts = [
  {
    id: 1,
    thread_id: 1,
    parent_id: 0,
    display_no: 10,
    title: 'Gold',
    name: 'Alice',
    message: 'body',
    image_path: null,
    created_at: '2026-05-01 10:00:00',
    user_id: 7,
    user_icon_path: '/storage/data/user_7.png',
    user_display_name: 'Alice',
    board_reactions: { views: 30, eejanaika: 0, omigoto: 0, goodjob: 0 },
  },
  {
    id: 2,
    thread_id: 2,
    parent_id: 0,
    display_no: 11,
    title: 'Silver',
    name: 'Bob',
    message: 'body',
    image_path: null,
    created_at: '2026-05-01 11:00:00',
    board_reactions: { views: 20, eejanaika: 0, omigoto: 0, goodjob: 0 },
  },
  {
    id: 3,
    thread_id: 3,
    parent_id: 0,
    display_no: 12,
    title: 'Bronze',
    name: 'Carol',
    message: 'body',
    image_path: null,
    created_at: '2026-05-01 12:00:00',
    board_reactions: { views: 10, eejanaika: 0, omigoto: 0, goodjob: 0 },
  },
];

describe('RankingPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(api.listRankingPosts).mockResolvedValue(posts as any);
  });

  it('decorates the top three and links titles to the list position with author icons', async () => {
    render(
      <MemoryRouter>
        <RankingPage />
      </MemoryRouter>,
    );

    const gold = await screen.findByText('1');
    expect(gold.closest('.ranking-row')).toHaveClass('ranking-row-gold');
    expect(screen.getByText('2').closest('.ranking-row')).toHaveClass('ranking-row-silver');
    expect(screen.getByText('3').closest('.ranking-row')).toHaveClass('ranking-row-bronze');

    const goldRow = gold.closest('.ranking-row') as HTMLElement;
    expect(within(goldRow).getByRole('link', { name: 'No.10 Gold' })).toHaveAttribute('href', '/?target=1#post-1');
    expect(within(goldRow).getByText('NAME: Alice')).toBeInTheDocument();
    expect(within(goldRow).getByRole('link', { name: 'Alice の作品を見る' })).toHaveAttribute('href', '/user/7');
    expect(within(goldRow).getByText('このユーザーの作品を見ますか？')).toBeInTheDocument();
  });

  it('uses shared ranks for ties and does not decorate zero scores', async () => {
    vi.mocked(api.listRankingPosts).mockResolvedValue([
      { ...posts[0], id: 1, display_no: 1, title: 'Tie gold A', board_reactions: { views: 10, eejanaika: 0, omigoto: 0, goodjob: 0 } },
      { ...posts[1], id: 2, display_no: 2, title: 'Tie gold B', board_reactions: { views: 10, eejanaika: 0, omigoto: 0, goodjob: 0 } },
      { ...posts[2], id: 3, display_no: 3, title: 'Tie bronze A', board_reactions: { views: 5, eejanaika: 0, omigoto: 0, goodjob: 0 } },
      { ...posts[2], id: 4, display_no: 4, title: 'Tie bronze B', board_reactions: { views: 5, eejanaika: 0, omigoto: 0, goodjob: 0 } },
      { ...posts[2], id: 5, display_no: 5, title: 'Zero', board_reactions: { views: 0, eejanaika: 0, omigoto: 0, goodjob: 0 } },
    ] as any);

    render(
      <MemoryRouter>
        <RankingPage />
      </MemoryRouter>,
    );

    const goldRows = [
      (await screen.findByRole('link', { name: 'No.1 Tie gold A' })).closest('.ranking-row') as HTMLElement,
      screen.getByRole('link', { name: 'No.2 Tie gold B' }).closest('.ranking-row') as HTMLElement,
    ];
    expect(goldRows[0]).toHaveClass('ranking-row-gold');
    expect(goldRows[1]).toHaveClass('ranking-row-gold');
    expect(within(goldRows[0]).getByText('1')).toBeInTheDocument();
    expect(within(goldRows[1]).getByText('1')).toBeInTheDocument();

    const bronzeRows = [
      screen.getByRole('link', { name: 'No.3 Tie bronze A' }).closest('.ranking-row') as HTMLElement,
      screen.getByRole('link', { name: 'No.4 Tie bronze B' }).closest('.ranking-row') as HTMLElement,
    ];
    expect(bronzeRows[0]).toHaveClass('ranking-row-bronze');
    expect(bronzeRows[1]).toHaveClass('ranking-row-bronze');
    expect(within(bronzeRows[0]).getByText('3')).toBeInTheDocument();
    expect(within(bronzeRows[1]).getByText('3')).toBeInTheDocument();

    expect(screen.getByRole('link', { name: 'No.5 Zero' }).closest('.ranking-row')).not.toHaveClass('ranking-row-gold');
    expect(screen.getByRole('link', { name: 'No.5 Zero' }).closest('.ranking-row')).not.toHaveClass('ranking-row-silver');
    expect(screen.getByRole('link', { name: 'No.5 Zero' }).closest('.ranking-row')).not.toHaveClass('ranking-row-bronze');
  });
});
