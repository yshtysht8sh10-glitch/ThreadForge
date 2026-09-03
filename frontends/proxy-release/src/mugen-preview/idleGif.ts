import { unzipSync } from 'fflate';
import { GIFEncoder, applyPalette, quantize } from 'gifenc';
import { parseAirText } from './air/AirParser';
import type { AirAction, AirElement } from './air/AirTypes';
import { getCharacterDefFiles, getDefSection, parseDefText } from './def/DefParser';
import type { DefDocument } from './def/DefTypes';
import { MUGEN_ACT_INDEX_ORDER } from './palette/MugenActPalette';
import type { ImageDataSpritePack } from './sprite/ImageDataSpriteTypes';
import { convertSffV1ToImageDataSpritePack } from './sprite/SffSpritePackConverter';
import { spriteKey } from './sprite/SpritePackLoader';
import { decodeMugenText } from './text/MugenTextDecoder';

const CANVAS_SIZE = 220;
const MAX_FRAMES = 12;
const FRAME_DELAY_MS = 80;

export type MugenActPaletteOption = { slot: number; file: string; path: string };
export type MugenActPaletteInfo = { options: MugenActPaletteOption[]; defaultSlot: number | null };
export type MugenIdlePreviewAssets = {
  action: AirAction;
  sprites: ImageDataSpritePack;
  selectedPalette: MugenActPaletteOption | null;
};

export async function inspectMugenZipActPalettes(zipFile: File): Promise<MugenActPaletteInfo> {
  const archive = await inspectCharacterArchive(zipFile);
  return {
    options: archive.palettes,
    defaultSlot: archive.palettes.find((palette) => palette.slot === 1)?.slot
      ?? archive.palettes[0]?.slot
      ?? null,
  };
}

export async function createIdleGifFromMugenZip(zipFile: File, paletteSlot: number | null | undefined = undefined): Promise<File> {
  const { action, sprites } = await loadMugenIdlePreviewAssets(zipFile, paletteSlot);
  const frames = action.elements
    .filter((element) => element.groupNo >= 0 && element.imageNo >= 0)
    .slice(0, MAX_FRAMES);
  if (frames.length === 0) {
    throw new Error('待機モーションに表示できるスプライトがありませんでした。');
  }

  const bounds = animationBounds(frames, sprites.sprites);
  const canvas = document.createElement('canvas');
  canvas.width = CANVAS_SIZE;
  canvas.height = CANVAS_SIZE;
  const context = canvas.getContext('2d', { willReadFrequently: true });
  if (!context) throw new Error('プレビュー用Canvasを作成できませんでした。');

  const gif = GIFEncoder();
  frames.forEach((element) => {
    context.clearRect(0, 0, canvas.width, canvas.height);
    drawElement(context, element, sprites.sprites, bounds);
    const image = context.getImageData(0, 0, canvas.width, canvas.height);
    const palette = quantize(image.data, 256, { format: 'rgba4444', oneBitAlpha: true });
    const index = applyPalette(image.data, palette, 'rgba4444');
    gif.writeFrame(index, canvas.width, canvas.height, {
      palette,
      delay: Math.max(FRAME_DELAY_MS, element.duration > 0 ? element.duration * 16 : FRAME_DELAY_MS),
      transparent: true,
      transparentIndex: 0,
    });
  });
  gif.finish();

  const baseName = zipFile.name.replace(/\.[^.]+$/, '') || 'mugen-character';
  return new File([gif.bytes()], `${baseName}-idle.gif`, { type: 'image/gif' });
}

