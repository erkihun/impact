/**
 * Headless screenshot + overflow harness for UI QA.
 *
 * Uses the Chromium already cached by Playwright via the CDP protocol, so it adds
 * no npm dependency to the project. Captures each route at the configured widths
 * and reports horizontal overflow, which is the cheapest reliable responsive signal.
 *
 * Usage: node scripts/ui-shots.mjs <baseUrl> <outDir> [routesFile]
 */
import { spawn } from 'node:child_process';
import { mkdir, writeFile, readFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import net from 'node:net';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8123';
const OUT = process.argv[3] ?? 'output/ui-shots';
const ROUTES_FILE = process.argv[4] ?? null;

const CHROME_CANDIDATES = [
    path.join(process.env.LOCALAPPDATA ?? '', 'ms-playwright/chromium-1219/chrome-win64/chrome.exe'),
    path.join(process.env.LOCALAPPDATA ?? '', 'ms-playwright/chromium-1208/chrome-win64/chrome.exe'),
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
];

const VIEWPORTS = [
    { name: 'mobile', width: 390, height: 900 },
    { name: 'tablet', width: 768, height: 1100 },
    { name: 'desktop', width: 1280, height: 1000 },
    { name: 'wide', width: 1920, height: 1080 },
];

const DEFAULT_ROUTES = [
    ['home', '/en'],
    ['services-index', '/en/services'],
    ['industries-index', '/en/industries'],
    ['experts-index', '/en/experts'],
    ['case-studies-index', '/en/case-studies'],
    ['insights-index', '/en/insights'],
    ['events-index', '/en/events'],
    ['careers-index', '/en/careers'],
    ['about', '/en/about'],
    ['contact', '/en/contact'],
    ['consultation', '/en/consultation'],
    ['rfp', '/en/request-for-proposal'],
    ['search', '/en/search?q=strategy'],
    ['privacy', '/en/privacy'],
    ['accessibility', '/en/accessibility'],
    ['404', '/en/definitely-not-a-real-page'],
    ['login', '/login'],
    ['home-am', '/am'],
    ['services-am', '/am/services'],
    ['consultation-am', '/am/consultation'],
];

const findChrome = () => {
    for (const candidate of CHROME_CANDIDATES) {
        if (candidate && existsSync(candidate)) return candidate;
    }
    throw new Error('No Chromium binary found.');
};

const freePort = () =>
    new Promise((resolve) => {
        const srv = net.createServer();
        srv.listen(0, () => {
            const { port } = srv.address();
            srv.close(() => resolve(port));
        });
    });

const send = (ws, id, method, params, sessionId) =>
    ws.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));

