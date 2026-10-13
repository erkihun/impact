// Lock-up layouts: emblem only, horizontal and vertical. Coordinates are in emblem units
// (the emblem artboard is 240 × 240).
import { TITLES } from './copy.mjs';
import { measure, outline, assertCovered } from './text.mjs';
import { poly } from './geometry.mjs';

const T = {
  am1: { font: 'am-semibold', size: 27, role: 'neutral', text: TITLES.amharic.city, id: 'am-city' },
  en1: { font: 'en-semibold', size: 15.5, tracking: 0.085, role: 'neutral', text: TITLES.english.city, id: 'en-city' },
  am2: { font: 'am-bold', size: 31, role: 'primary', text: TITLES.amharic.bureau, id: 'am-bureau' },
  en2a: { font: 'en-bold', size: 21, tracking: 0.035, role: 'primary', text: TITLES.english.bureauLines[0], id: 'en-bureau-1' },
  en2b: { font: 'en-bold', size: 21, tracking: 0.035, role: 'primary', text: TITLES.english.bureauLines[1], id: 'en-bureau-2' },
};
Object.values(T).forEach((t) => assertCovered(t.font, t.text));

const w = (t) => measure(t.font, t.text, t.size, t.tracking ?? 0);
const put = (t, x, y) => ({ id: t.id, role: t.role, d: outline(t.font, t.text, x, y, t.size, t.tracking ?? 0) });
const rule = (x, y, width) => ({ id: 'rule', role: 'accent', d: poly([[x, y], [x + width, y], [x + width, y + 4], [x, y + 4]]) });
const ceil = (n) => Math.ceil(n);

export function buildLayouts() {
  // Horizontal: emblem left, left-aligned text block on the emblem's optical centre line.
  const x0 = 262;
  const blockW = Math.max(w(T.am2), w(T.en2a), w(T.en2b), w(T.en1));
  const horizontal = {
    viewBox: [ceil(x0 + blockW + 8), 240],
    emblem: { x: 0, y: 0 },
    parts: [
      put(T.am1, x0, 57),
      put(T.en1, x0, 83),
      rule(x0, 99, 44),
      put(T.am2, x0, 145),
      put(T.en2a, x0, 177),
      put(T.en2b, x0, 203),
    ],
  };

  // Vertical: emblem centred over centred text.
  const W = ceil(Math.max(w(T.am2), w(T.en2b)) + 24);
  const cx = W / 2;
  const c = (t) => cx - w(t) / 2;
  const vertical = {
    viewBox: [W, 456],
    emblem: { x: (W - 240) / 2, y: 0 },
    parts: [
      put(T.am1, c(T.am1), 292),
      put(T.en1, c(T.en1), 318),
      rule(cx - 22, 336, 44),
      put(T.am2, c(T.am2), 382),
      put(T.en2a, c(T.en2a), 414),
      put(T.en2b, c(T.en2b), 440),
    ],
  };

  const emblem = { viewBox: [240, 240], emblem: { x: 0, y: 0 }, parts: [] };
  return { emblem, horizontal, vertical };
}
