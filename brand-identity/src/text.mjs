// Converts live text to vector outlines so lock-ups render identically everywhere
// (Illustrator, Inkscape, browsers, <img>/canvas export) without installed fonts.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import opentype from 'opentype.js';

const here = path.dirname(fileURLToPath(import.meta.url));
const load = (file) => {
  const b = fs.readFileSync(path.join(here, 'fonts', file));
  return opentype.parse(b.buffer.slice(b.byteOffset, b.byteOffset + b.byteLength));
};

export const FONTS = {
  'en-semibold': load('inter-latin-600-normal.woff'),
  'en-bold': load('inter-latin-700-normal.woff'),
  'am-semibold': load('noto-sans-ethiopic-ethiopic-600-normal.woff'),
  'am-bold': load('noto-sans-ethiopic-ethiopic-700-normal.woff'),
};

/** Width of a string in layout units. tracking is in em. */
export function measure(fontKey, text, size, tracking = 0) {
  const f = FONTS[fontKey];
  // letter-spacing is applied after every glyph; drop the trailing one for true optical width
  return f.getAdvanceWidth(text, size, { kerning: true, letterSpacing: tracking }) - tracking * size;
}

/** Outline path data with the baseline at y and the left edge at x. */
export function outline(fontKey, text, x, y, size, tracking = 0) {
  const f = FONTS[fontKey];
  const p = f.getPath(text, x, y, size, { kerning: true, letterSpacing: tracking });
  return p.toPathData(2);
}

/** Verify every character resolves to a real glyph (guards against tofu). */
export function assertCovered(fontKey, text) {
  const f = FONTS[fontKey];
  const missing = [...text].filter((c) => c !== ' ' && f.charToGlyphIndex(c) === 0);
  if (missing.length) throw new Error(`Font ${fontKey} lacks glyphs for: ${missing.join(' ')}`);
}
