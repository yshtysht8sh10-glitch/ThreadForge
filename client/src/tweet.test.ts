import { describe, expect, it } from 'vitest';
import { BLUESKY_LIMIT, countTweetLength, createSocialPostPreviews, createTweetText, TWEET_LIMIT } from './tweet';

describe('tweet utilities', () => {
  it('creates tweet text from post fields', () => {
    const text = createTweetText('Alice', 'Title', '本文です', 'https://example.com/thread/1');

    expect(text).toContain('[DT000000：Title]');
    expect(text).toContain('作者：Alice');
    expect(text).toContain('本文です');
    expect(text).toContain('元：https://example.com/thread/1');
  });

  it('ignores message text after _TWEND_', () => {
    const text = createTweetText('Alice', 'Title', '表に出る_TWEND_出ない');

    expect(text).toContain('表に出る');
    expect(text).not.toContain('出ない');
  });

  it('counts URLs as 23 characters and trims long text', () => {
    expect(countTweetLength('a https://example.com/very/long/url')).toBe(25);

    const text = createTweetText('Alice', 'Title', 'あ'.repeat(400), 'https://example.com/thread/1');
    expect(countTweetLength(text)).toBeLessThanOrEqual(TWEET_LIMIT);
    expect(text).toContain('..');
  });

  it('trims each social preview to its own posting limit', () => {
    const previews = createSocialPostPreviews(
      { x: true, bluesky: true, mastodon: false, misskey: false },
      'Alice',
      'Title',
      'あ'.repeat(400),
      'https://example.com/thread/1',
    );
    const x = previews.find((preview) => preview.platform === 'x');
    const bluesky = previews.find((preview) => preview.platform === 'bluesky');

    expect(x?.limit).toBe(TWEET_LIMIT);
    expect(x && countTweetLength(x.text)).toBeLessThanOrEqual(TWEET_LIMIT);
    expect(bluesky?.limit).toBe(BLUESKY_LIMIT);
    expect(bluesky?.length).toBeLessThanOrEqual(BLUESKY_LIMIT);
    expect(bluesky?.text).toContain('..');
  });
});
