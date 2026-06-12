import { describe, expect, it } from 'vitest';
import {
  acceptValue,
  allowedExtensionList,
  buildMonthlyAnalytics,
  formatSize,
  homeHref,
  normalizeSettings,
  resolveBackendFileUrl,
  toggleId,
  validateUploadFile,
  type UploadRow,
} from './App';

describe('file-uploader helpers', () => {
  it('normalizes and de-duplicates allowed extensions', () => {
    expect(allowedExtensionList('PNG, .jpg; zip png ../exe')).toEqual(['png', 'jpg', 'zip']);
    expect(acceptValue('PNG jpg zip')).toBe('.png,.jpg,.zip');
  });

  it('validates extension and file-size limits', () => {
    const settings = normalizeSettings({
      allowedExtensions: 'png zip',
      maxUploadKb: 10,
    });

    expect(validateUploadFile({ name: 'sample.png', size: 10240 } as File, settings)).toBe('');
    expect(validateUploadFile({ name: 'sample.exe', size: 10 } as File, settings)).toContain('許可されていない拡張子');
    expect(validateUploadFile({ name: 'sample.zip', size: 10241 } as File, settings)).toContain('上限を超えています');
  });

  it('fills missing settings with stable defaults', () => {
    const normalized = normalizeSettings({
      title: '',
      allowedExtensions: 'png',
      maxUploadKb: 0,
      design: { pageBackgroundColor: '#123456' } as never,
    });

    expect(normalized.title).toBe('ファイルアップローダー');
    expect(normalized.homePageUrl).toBe('../');
    expect(normalized.allowedExtensions).toBe('png');
    expect(normalized.maxUploadKb).toBe(20000);
    expect(normalized.design.pageBackgroundColor).toBe('#123456');
    expect(normalized.design.buttonTextColor).toBe('#000000');
  });

  it('normalizes configured HOME URLs', () => {
    expect(homeHref('https://example.com/home')).toBe('https://example.com/home');
    expect(homeHref('example.com/home')).toBe('https://example.com/home');
    expect(homeHref('/DotoEita/')).toBe('/DotoEita/');
    expect(homeHref('../')).toBe('../');
  });

  it('resolves packaged storage paths beside the deployed API', () => {
    expect(resolveBackendFileUrl(
      'storage/data/file1.png',
      'api.php',
      'https://mugendoteita.main.jp/DotoEita/12_file_uploader/',
    )).toBe('https://mugendoteita.main.jp/DotoEita/12_file_uploader/storage/data/file1.png');
  });

  it('builds monthly analytics and formats sizes', () => {
    const rows: UploadRow[] = [
      row(1, '26/06/01-10:00', 512),
      row(2, '26/06/02-10:00', 1024),
      row(3, '26/05/31-10:00', 2048),
    ];

    expect(buildMonthlyAnalytics(rows)).toEqual([
      { month: '26/06', count: 2, sizeBytes: 1536 },
      { month: '26/05', count: 1, sizeBytes: 2048 },
    ]);
    expect(formatSize(415)).toBe('415bytes');
    expect(formatSize(1536)).toBe('2KB');
  });

  it('toggles selected ids without mutating the source', () => {
    const source = [1, 2];
    expect(toggleId(source, 3)).toEqual([1, 2, 3]);
    expect(toggleId(source, 2)).toEqual([1]);
    expect(source).toEqual([1, 2]);
  });
});

function row(id: number, date: string, sizeBytes: number): UploadRow {
  return {
    id,
    filename: `file${id}.png`,
    comment: '',
    sizeBytes,
    date,
    originalName: `${id}.png`,
    deleteKey: '',
  };
}
