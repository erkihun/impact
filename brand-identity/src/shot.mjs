import { chromium } from 'playwright-core';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
const [,, out='/tmp/page.png', width='1440', sel] = process.argv;
const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const p = await b.newPage({ viewport: { width: +width, height: 900 } });
const errs = [];
p.on('console', m => { if (['error','warning'].includes(m.type())) errs.push(m.type()+': '+m.text()); });
p.on('pageerror', e => errs.push('pageerror: '+e.message));
await p.goto(pathToFileURL(path.resolve('index.html')).href);
await p.waitForSelector('html[data-ready=true]');
await p.evaluate(() => document.fonts.ready);
if (sel) { await (await p.$(sel)).screenshot({ path: out }); } else { await p.screenshot({ path: out, fullPage: true }); }
console.log('errors:', errs);
await b.close();