async function main() {
    const chrome = findChrome();
    const port = await freePort();
    const userDataDir = path.join(process.env.TEMP ?? '.', `ui-shots-${Date.now()}`);

    const routes = ROUTES_FILE
        ? JSON.parse(await readFile(ROUTES_FILE, 'utf8'))
        : DEFAULT_ROUTES;

    const proc = spawn(chrome, [
        `--remote-debugging-port=${port}`,
        `--user-data-dir=${userDataDir}`,
        '--headless=new',
        '--no-first-run',
        '--no-default-browser-check',
        '--disable-gpu',
        '--hide-scrollbars',
        '--force-color-profile=srgb',
    ], { stdio: 'ignore' });

    const endpoint = await (async () => {
        for (let i = 0; i < 60; i += 1) {
            try {
                const res = await fetch(`http://127.0.0.1:${port}/json/version`);
                const json = await res.json();
                if (json.webSocketDebuggerUrl) return json.webSocketDebuggerUrl;
            } catch {
                /* keep polling until the browser is listening */
            }
            await new Promise((r) => setTimeout(r, 250));
        }
        throw new Error('Chromium did not expose a debugging endpoint.');
    })();

    await mkdir(OUT, { recursive: true });

    const ws = new WebSocket(endpoint);
    await new Promise((resolve, reject) => {
        ws.addEventListener('open', resolve, { once: true });
        ws.addEventListener('error', reject, { once: true });
    });

    let messageId = 0;
    const pending = new Map();
    ws.addEventListener('message', (event) => {
        const msg = JSON.parse(event.data);
        if (msg.id && pending.has(msg.id)) {
            pending.get(msg.id)(msg);
            pending.delete(msg.id);
        }
    });

    const call = (method, params = {}, sessionId) =>
        new Promise((resolve) => {
            const id = ++messageId;
            pending.set(id, resolve);
            send(ws, id, method, params, sessionId);
        });

    const { result: target } = await call('Target.createTarget', { url: 'about:blank' });
    const { result: attached } = await call('Target.attachToTarget', {
        targetId: target.targetId,
        flatten: true,
    });
    const session = attached.sessionId;

    await call('Page.enable', {}, session);
    await call('Runtime.enable', {}, session);

    const findings = [];

    // Optional authenticated pass: AUTH_EMAIL/AUTH_PASSWORD sign in before capture so
    // admin routes render as a real operator sees them rather than as a redirect.
    if (process.env.AUTH_EMAIL && process.env.AUTH_PASSWORD) {
        await call('Emulation.setDeviceMetricsOverride', {
            width: 1280, height: 1000, deviceScaleFactor: 1, mobile: false,
        }, session);
        await call('Page.navigate', { url: `${BASE}/login` }, session);
        await new Promise((r) => setTimeout(r, 1200));

        await call('Runtime.evaluate', {
            expression: `(() => {
                const f = document.querySelector('form[action*="login"]') || document.querySelector('form');
                if (!f) return 'no-form';
                const email = f.querySelector('input[type=email], input[name=email]');
                const pass = f.querySelector('input[type=password]');
                if (!email || !pass) return 'no-fields';
                const set = (el, v) => {
                    const proto = Object.getPrototypeOf(el);
                    Object.getOwnPropertyDescriptor(proto, 'value').set.call(el, v);
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                };
                set(email, ${JSON.stringify(process.env.AUTH_EMAIL)});
                set(pass, ${JSON.stringify(process.env.AUTH_PASSWORD)});
                f.submit();
                return 'submitted';
            })()`,
            returnByValue: true,
        }, session);
        await new Promise((r) => setTimeout(r, 2000));

        // MFA is mandatory for privileged accounts; supply the current TOTP when challenged.
        // AUTH_TOTP_CMD is preferred: it is run at this moment, so the code cannot expire
        // between generation and submission the way a pre-computed value can.
        if (process.env.AUTH_TOTP_CMD) {
            const { execSync } = await import('node:child_process');
            process.env.AUTH_TOTP = execSync(process.env.AUTH_TOTP_CMD, { encoding: 'utf8' }).trim();
        }

        if (process.env.AUTH_TOTP) {
            await call('Runtime.evaluate', {
                expression: `(() => {
                    const f = document.querySelector('form');
                    if (!f) return 'no-form';
                    const code = f.querySelector('input[name=code], input[inputmode=numeric], input[type=text]');
                    if (!code) return 'no-code-field';
                    const proto = Object.getPrototypeOf(code);
                    Object.getOwnPropertyDescriptor(proto, 'value').set.call(code, ${JSON.stringify(process.env.AUTH_TOTP)});
                    code.dispatchEvent(new Event('input', { bubbles: true }));
                    f.submit();
                    return 'submitted';
                })()`,
                returnByValue: true,
            }, session);
            await new Promise((r) => setTimeout(r, 2000));
        }

        const { result: who } = await call('Runtime.evaluate', {
            expression: 'JSON.stringify({ url: location.pathname, title: document.title })',
            returnByValue: true,
        }, session);
        console.log('post-login:', who?.result?.value);
    }

    for (const [name, route] of routes) {
        for (const vp of VIEWPORTS) {
            await call('Emulation.setDeviceMetricsOverride', {
                width: vp.width,
                height: vp.height,
                deviceScaleFactor: 1,
                mobile: vp.width < 768,
            }, session);

            await call('Page.navigate', { url: `${BASE}${route}` }, session);
            // Settle: allow Blade render, fonts and Alpine hydration to finish.
            await new Promise((r) => setTimeout(r, 1600));

            const { result: probe } = await call('Runtime.evaluate', {
                expression: `JSON.stringify({
                    scrollW: document.documentElement.scrollWidth,
                    clientW: document.documentElement.clientWidth,
                    title: document.title,
                    h1: document.querySelectorAll('h1').length,
                    lang: document.documentElement.lang
                })`,
                returnByValue: true,
            }, session);

            let data = {};
            try { data = JSON.parse(probe?.result?.value ?? '{}'); } catch { /* ignore */ }
            const overflow = Math.max(0, (data.scrollW ?? 0) - (data.clientW ?? 0));

            findings.push({ route: name, path: route, viewport: vp.name, width: vp.width, overflow, h1: data.h1, lang: data.lang });

            const { result: shot } = await call('Page.captureScreenshot', {
                format: 'png',
                captureBeyondViewport: true,
            }, session);

            if (shot?.data) {
                await writeFile(path.join(OUT, `${name}-${vp.name}.png`), Buffer.from(shot.data, 'base64'));
            }
        }
    }

    await writeFile(path.join(OUT, 'findings.json'), JSON.stringify(findings, null, 2));

    const overflows = findings.filter((f) => f.overflow > 0);
    const badH1 = findings.filter((f) => f.h1 !== 1 && f.viewport === 'desktop');
    console.log(`captured ${findings.length} screenshots across ${routes.length} routes`);
    console.log(`horizontal overflow failures: ${overflows.length}`);
    overflows.forEach((f) => console.log(`  OVERFLOW ${f.route} @${f.width} = ${f.overflow}px`));
    console.log(`routes without exactly one H1 (desktop): ${badH1.length}`);
    badH1.forEach((f) => console.log(`  H1=${f.h1} ${f.route}`));

    ws.close();
    proc.kill();
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
