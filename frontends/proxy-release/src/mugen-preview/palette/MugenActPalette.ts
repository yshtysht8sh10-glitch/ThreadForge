// Synchronized from WebMUGEN's runtime PcxDecoder palette path at d5f4a2b.
// ACT bytes are RGB triplets; WinMUGEN/SFF v1 looks them up in reversed index order.
export type PaletteIndexOrder = 'normal' | 'reversed';

export const MUGEN_ACT_PALETTE_SIZE = 256 * 3;
export const MUGEN_ACT_INDEX_ORDER: PaletteIndexOrder = 'reversed';

export function normalizeMugenActPalette(palette: Uint8Array | undefined): Uint8Array | null {
  if (!palette) return null;
  if (palette.length !== MUGEN_ACT_PALETTE_SIZE) {
    throw new Error(`Invalid external PCX palette size: ${palette.length}`);
  }
  return palette;
}

export function paletteColorOffset(sourceIndex: number, indexOrder: PaletteIndexOrder): number {
  const colorIndex = indexOrder === 'reversed' ? 255 - sourceIndex : sourceIndex;
  return colorIndex * 3;
}
