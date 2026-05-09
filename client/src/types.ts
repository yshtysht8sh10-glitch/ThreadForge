export type Post = {
  id: number;
  display_no?: number;
  reply_no?: number;
  thread_id: number;
  parent_id: number;
  name: string;
  url?: string | null;
  title: string;
  message: string;
  image_path?: string | null;
  created_at: string;
  deleted_at?: string | null;
  gdgd?: boolean;
  tweet_off?: boolean;
  tweet_text?: string | null;
  tweet_url?: string | null;
  social_links?: SocialLinks;
  social_reactions?: SocialReactions;
  replies?: Post[];
  reply_count?: number;
};

export type SocialLinks = {
  x?: string | null;
  bluesky?: string | null;
  mastodon?: string | null;
  misskey?: string | null;
};

export type SocialReactions = {
  x?: {
    likes: number;
    reposts: number;
    impressions: number;
  };
  bluesky?: {
    likes: number;
    reposts: number;
    quotes: number;
  };
  mastodon?: {
    boosts: number;
    favs: number;
  };
  misskey?: {
    fire: number;
    eyes: number;
    cry: number;
    thinking: number;
    party: number;
    other: number;
  };
};

export type ThreadResponse = {
  thread: Post | null;
  replies: Post[];
};

export type SearchResult = Post;

export type NewPostData = {
  thread_id?: number;
  parent_id?: number;
  name: string;
  url?: string;
  title: string;
  message: string;
  password?: string;
  file?: File;
  gdgd?: boolean;
  tweet_off?: boolean;
};

export type EditPostData = {
  id: number;
  name: string;
  url?: string;
  title: string;
  message: string;
  password: string;
  file?: File;
  gdgd?: boolean;
  tweet_off?: boolean;
  tweet_url?: string;
};
