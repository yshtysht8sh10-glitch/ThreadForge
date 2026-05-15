export const TWEET_LIMIT = 280;
export const BLUESKY_LIMIT = 300;
export const MASTODON_DEFAULT_LIMIT = 500;
export const MISSKEY_DEFAULT_LIMIT = 3000;
export type SocialPlatform = 'x' | 'bluesky' | 'mastodon' | 'misskey';

export type SocialPostPreview = {
  platform: SocialPlatform;
  label: string;
  text: string;
  limit?: number;
  length: number;
};

const DEFAULT_SOCIAL_HASHTAGS = '#ドット絵 #pixelart';

export function createTweetText(name: string, title: string, message: string, sourceUrl = '', hashtags = DEFAULT_SOCIAL_HASHTAGS): string {
  const tweetMessage = message.split('_TWEND_')[0].trim();
  const source = sourceUrl.trim();
  const tagLine = hashtags.trim();
  const base = [
    `[DT000000：${title}]`,
    `作者：${name}`,
    '',
    tweetMessage,
    '',
    source ? `最新はこちら ${source}` : '',
    tagLine,
  ].filter((line, index, lines) => line !== '' || lines[index - 1] !== '').join('\n');

  return trimSocialText(base, TWEET_LIMIT, countTweetLength);
}

export function createSocialPostPreviews(
  enabled: Record<SocialPlatform, boolean>,
  name: string,
  title: string,
  message: string,
  sourceUrl = '',
  hashtags = DEFAULT_SOCIAL_HASHTAGS,
): SocialPostPreview[] {
  const previews: SocialPostPreview[] = [];
  const baseInputs = { name, title, message, sourceUrl, hashtags };

  if (enabled.x) {
    const text = createTweetText(name, title, message, sourceUrl, hashtags);
    previews.push({ platform: 'x', label: 'X', text, limit: TWEET_LIMIT, length: countTweetLength(text) });
  }

  if (enabled.bluesky) {
    previews.push(createFederatedPreview('bluesky', 'Bluesky', BLUESKY_LIMIT, baseInputs));
  }

  if (enabled.mastodon) {
    previews.push(createFederatedPreview('mastodon', 'Mastodon', MASTODON_DEFAULT_LIMIT, baseInputs));
  }

  if (enabled.misskey) {
    previews.push(createFederatedPreview('misskey', 'Misskey', MISSKEY_DEFAULT_LIMIT, baseInputs));
  }

  return previews;
}

function createFederatedPreview(
  platform: Exclude<SocialPlatform, 'x'>,
  label: string,
  limit: number,
  { name, title, message, sourceUrl, hashtags }: { name: string; title: string; message: string; sourceUrl: string; hashtags: string },
): SocialPostPreview {
  const tweetMessage = message.split('_TWEND_')[0].trim();
  const source = sourceUrl.trim();
  const tagLine = hashtags.trim();
  const base = [
    `[DT000000：${title}]`,
    `作者：${name}`,
    '',
    tweetMessage,
    '',
    source ? `最新はこちら ${source}` : '',
    socialTagLine(platform, tagLine),
  ].filter((line, index, lines) => line !== '' || lines[index - 1] !== '').join('\n');
  const text = trimSocialText(base, limit, countPlainTextLength);

  return { platform, label, text, limit, length: countPlainTextLength(text) };
}

function socialTagLine(_platform: Exclude<SocialPlatform, 'x'>, hashtags: string): string {
  return hashtags;
}

export function countTweetLength(text: string): number {
  const urls = text.match(/https?:\/\/[^\s]+/g) ?? [];
  const withoutUrls = urls.reduce((value, url) => value.replace(url, ''), text);
  return Array.from(withoutUrls).reduce((total, char) => total + tweetCharacterWeight(char), urls.length * 23);
}

export function trimTweetText(text: string): string {
  return trimSocialText(text, TWEET_LIMIT, countTweetLength);
}

function countPlainTextLength(text: string): number {
  return Array.from(text).length;
}

function trimSocialText(text: string, limit: number, countLength: (value: string) => number): string {
  if (countLength(text) <= limit) {
    return text;
  }

  const ellipsis = '..';
  const marker = '\n\n最新はこちら ';
  const tagMarker = '\n#';
  const sourceIndex = text.indexOf(marker);
  const tagIndex = text.lastIndexOf(tagMarker);
  const tailIndex = sourceIndex >= 0 ? sourceIndex : tagIndex >= 0 ? tagIndex : -1;
  let body = tailIndex >= 0 ? text.slice(0, tailIndex) : text;
  let tail = tailIndex >= 0 ? text.slice(tailIndex) : '';

  while (body.length > 0 && countLength(`${body}${ellipsis}${tail}`) > limit) {
    body = Array.from(body).slice(0, -1).join('');
  }

  while (tail.length > 0 && countLength(`${body.trimEnd()}${ellipsis}${tail}`) > limit) {
    tail = Array.from(tail).slice(0, -1).join('');
  }

  return `${body.trimEnd()}${ellipsis}${tail}`;
}

function tweetCharacterWeight(char: string): number {
  if (char === '\r') {
    return 0;
  }
  if (/[\u0000-\u10ff\u2000-\u200d\u2010-\u201f\u2032-\u2037]/u.test(char)) {
    return 1;
  }
  return 2;
}