export async function loadMugenIdlePreviewAssets(
  zipFile: File,
  paletteSlot: number | null | undefined = undefined,
): Promise<MugenIdlePreviewAssets> {
  const archive = await inspectCharacterArchive(zipFile);
  const selectedPalette = paletteSlot === null
    ? undefined
    : paletteSlot === undefined
    ? archive.palettes.find((palette) => palette.slot === 1) ?? archive.palettes[0]
    : archive.palettes.find((palette) => palette.slot === paletteSlot) ?? archive.palettes[0];

  const air = parseAirText(decodeMugenText(archive.airEntry));
  const action = findIdleAction(air.actions);
  if (!action) {
    throw new Error('待機モーションとして使えるAIR actionが見つかりませんでした。');
  }
  const sprites = convertSffV1ToImageDataSpritePack(toArrayBuffer(archive.sffEntry), selectedPalette ? {
    externalPalette: archive.entries.get(selectedPalette.path),
    paletteIndexOrder: MUGEN_ACT_INDEX_ORDER,
  } : {});
  return { action, sprites, selectedPalette: selectedPalette ?? null };
}

type CharacterArchive = {
  entries: ReadonlyMap<string, Uint8Array>;
  def: DefDocument;
  airEntry: Uint8Array;
  sffEntry: Uint8Array;
  palettes: MugenActPaletteOption[];
};

async function inspectCharacterArchive(zipFile: File): Promise<CharacterArchive> {
  const unzipped = unzipSync(new Uint8Array(await zipFile.arrayBuffer()));
  const entries = new Map<string, Uint8Array>();
  const entryKeys = new Map<string, string>();
  for (const [path, bytes] of Object.entries(unzipped)) {
    if (path.endsWith('/')) continue;
    const normalized = normalizeZipPath(path);
    entries.set(normalized, bytes);
    entryKeys.set(normalized.toLowerCase(), normalized);
  }

  const defPath = discoverCharacterDef(entries);
  const def = parseDefText(decodeMugenText(entries.get(defPath)!));
  const files = getCharacterDefFiles(def);
  const basePath = directoryOf(defPath);
  if (!files.anim || !files.sprite) {
    throw new Error('Character DEFの[Files]にanimまたはspriteがありません。');
  }
  const resolveEntry = (relativePath: string): { path: string; bytes: Uint8Array } | null => {
    const resolved = normalizeZipPath(resolveAssetPath(basePath, relativePath));
    const actualPath = entryKeys.get(resolved.toLowerCase());
    return actualPath ? { path: actualPath, bytes: entries.get(actualPath)! } : null;
  };
  const air = resolveEntry(files.anim);
  const sff = resolveEntry(files.sprite);
  if (!air || !sff) {
    throw new Error('Character DEFが参照するAIRまたはSFFがZIP内に見つかりません。');
  }
  const palettes = (files.palettes ?? []).flatMap((palette) => {
    const entry = resolveEntry(palette.file);
    return entry ? [{ slot: palette.slot, file: palette.file, path: entry.path }] : [];
  });
  return { entries, def, airEntry: air.bytes, sffEntry: sff.bytes, palettes };
}

function discoverCharacterDef(entries: ReadonlyMap<string, Uint8Array>): string {
  const candidates = Array.from(entries)
    .filter(([path]) => path.toLowerCase().endsWith('.def'))
    .filter(([, bytes]) => {
      const def = parseDefText(decodeMugenText(bytes).replace(/^\uFEFF/, ''));
      const files = getCharacterDefFiles(def);
      return Boolean(getDefSection(def, 'Info') && getDefSection(def, 'Files') && files.cmd && (files.cns || files.st?.length) && files.anim);
    })
    .map(([path]) => path)
    .sort((left, right) => pathDepth(left) - pathDepth(right)
      || fileStem(left).length - fileStem(right).length
      || left.localeCompare(right, 'en'));
  if (!candidates.length) throw new Error('ZIP内に有効なCharacter DEFが見つかりません。');
  return candidates[0];
}

function resolveAssetPath(basePath: string, relativePath: string): string {
  if (!basePath) return relativePath;
  return `${basePath.replace(/\/$/, '')}/${relativePath.replace(/^\.\//, '')}`;
}

