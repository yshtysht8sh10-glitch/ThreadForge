import { unzipSync } from 'fflate';
import { GIFEncoder, applyPalette, quantize } from 'gifenc';
import { parseAirText } from './air/AirParser';
import type { AirAction, AirElement } from './air/AirTypes';
import { convertSffV1ToImageDataSpritePack } from './sprite/SffSpritePackConverter';
import { spriteKey } from './sprite/SpritePackLoader';

const CANVAS_SIZE = 220;
const MAX_FRAMES = 12;
const FRAME_DELAY_MS = 80;

export async function createIdleGifFromMugenZip(zipFile: File): Promise<File> {
  const entries = unzipSync(new Uint8Array(await zipFile.arrayBuffer()));
  const airEntry = findEntry(entries, '.air');
  const sffEntry = findEntry(entries, '.sff');
  if (!airEntry || !sffEntry) {
    throw new Error('zip内に.airと.sffが見つかりませんでした。説明用画像は任意選択してください。');
  }

  const air = parseAirText(decodeText(airEntry));
  const action = findIdleAction(air.actions);
  if (!action) {
    throw new Error('待機モーションとして使えるAIR actionが見つかりませんでした。');
  }
  const sprites = convertSffV1ToImageDataSpritePack(toArrayBuffer(sffEntry));
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

function findEntry(entries: Record<string, Uint8Array>, extension: string): Uint8Array | null {
  const names = Object.keys(entries)
    .filter((name) => name.toLowerCase().endsWith(extension))
    .sort((a, b) => a.split('/').length - b.split('/').length || a.localeCompare(b));
  return names.length ? entries[names[0]] : null;
}

function decodeText(bytes: Uint8Array): string {
  const utf8 = new TextDecoder('utf-8', { fatal: false }).decode(bytes);
  if (!utf8.includes('\uFFFD')) return utf8;
  try {
    return new TextDecoder('shift_jis').decode(bytes);
  } catch {
    return utf8;
  }
}

function findIdleAction(actions: AirAction[]): AirAction | null {
  return actions.find((action) => action.actionNo === 0 && action.elements.length > 0)
    ?? actions.find((action) => action.actionNo === 180 && action.elements.length > 0)
    ?? actions.find((action) => action.elements.length > 0)
    ?? null;
}

function animationBounds(elements: AirElement[], sprites: Map<string, { xAxis: number; yAxis: number; imageData: ImageData }>) {
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
  sprites: Map<string, { xAxis: number; yAxis: number; imageData: ImageData }>,
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
