import { Link } from 'react-router-dom';
import { mediaUrl } from '../api';
import { Post } from '../types';

type UserIconLinkProps = {
  post: Post;
  enabled?: boolean;
};

const UserIconLink = ({ post, enabled = true }: UserIconLinkProps) => {
  if (!post.user_id || !post.user_icon_path) {
    return null;
  }
  const iconUrl = mediaUrl(post.user_icon_path) ?? undefined;
  const userName = post.user_display_name || post.name;

  if (!enabled) {
    return <img className="user-icon" src={iconUrl} alt="" />;
  }

  return (
    <Link className="user-icon-link" to={`/user/${post.user_id}`} aria-label={`${userName} の作品を見る`}>
      <img className="user-icon" src={iconUrl} alt="" />
      <span className="user-icon-popover" role="tooltip">
        <img src={iconUrl} alt="" />
        <strong>{userName}</strong>
        <span>このユーザーの作品を見ますか？</span>
      </span>
    </Link>
  );
};

export default UserIconLink;
