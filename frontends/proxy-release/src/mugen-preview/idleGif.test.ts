import { readFile } from 'node:fs/promises';
import { describe, expect, it } from 'vitest';
import { inspectMugenZipActPalettes, loadMugenIdlePreviewAssets } from './idleGif';
import { MUGEN_ACT_INDEX_ORDER, paletteColorOffset } from './palette/MugenActPalette';

class FakeImageData {
  constructor(
    public data: Uint8ClampedArray,
    public width: number,
    public height: number,
  ) {}
}

(globalThis as unknown as { ImageData: typeof ImageData }).ImageData =
  FakeImageData as unknown as typeof ImageData;

describe('MUGEN idle GIF palette source', () => {
  it('reads ordered pal entries from the Character DEF and defaults to pal1', async () => {
    const zip = await fixtureZip();
    const info = await inspectMugenZipActPalettes(zip);

    expect(info.options.map(({ slot }) => slot)).toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);
    expect(info.options[0].file).toBe('act/anguilas1.act');
    expect(info.defaultSlot).toBe(1);
  });

  it('uses WebMUGEN SFF v1 palette application and changes colors with the selected ACT', async () => {
    const zip = await fixtureZip();
    const pal1 = await loadMugenIdlePreviewAssets(zip, 1);
    const pal2 = await loadMugenIdlePreviewAssets(zip, 2);
    const sprite1 = pal1.sprites.sprites.get('0,0');
    const sprite2 = pal2.sprites.sprites.get('0,0');

    expect(pal1.selectedPalette?.slot).toBe(1);
    expect(pal2.selectedPalette?.slot).toBe(2);
    expect(MUGEN_ACT_INDEX_ORDER).toBe('reversed');
    expect(paletteColorOffset(0, MUGEN_ACT_INDEX_ORDER)).toBe(255 * 3);
    expect(paletteColorOffset(1, MUGEN_ACT_INDEX_ORDER)).toBe(254 * 3);
    expect(Array.from(sprite1!.imageData.data)).not.toEqual(Array.from(sprite2!.imageData.data));
  });

  it('can generate preview assets without applying an ACT palette', async () => {
    const withoutAct = await loadMugenIdlePreviewAssets(await fixtureZip(), null);

    expect(withoutAct.selectedPalette).toBeNull();
    expect(withoutAct.sprites.sprites.get('0,0')?.imageData.data.length).toBeGreaterThan(0);
  });
});

async function fixtureZip(): Promise<File> {
  const bytes = await readFile('../file-uploader/legacy/uploda/src/file132.zip');
  return {
    name: 'file132.zip',
    arrayBuffer: async () => bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength),
  } as File;
}
