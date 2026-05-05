export type Post = {
  id: number;
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
  tweet_like_count?: number;
  tweet_retweet_count?: number;
  tweet_comment_count?: number;
  tweet_impression_count?: number;
  replies?: Post[];
  reply_count?: number;
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
  tweet_url?: string;
  tweet_like_count?: number;
  tweet_retweet_count?: number;
  tweet_comment_count?: number;
  tweet_impression_count?: number;
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
  tweet_like_count?: number;
  tweet_retweet_count?: number;
  tweet_comment_count?: number;
  tweet_impression_count?: number;
};
