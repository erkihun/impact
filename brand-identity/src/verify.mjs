// End-to-end verification of the deliverables. Exits non-zero on any failure.
//   • SVG files: well-formed, no raster/text/filter/gradient, correct root attributes
//   • Institutional titles: exact Unicode, glyph coverage
//   • Geometry: no two shapes of an emblem overlap (so one-colour output is lossless)
//   • Page: no console errors, fonts loaded, no horizontal overflow at 3 widths
//   • Real downloads: SVG + PNG 1024/2048/4096 triggered through the UI, transparent
//   • Committed PNGs: dimensions and transparent corners
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { launch } from './browser.mjs';
import { TITLES } from './copy.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
let failed = 0;
const ok = (cond, msg) => { console.log(`${cond ? '  ✓' : '  ✗'} ${msg}`); if (!cond) failed += 1; };
const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? walk(path.join(d, e.name)) : [path.join(d, e.name)]));

console.log('Institutional titles');
ok(TITLES.amharic.city === 'የአዲስ አበባ ከተማ አስተዳደር', 'Amharic city line exact');
ok(TITLES.amharic.bureau === 'የቴክኒክና ሙያ ትምህርትና ሥልጠና ቢሮ', 'Amharic bureau line exact');
ok(TITLES.english.city === 'ADDIS ABABA CITY ADMINISTRATION', 'English city line exact');
ok(TITLES.english.bureauLines.join(' ') === 'TECHNICAL AND VOCATIONAL EDUCATION AND TRAINING BUREAU', 'English bureau title exact (two-line set preserves word order)');

const browser = await launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
const errors = [];
page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
page.on('pageerror', (e) => errors.push(e.message));

