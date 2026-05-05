export const TWEET_LIMIT = 280;

export function createTweetText(name: string, title: string, message: string, sourceUrl = ''): string {
  const tweetMessage = message.split('_TWEND_')[0].trim();
  const source = sourceUrl.trim();
  const base = [
    `[DT000000：${title}]`,
    `作者：${name}`,
    '',
    tweetMessage,
    '',
    source ? `元：${source}` : '',
    '#ドット絵 #pixelart',
  ].filter((line, index, lines) => line !== '' || lines[index - 1] !== '').join('\n');

  return trimTweetText(base);
}

export function countTweetLength(text: string): number {
  const urls = text.match(/https?:\/\/[^\s]+/g) ?? [];
  const withoutUrls = urls.reduce((value, url) => value.replace(url, ''), text);
  return Array.from(withoutUrls.replace(/\r?\n/g, '')).length + urls.length * 23;
}

export function trimTweetText(text: string): string {
  if (countTweetLength(text) <= TWEET_LIMIT) {
    return text;
  }

  const marker = '\n\n元：';
  const sourceIndex = text.indexOf(marker);
  let body = sourceIndex >= 0 ? text.slice(0, sourceIndex) : text;
  const tail = sourceIndex >= 0 ? text.slice(sourceIndex) : '';

  while (body.length > 0 && countTweetLength(`${body}...${tail}`) > TWEET_LIMIT) {
    body = body.slice(0, -1);
  }

  return `${body.trimEnd()}...${tail}`;
}
