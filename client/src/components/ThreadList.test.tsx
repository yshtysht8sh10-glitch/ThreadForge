import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import ThreadList from './ThreadList';
import { Post } from '../types';

vi.mock('../api', async () => {
  const actual = await vi.importActual<typeof import('../api')>('../api');
  return {
    ...actual,
    api: {
      ...actual.api,
      publicSettings: vi.fn().mockResolvedValue({
        success: true,
        settings: {
          config: {
            bbsTitle: 'ThreadForge',
            homePageUrl: '/',
            manualTitle: 'ThreadForge',
            manualBody: '',
            tweetEnabled: true,
            blueskyEnabled: true,
            mastodonEnabled: true,
            misskeyEnabled: true,
            gdgdEnabled: true,
            gdgdLabel: '特殊投稿',
            eejanaikaOmigotoText: 'お美事にございまする',
            eejanaikaOmigotoColor: '#ff72ff',
            eejanaikaGoodjobText: 'いい仕事してますねぇ',
            eejanaikaGoodjobColor: '#27a8ff',
            eejanaikaEejanaikaText: 'ええじゃないか',
            eejanaikaEejanaikaColor: '#fff200',
            socialHashtags: '#art',
            logView: 20,
            allowedImageTypes: ['gif', 'png', 'jpeg', 'jpg', 'bmp'],
          },
        },
      }),
      recordPostView: vi.fn().mockResolvedValue({ success: true, view_count: 1 }),
    },
  };
});

const thread: Post = {
  id: 1,
  thread_id: 1,
  parent_id: 0,
  name: 'Yes',
  title: 'SNS OFF post',
  message: 'SNS posting is disabled for this post.',
  created_at: '2026-06-01 12:00:00',
  tweet_off: true,
  tweet_url: 'https://x.example/status/1',
  social_links: {
    bluesky: 'https://bsky.app/profile/example/post/1',
    mastodon: 'https://mastodon.example/@user/1',
    misskey: 'https://misskey.example/notes/1',
  },
  social_reactions: {
    bluesky: { likes: 1, reposts: 2, quotes: 3 },
    mastodon: { boosts: 1, favs: 2 },
    misskey: { fire: 1, eyes: 2, cry: 3, thinking: 4, party: 5, other: 6 },
  },
  board_reactions: { views: 10, eejanaika: 1, omigoto: 2, goodjob: 3 },
  replies: [],
};

describe('ThreadList', () => {
  it('hides all SNS destination rows when social posting is disabled for a post', async () => {
    render(
      <BrowserRouter>
        <ThreadList threads={[thread]} />
      </BrowserRouter>,
    );

    expect(await screen.findByText('当板：')).toBeInTheDocument();
    expect(screen.queryByText('X先：')).not.toBeInTheDocument();
    expect(screen.queryByText('Bluesky先：')).not.toBeInTheDocument();
    expect(screen.queryByText('Mastodon先：')).not.toBeInTheDocument();
    expect(screen.queryByText('Misskey先：')).not.toBeInTheDocument();
  });
});
