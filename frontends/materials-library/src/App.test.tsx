import { describe, expect, it } from 'vitest';
import { acceptExtensions, formatBytes } from './App';
import { groupMaterials, homeHref, MaterialItem } from './api';

describe('materials-library helpers', () => {
  it('groups logged-in and guest authors separately even when names match', () => {
    const items = [
      item(1, 10, 'Same Name', 'tag:1'),
      item(2, null, 'Same Name', 'tag:1'),
    ];
    const groups = groupMaterials(items, 'tag');
    expect(groups).toHaveLength(1);
    expect(groups[0].groups).toHaveLength(2);
    expect(groups[0].groups.map((group) => group.key)).toEqual(['user:10', 'guest:Same Name']);
  });

  it('supports author-first grouping', () => {
    const groups = groupMaterials([item(1, 10, 'Author', 'tag:1')], 'author');
    expect(groups[0].label).toBe('Author');
    expect(groups[0].groups[0].label).toBe('Effects');
  });

  it('normalizes home links and upload accept values', () => {
    expect(homeHref('example.com')).toBe('https://example.com');
    expect(homeHref('../')).toBe('../');
    expect(acceptExtensions('zip 7z,rar')).toBe('.zip,.7z,.rar');
  });

  it('formats archive sizes', () => {
    expect(formatBytes(512)).toBe('512bytes');
    expect(formatBytes(2048)).toBe('2KB');
    expect(formatBytes(1572864)).toBe('1.5MB');
  });
});

function item(id: number, userId: number | null, authorName: string, _tag: string): MaterialItem {
  return {
    id, userId, authorName, authorKey: userId ? `user:${userId}` : `guest:${authorName}`,
    authorIcon: null, name: `Item ${id}`, notes: '', tagId: 1, tagName: 'Effects',
    archiveUrl: 'storage/item.zip', archiveOriginalName: 'item.zip', archiveSizeBytes: 1,
    imageUrl: null, imageOriginalName: null, createdAt: '', updatedAt: '', deletedAt: null, terms: [],
  };
}
