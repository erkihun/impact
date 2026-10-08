// Shared Playwright launcher: uses PLAYWRIGHT_CHROMIUM / system Chromium when present.
import fs from 'node:fs';
import { chromium } from 'playwright-core';

export async function launch() {
  const candidates = [process.env.CHROMIUM_PATH, '/opt/pw-browsers/chromium', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'].filter(Boolean);
  const executablePath = candidates.find((p) => fs.existsSync(p));
  return chromium.launch(executablePath ? { executablePath } : {});
}
