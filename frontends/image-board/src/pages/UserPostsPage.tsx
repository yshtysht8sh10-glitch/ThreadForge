import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, mediaUrl } from '../api';
import ThreadList from '../components/ThreadList';
import { Post, UserProfile } from '../types';

const UserPostsPage = () => {
  const { id } = useParams();
  const [posts, setPosts] = useState<Post[]>([]);
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError(null);
    api.listUserPosts(id)
      .then((response) => {
        setProfile(response.user);
        setPosts(response.posts);
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [id]);

  if (loading) {
    return <div className="board-message">読み込み中...</div>;
  }

  if (error) {
    return <div className="board-message">エラー: {error}</div>;
  }

  return (
    <section>
      <div className="user-posts-header">
        <h1>
          {profile?.icon_path && <img className="user-posts-header-icon" src={mediaUrl(profile.icon_path) ?? undefined} alt="" />}
          {profile?.display_name ?? 'ユーザー'} の作品
        </h1>
        <Link to="/">一覧に戻る</Link>
      </div>
      <ThreadList threads={posts} showReplies={false} userIconLinks={false} />
    </section>
  );
};

export default UserPostsPage;
