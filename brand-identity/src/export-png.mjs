// Renders the committed PNG deliverables by calling the presentation's own exporter
// (window.TVETExport), i.e. the same code path as the Download buttons.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { launch } from './browser.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const dir = path.join(root, 'assets/exports');
fs.rmSync(dir, { recursive: true, force: true });
fs.mkdirSync(dir, { recursive: true });

const JOBS = [
  ['emblem', 'full-color', [1024, 2048, 4096]],
  ['emblem', 'reversed-white', [1024]],
  ['emblem', 'mono-blue', [1024]],
  ['horizontal', 'full-color', [2048]],
  ['vertical', 'full-color', [2048]],
];

const browser = await launch();
const page = await browser.newPage();
await page.goto(pathToFileURL(path.join(root, 'index.html')).href);
await page.waitForSelector('html[data-ready=true]');
const concepts = await page.evaluate(() => window.TVETExport.concepts);
let n = 0;
for (const [ci, c] of concepts.entries()) {
  for (const [layout, variant, sizes] of JOBS) {
    for (const px of sizes) {
      const b64 = await page.evaluate(([i, l, v, s]) => window.TVETExport.png(i, l, v, s), [ci, layout, variant, px]);
      fs.writeFileSync(path.join(dir, `${c.slug}-${layout}-${variant}-${px}px.png`), Buffer.from(b64, 'base64'));
      n += 1;
    }
  }
}
await browser.close();
console.log(`Wrote ${n} PNG files to assets/exports/`);