console.log('SVG files');
const svgFiles = [...walk(path.join(root, 'assets/logos')), ...walk(path.join(root, 'assets/variants'))].filter((f) => f.endsWith('.svg'));
ok(svgFiles.length === 84, `84 SVG files present (found ${svgFiles.length})`);
await page.goto('about:blank');
const bad = [];
for (const f of svgFiles) {
  const src = fs.readFileSync(f, 'utf8');
  const res = await page.evaluate((s) => {
    const doc = new DOMParser().parseFromString(s, 'image/svg+xml');
    const err = doc.querySelector('parsererror');
    const svg = doc.documentElement;
    return {
      err: !!err,
      root: svg.localName,
      vb: svg.getAttribute('viewBox'),
      wh: !!svg.getAttribute('width') && !!svg.getAttribute('height'),
      forbidden: ['image', 'text', 'foreignObject', 'style', 'filter', 'linearGradient', 'radialGradient', 'mask', 'script'].filter((t) => doc.getElementsByTagName(t).length),
      fills: [...doc.querySelectorAll('path')].every((p) => /^#[0-9A-F]{6}$/i.test(p.getAttribute('fill') || '')),
    };
  }, src);
  if (res.err || res.root !== 'svg' || !res.vb || !res.wh || res.forbidden.length || !res.fills) bad.push(`${path.relative(root, f)} ${JSON.stringify(res)}`);
}
ok(bad.length === 0, 'all SVGs well-formed; vector-only (no raster, live text, filters, gradients, masks)');
bad.slice(0, 5).forEach((b) => console.log('     ', b));

await page.goto(pathToFileURL(path.join(root, 'index.html')).href);
await page.waitForSelector('html[data-ready=true]');
await page.evaluate(() => document.fonts.ready);

console.log('Geometry');
const overlaps = await page.evaluate(() => {
  const B = window.TVET_BRAND;
  const S = 480;
  const out = [];
  B.concepts.forEach((c) => {
    const masks = c.parts.map((p) => {
      const cv = document.createElement('canvas');
      cv.width = cv.height = S;
      const ctx = cv.getContext('2d');
      ctx.scale(S / 240, S / 240);
      ctx.fill(new Path2D(p.d), p.fillRule === 'evenodd' ? 'evenodd' : 'nonzero');
      return ctx.getImageData(0, 0, S, S).data;
    });
    for (let a = 0; a < masks.length; a += 1) for (let b = a + 1; b < masks.length; b += 1) {
      let n = 0;
      for (let i = 3; i < masks[a].length; i += 4) if (masks[a][i] > 200 && masks[b][i] > 200) n += 1;
      if (n > 0) out.push(`${c.key}: ${c.parts[a].id} × ${c.parts[b].id} (${n}px)`);
    }
  });
  return out;
});
ok(overlaps.length === 0, 'no overlapping shapes in any emblem (one-colour output loses no detail)');
overlaps.forEach((o) => console.log('     ', o));

console.log('Page');
ok(await page.evaluate(() => document.fonts.check("16px 'Noto Sans Ethiopic'", 'አበባ')), 'Noto Sans Ethiopic loaded');
ok(await page.evaluate(() => document.fonts.check("16px 'Inter'")), 'Inter loaded');
for (const w of [375, 768, 1440]) {
  await page.setViewportSize({ width: w, height: 900 });
  const over = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  ok(over <= 0, `no horizontal scroll at ${w}px (overflow ${over}px)`);
}
await page.setViewportSize({ width: 1440, height: 900 });

console.log('Interactive exports (UI-triggered downloads)');
await page.click('[data-studio-concept="2"]');
await page.click('[data-studio-layout="emblem"]');
const decode = (b64) => page.evaluate(async (s) => {
  const img = new Image();
  img.src = `data:image/png;base64,${s}`;
  await img.decode();
  const cv = document.createElement('canvas');
  cv.width = img.naturalWidth; cv.height = img.naturalHeight;
  const ctx = cv.getContext('2d');
  ctx.drawImage(img, 0, 0);
  const a = (x, y) => ctx.getImageData(x, y, 1, 1).data[3];
  const mid = ctx.getImageData(cv.width >> 1, cv.height >> 1, 1, 1).data;
  return { w: img.naturalWidth, h: img.naturalHeight, corners: [a(0, 0), a(cv.width - 1, 0), a(0, cv.height - 1), a(cv.width - 1, cv.height - 1)], center: [...mid], any: (() => { const all = ctx.getImageData(0, 0, cv.width, cv.height).data; for (let i = 3; i < all.length; i += 4) if (all[i] > 0) return true; return false; })() };
}, b64);
{
  const [dl] = await Promise.all([page.waitForEvent('download'), page.click('#studio-downloads [data-dl^="svg"]')]);
  const p = await dl.path();
  const txt = fs.readFileSync(p, 'utf8');
  ok(dl.suggestedFilename() === '03-green-innovation-emblem-full-color.svg' && txt.startsWith('<?xml') && txt.includes('viewBox="0 0 240 240"'), `SVG download → ${dl.suggestedFilename()}`);
  ok(txt === fs.readFileSync(path.join(root, 'assets/logos/03-green-innovation-emblem.svg'), 'utf8'), 'downloaded SVG is byte-identical to assets/logos file');
}
for (const px of [1024, 2048, 4096]) {
  const [dl] = await Promise.all([page.waitForEvent('download', { timeout: 60000 }), page.click(`#studio-downloads [data-dl$="|${px}"]`)]);
  const buf = fs.readFileSync(await dl.path());
  const sig = buf.subarray(0, 8).toString('hex') === '89504e470d0a1a0a';
  const w = buf.readUInt32BE(16); const h = buf.readUInt32BE(20); const ct = buf[25];
  const d = await decode(buf.toString('base64'));
  ok(sig && w === px && h === px && ct === 6, `PNG ${px}: ${dl.suggestedFilename()} (${w}×${h}, RGBA)`);
  ok(d.corners.every((a) => a === 0) && d.any, `PNG ${px}: transparent corners, artwork present`);
}
{
  await page.click('[data-studio-layout="horizontal"]');
  const [dl] = await Promise.all([page.waitForEvent('download'), page.click('#studio-downloads [data-dl$="|1024"]')]);
  const buf = fs.readFileSync(await dl.path());
  const w = buf.readUInt32BE(16); const h = buf.readUInt32BE(20);
  ok(w === 1024 && h === Math.round((1024 * 240) / 744), `horizontal PNG longest edge 1024 (${w}×${h})`);
  const d = await decode(buf.toString('base64'));
  ok(d.corners.every((a) => a === 0), 'horizontal PNG has transparent corners (text outlines render)');
}

console.log('Committed PNG exports');
const pngs = walk(path.join(root, 'assets/exports')).filter((f) => f.endsWith('.png'));
ok(pngs.length >= 28, `${pngs.length} PNG files in assets/exports`);
let pngBad = 0;
for (const f of pngs) {
  const buf = fs.readFileSync(f);
  const m = path.basename(f).match(/-(\d+)px\.png$/);
  const w = buf.readUInt32BE(16); const h = buf.readUInt32BE(20);
  const longest = Math.max(w, h);
  const d = await decode(buf.toString('base64'));
  if (!m || longest !== +m[1] || buf[25] !== 6 || !d.corners.every((a) => a === 0)) { pngBad += 1; console.log('     ', path.basename(f), w, h); }
}
ok(pngBad === 0, 'every PNG: correct longest edge, RGBA, transparent corners');

console.log('Runtime');
ok(errors.length === 0, `no console errors (${errors.length})`);
errors.forEach((e) => console.log('     ', e));
await browser.close();
console.log(failed ? `\n${failed} check(s) FAILED` : '\nAll checks passed');
process.exit(failed ? 1 : 0);
