// Shared Playwright launcher. Finds an installed Chromium-family browser on Linux, macOS
// or Windows (override with CHROMIUM_PATH); falls back to the Chrome / Edge channels.
import fs from 'node:fs';
import { chromium } from 'playwright-core';

const env = process.env;
const candidates = [
  env.CHROMIUM_PATH,
  '/opt/pw-browsers/chromium',
  '/usr/bin/chromium',
  '/usr/bin/chromium-browser',
  '/usr/bin/google-chrome',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge',
  `${env.PROGRAMFILES}\\Google\\Chrome\\Application\\chrome.exe`,
  `${env['PROGRAMFILES(X86)']}\\Google\\Chrome\\Application\\chrome.exe`,
  `${env.LOCALAPPDATA}\\Google\\Chrome\\Application\\chrome.exe`,
  `${env.PROGRAMFILES}\\Microsoft\\Edge\\Application\\msedge.exe`,
  `${env['PROGRAMFILES(X86)']}\\Microsoft\\Edge\\Application\\msedge.exe`,
].filter(Boolean);

export async function launch() {
  const executablePath = candidates.find((p) => fs.existsSync(p));
  if (executablePath) return chromium.launch({ executablePath });
  for (const channel of ['chrome', 'msedge']) {
    try { return await chromium.launch({ channel }); } catch { /* try next */ }
  }
  throw new Error('No Chromium-family browser found. Install Chrome or Edge, or set CHROMIUM_PATH to its executable.');
}