function pathDepth(path: string): number {
  return path.replace(/\\/g, '/').split('/').filter(Boolean).length;
}

function fileStem(path: string): string {
  return (path.replace(/\\/g, '/').split('/').pop() ?? path).replace(/\.[^.]+$/, '');
}

function normalizeZipPath(path: string): string {
  const segments: string[] = [];
  for (const segment of path.replace(/\\/g, '/').split('/')) {
    if (!segment || segment === '.') continue;
    if (segment === '..') {
      if (segments.length === 0) throw new Error(`ZIP内の不正なパスです: ${path}`);
      segments.pop();
      continue;
    }
    segments.push(segment);
  }
  return segments.join('/');
}

function directoryOf(path: string): string {
  const normalized = path.replace(/\\/g, '/');
  const slash = normalized.lastIndexOf('/');
  return slash >= 0 ? normalized.slice(0, slash) : '';
}

function findIdleAction(actions: AirAction[]): AirAction | null {
  return actions.find((action) => action.actionNo === 0 && action.elements.length > 0)
    ?? actions.find((action) => action.actionNo === 180 && action.elements.length > 0)
    ?? actions.find((action) => action.elements.length > 0)
    ?? null;
}

function animationBounds(elements: AirElement[], sprites: ImageDataSpritePack['sprites']) {
  const rects = elements.map((element) => {
    const sprite = sprites.get(spriteKey(element.groupNo, element.imageNo));
    if (!sprite) return null;
    return {
      left: element.offsetX - sprite.xAxis,
      top: element.offsetY - sprite.yAxis,
      right: element.offsetX - sprite.xAxis + sprite.imageData.width,
      bottom: element.offsetY - sprite.yAxis + sprite.imageData.height,
    };
  }).filter(Boolean) as Array<{ left: number; top: number; right: number; bottom: number }>;
  if (rects.length === 0) return { left: -80, top: -160, right: 80, bottom: 40 };
  return {
    left: Math.min(...rects.map((rect) => rect.left)),
    top: Math.min(...rects.map((rect) => rect.top)),
    right: Math.max(...rects.map((rect) => rect.right)),
    bottom: Math.max(...rects.map((rect) => rect.bottom)),
  };
}

function drawElement(
  context: CanvasRenderingContext2D,
  element: AirElement,
  sprites: ImageDataSpritePack['sprites'],
  bounds: { left: number; top: number; right: number; bottom: number },
) {
  const sprite = sprites.get(spriteKey(element.groupNo, element.imageNo));
  if (!sprite) return;
  const width = Math.max(1, bounds.right - bounds.left);
  const height = Math.max(1, bounds.bottom - bounds.top);
  const scale = Math.min((CANVAS_SIZE - 18) / width, (CANVAS_SIZE - 18) / height, 2);
  const originX = (CANVAS_SIZE - width * scale) / 2 - bounds.left * scale;
  const originY = (CANVAS_SIZE - height * scale) / 2 - bounds.top * scale;
  const x = originX + (element.offsetX - sprite.xAxis) * scale;
  const y = originY + (element.offsetY - sprite.yAxis) * scale;

  const frameCanvas = document.createElement('canvas');
  frameCanvas.width = sprite.imageData.width;
  frameCanvas.height = sprite.imageData.height;
  frameCanvas.getContext('2d')?.putImageData(sprite.imageData, 0, 0);

  context.save();
  context.imageSmoothingEnabled = false;
  if (element.flip?.toLowerCase().includes('h')) {
    context.translate(x + sprite.imageData.width * scale, y);
    context.scale(-1, 1);
    context.drawImage(frameCanvas, 0, 0, sprite.imageData.width * scale, sprite.imageData.height * scale);
  } else {
    context.drawImage(frameCanvas, x, y, sprite.imageData.width * scale, sprite.imageData.height * scale);
  }
  context.restore();
}

function toArrayBuffer(bytes: Uint8Array): ArrayBuffer {
  return bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength);
}
