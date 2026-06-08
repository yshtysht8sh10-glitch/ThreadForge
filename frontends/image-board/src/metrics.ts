import { Post } from './types';

export type MetricId =
  | 'comments'
  | 'views'
  | 'eejanaika'
  | 'omigoto'
  | 'goodjob'
  | 'xLikes'
  | 'xReposts'
  | 'xImpressions'
  | 'blueskyLikes'
  | 'mastodonFavs'
  | 'misskeyReactions';

export const metricOptions: Array<{ id: MetricId; label: string; value: (post: Post) => number }> = [
  { id: 'comments', label: 'コメント数', value: (post) => post.reply_count ?? post.replies?.length ?? 0 },
  { id: 'views', label: '閲覧数', value: (post) => post.board_reactions?.views ?? post.view_count ?? 0 },
  { id: 'eejanaika', label: 'ええじゃ数', value: (post) => post.board_reactions?.eejanaika ?? 0 },
  { id: 'omigoto', label: 'お美事数', value: (post) => post.board_reactions?.omigoto ?? 0 },
  { id: 'goodjob', label: 'いい仕事数', value: (post) => post.board_reactions?.goodjob ?? 0 },
  { id: 'xLikes', label: 'Xいいね数', value: (post) => post.social_reactions?.x?.likes ?? 0 },
  { id: 'xReposts', label: 'Xリポスト数', value: (post) => post.social_reactions?.x?.reposts ?? 0 },
  { id: 'xImpressions', label: 'X表示数', value: (post) => post.social_reactions?.x?.impressions ?? 0 },
  { id: 'blueskyLikes', label: 'Blueskyいいね数', value: (post) => post.social_reactions?.bluesky?.likes ?? 0 },
  { id: 'mastodonFavs', label: 'Mastodon favo数', value: (post) => post.social_reactions?.mastodon?.favs ?? 0 },
  {
    id: 'misskeyReactions',
    label: 'Misskeyリアクション数',
    value: (post) => {
      const reactions = post.social_reactions?.misskey;
      return reactions ? reactions.fire + reactions.eyes + reactions.cry + reactions.thinking + reactions.party + reactions.other : 0;
    },
  },
];

export function metricValue(post: Post, id: MetricId): number {
  return (metricOptions.find((option) => option.id === id) ?? metricOptions[0]).value(post);
}
