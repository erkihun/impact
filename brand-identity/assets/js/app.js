/* TVET Bureau identity presentation — interaction layer.
   Depends on assets/js/brand-data.js (window.TVET_BRAND), which carries the vector
   geometry and the same compose() function used to write the .svg files in /assets. */
(() => {
  'use strict';

  const B = window.TVET_BRAND;
  if (!B) {
    document.body.insertAdjacentHTML('afterbegin', '<p style="padding:1rem;background:#fee;color:#900">brand-data.js failed to load — run <code>npm run build:assets</code>.</p>');
    return;
  }

  /* ───────────────────────── helpers ───────────────────────── */
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
  const ORG = 'Addis Ababa City Administration Technical and Vocational Education and Training Bureau';

  const VARIANT_KEYS = ['full-color', 'mono-blue', 'mono-black', 'grayscale', 'reversed-white', 'reversed-color'];
  const LAYOUT_KEYS = ['emblem', 'horizontal', 'vertical'];
  const LAYOUT_LABEL = { emblem: 'Emblem', horizontal: 'Horizontal', vertical: 'Vertical' };
  const PNG_SIZES = [512, 1024, 2048, 4096];
  const BGS = [
    { key: 'white', label: 'White', color: '#FFFFFF' },
    { key: 'mist', label: 'Mist', color: '#E9EEF4' },
    { key: 'blue', label: 'Institutional Blue', color: '#123B75' },
    { key: 'navy', label: 'Navy', color: '#0A1F3D' },
    { key: 'black', label: 'Black', color: '#111111' },
    { key: 'clear', label: 'Transparent', color: null },
  ];

  const CONTENT = [
    {
      tagline: 'A learner at the centre of the machine.',
      summary:
        'The most literal reading of the Bureau’s mission: technical education (the gear) holding a human being (the learner) whose raised arms are an open book. One circle, one idea, instantly legible.',
      elements: [
        ['primary', 'Ten-tooth gear', 'Technical education and industrial production. Evenly spaced teeth express standardised, systematic training.'],
        ['secondary', 'Open book', 'Knowledge and competence. The two pages double as the raised arms of the figure — learning as an act of reaching up.'],
        ['accent', 'Orange sphere', 'The learner’s head and the spark of innovation — the only warm note in the mark.'],
        ['primary', 'Circular aperture', 'Negative space that frames the learner like an official seal and keeps the mark open, not closed.'],
      ],
      scores: [3, 5, 5, 5, 5],
    },
    {
      tagline: 'Every stage of the philosophy, in one climbing form.',
      summary:
        'An open book whose right-hand page becomes three saw-tooth roof steps: the unmistakable silhouette of industry. Knowledge turns into Skills, Innovation and Productivity, and the climb ends at a figure on the summit.',
      elements: [
        ['secondary', 'Left page', 'Knowledge — where every training pathway begins.'],
        ['primary', 'Saw-tooth steps', 'Skills, Innovation, Productivity. The saw-tooth is the universal profile of a workshop or factory roof.'],
        ['primary', 'Rising baseline', 'Each step lifts 26 units: progress is cumulative, never a single leap.'],
        ['accent', 'Orange figure', 'Sustainable development, achieved through people — a sun over the roofline and a person at the summit.'],
      ],
      scores: [5, 3, 4, 4, 3],
    },
    {
      tagline: 'Precision engineering that grows.',
      summary:
        'A hexagon — the shape of a nut, a bolt head and a honeycomb cell — shelters a seedling. The leaves are cut with circuit-like veins, and read equally as the raised arms of a person: human development and growth as one form.',
      elements: [
        ['primary', 'Hexagonal frame', 'Engineered precision and the modular cell of a connected system. Six equal sides, one tolerance.'],
        ['secondary', 'Twin leaves', 'Green TVET and sustainable growth. Each leaf is a lens built from two equal arcs.'],
        ['secondary', 'Vein slits', 'Leaf veins that double as circuit traces: technology grown from, not imposed on, nature.'],
        ['accent', 'Orange node', 'The spark of innovation — a sun, a node on the circuit, and a head above the arms.'],
      ],
      scores: [4, 4, 4, 4, 4],
    },
    {
      tagline: 'Authority, certification and trust — nothing more.',
      summary:
        'The most restrained of the four. A heraldic shield carries three rising chevrons, the tiered levels of a competency framework, beneath a single four-point star of excellence. It signs documents and certificates with quiet authority.',
      elements: [
        ['primary', 'Shield with inset line', 'Trust, quality assurance and public authority; the inset line echoes the border of an official certificate.'],
        ['primary', 'Outer chevrons', 'Graded competency levels — certified, progressive, upward.'],
        ['secondary', 'Green chevron', 'The sustainable (Green TVET) thread running through every level.'],
        ['accent', 'Four-point star', 'Excellence and innovation — the light at the top of the framework.'],
      ],
      scores: [2, 4, 5, 3, 5],
    },
  ];
  const CRITERIA = ['Distinctiveness', 'Small-size legibility', 'One-colour robustness', 'Symbolic coverage', 'Versatility (seal, ID, signage)'];

  const store = {
    get(k, d) { try { const v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch { /* storage unavailable */ } },
  };

  const state = {
    concept: 0,
    layout: 'horizontal',
    variant: 'full-color',
    bg: 'white',
    chosen: store.get('tvet.chosen', 0),
    compare: [0, 1, 2, 3],
    cmpSize: 120,
    cmpDark: false,
  };
  if (!(state.chosen >= 0 && state.chosen < 4)) state.chosen = 0;
  state.concept = state.chosen;

  /* ───────────────────────── vector composition ───────────────────────── */
  const concept = (i) => B.concepts[i];
  const fileBase = (i, layout, variant) => `${concept(i).slug}-${layout}-${variant}`;

  function svgFor(i, layout, variant) {
    const c = concept(i);
    return B.compose(B.layouts[layout], c.parts, B.variants[variant], {
      title: `${c.name} — ${ORG}`,
      desc: `${B.variants[variant].label} ${layout} logo, concept ${c.number}: ${c.name}.`,
    });
  }
  const viewBoxOf = (svg) => svg.match(/viewBox="0 0 ([\d.]+) ([\d.]+)"/).slice(1).map(Number);

  /** Strip file-level metadata/ids so many copies can sit in one DOM. */
  function inline(svg, label) {
    const [w, h] = viewBoxOf(svg);
    return svg
      .replace(/<\?xml[^>]*\?>\s*/, '')
      .replace(/<title[\s\S]*?<\/title>\s*/, '')
      .replace(/<desc[\s\S]*?<\/desc>\s*/, '')
      .replace(/<svg [^>]*>/, `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" role="img" aria-label="${esc(label)}" focusable="false">`)
      .replace(/ id="[^"]*"/g, '');
  }
  const logoHtml = (i, layout, variant, label) => inline(svgFor(i, layout, variant), label || `${concept(i).name} ${layout} logo`);
  const aspect = (layout) => B.layouts[layout].viewBox.join(' / ');

  /* ───────────────────────── export (real files) ───────────────────────── */
  function saveBlob(blob, name) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 5000);
  }

  /** Rasterise an SVG string to a transparent PNG whose longest edge is `longEdge` px. */
  async function svgToPng(svg, longEdge) {
    const [w, h] = viewBoxOf(svg);
    const k = longEdge / Math.max(w, h);
    const pw = Math.round(w * k);
    const ph = Math.round(h * k);
    const sized = svg.replace(/(<svg[^>]*?) width="[^"]*" height="[^"]*"/, `$1 width="${pw}" height="${ph}"`);
    const url = URL.createObjectURL(new Blob([sized], { type: 'image/svg+xml;charset=utf-8' }));
    try {
      const img = new Image();
      img.src = url;
      await img.decode();
      const canvas = document.createElement('canvas');
      canvas.width = pw;
      canvas.height = ph;
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, pw, ph); // never paint a background: output stays transparent
      ctx.drawImage(img, 0, 0, pw, ph);
      return await new Promise((resolve, reject) =>
        canvas.toBlob((b) => (b ? resolve(b) : reject(new Error('PNG encoding failed'))), 'image/png'),
      );
    } finally {
      URL.revokeObjectURL(url);
    }
  }

  const toastEl = $('#toast');
  let toastTimer;
  function toast(msg, tone = 'ok') {
    toastEl.textContent = msg;
    toastEl.dataset.tone = tone;
    toastEl.classList.remove('opacity-0', 'translate-y-2');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.add('opacity-0', 'translate-y-2'), 3200);
  }

  async function exportSvg(i, layout, variant) {
    const name = `${fileBase(i, layout, variant)}.svg`;
    saveBlob(new Blob([svgFor(i, layout, variant)], { type: 'image/svg+xml' }), name);
    toast(`Saved ${name}`);
  }
  async function exportPng(i, layout, variant, px) {
    const name = `${fileBase(i, layout, variant)}-${px}px.png`;
    toast(`Rendering ${px.toLocaleString()} px PNG…`, 'busy');
    try {
      saveBlob(await svgToPng(svgFor(i, layout, variant), px), name);
      toast(`Saved ${name} (transparent)`);
    } catch (err) {
      console.error(err);
      toast('PNG export failed in this browser — use the SVG instead.', 'error');
    }
  }
  // Used by src/export-png.mjs and src/verify.mjs so the committed PNGs come from the
  // exact code path a visitor triggers with the Download buttons.
  window.TVETExport = {
    svg: svgFor,
    png: async (i, layout, variant, px) => {
      const blob = await svgToPng(svgFor(i, layout, variant), px);
      const buf = new Uint8Array(await blob.arrayBuffer());
      let bin = '';
      for (let k = 0; k < buf.length; k += 0x8000) bin += String.fromCharCode.apply(null, buf.subarray(k, k + 0x8000));
      return btoa(bin);
    },
    concepts: B.concepts.map((c) => ({ slug: c.slug, name: c.name })),
  };

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-dl]');
    if (!btn) return;
    const [kind, ci, layout, variant, px] = btn.dataset.dl.split('|');
    if (kind === 'svg') exportSvg(+ci, layout, variant);
    else exportPng(+ci, layout, variant, +px);
  });

  /* ───────────────────────── colour maths ───────────────────────── */
  const lum = (hex) => {
    const c = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255).map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  };
  const contrast = (a, b) => {
    const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p);
    return (x + 0.05) / (y + 0.05);
  };
  const rgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(', ');

  /* ───────────────────────── hero + header ───────────────────────── */
  function renderHero() {
    $('#hero-grid').innerHTML = B.concepts
      .map(
        (c, i) => `
      <button type="button" data-open-studio="${i}" class="group relative aspect-square rounded-2xl bg-white/[.06] p-5 ring-1 ring-white/15 transition hover:bg-white/10 sm:p-7" aria-label="Open ${esc(c.name)} in the studio">
        <span class="fit-svg block h-full w-full">${logoHtml(i, 'emblem', 'reversed-color', `${c.name} emblem`)}</span>
        <span class="absolute left-3 top-3 text-[11px] font-semibold tracking-widest text-white/60 num">0${c.number}</span>
      </button>`,
      )
      .join('');
  }

  function renderHeaderMark() {
    $('#header-mark').innerHTML = logoHtml(state.chosen, 'emblem', 'full-color', 'Selected emblem');
  }

  /* ───────────────────────── concept cards ───────────────────────── */
  function renderConcepts() {
    $('#concept-cards').innerHTML = B.concepts
      .map((c, i) => {
        const k = CONTENT[i];
        const rec = i === 0;
        return `
      <article class="card flex flex-col overflow-hidden" aria-labelledby="c${i}-title" data-card="${i}">
        <div class="relative grid place-items-center bg-gradient-to-b from-white to-brand-mist px-6 py-8">
          <div class="fit-svg aspect-square w-full max-w-[300px]">${logoHtml(i, 'emblem', 'full-color', `${c.name} emblem`)}</div>
          <span class="absolute left-5 top-5 rounded-full bg-brand-blue px-2.5 py-1 text-[11px] font-semibold tracking-wider text-white">CONCEPT 0${c.number}</span>
          ${rec ? '<span class="absolute right-5 top-5 rounded-full bg-brand-orange px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-brand-ink">Recommended</span>' : ''}
        </div>
        <div class="flex flex-1 flex-col gap-4 p-6">
          <header>
            <h3 id="c${i}-title" class="text-xl font-bold text-brand-blue">${esc(c.name)}</h3>
            <p class="mt-1 text-sm font-medium text-brand-green">${esc(k.tagline)}</p>
          </header>
          <p class="text-sm leading-relaxed text-brand-gray">${esc(k.summary)}</p>
          <dl class="grid gap-2.5 border-t border-brand-line pt-4">
            ${k.elements
              .map(
                ([role, name, text]) => `
              <div class="grid grid-cols-[14px_1fr] gap-x-3">
                <span class="mt-1.5 h-3 w-3 rounded-full" style="background:${B.variants['full-color'][role]}"></span>
                <div><dt class="text-sm font-semibold text-brand-ink">${esc(name)}</dt><dd class="text-[13px] leading-snug text-brand-gray">${esc(text)}</dd></div>
              </div>`,
              )
              .join('')}
          </dl>
          <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-brand-line pt-4">
            <button type="button" class="btn btn-primary btn-sm" data-open-studio="${i}">Open in studio</button>
            <label class="chip cursor-pointer select-none"><input type="checkbox" class="accent-[#123B75]" data-compare-toggle="${i}" ${state.compare.includes(i) ? 'checked' : ''}> Compare</label>
            <span class="ml-auto flex gap-1.5">
              <button type="button" class="btn btn-sm" data-dl="svg|${i}|emblem|full-color" aria-label="Download ${esc(c.name)} emblem as SVG">SVG</button>
              <button type="button" class="btn btn-sm" data-dl="png|${i}|emblem|full-color|1024" aria-label="Download ${esc(c.name)} emblem as 1024 pixel PNG">PNG</button>
            </span>
          </div>
        </div>
      </article>`;
      })
      .join('');
  }

  /* ───────────────────────── studio ───────────────────────── */
  const bgOf = (key) => BGS.find((b) => b.key === key);

  function renderStudioControls() {
    $('#studio-concepts').innerHTML = B.concepts
      .map(
        (c, i) => `
      <button type="button" role="tab" aria-selected="${i === state.concept}" data-studio-concept="${i}" class="flex items-center gap-3 rounded-xl border px-3 py-2 text-left transition ${i === state.concept ? 'border-brand-blue bg-white shadow-card' : 'border-transparent hover:bg-white/70'}">
        <span class="fit-svg h-10 w-10 shrink-0">${logoHtml(i, 'emblem', 'full-color', '')}</span>
        <span class="min-w-0"><span class="block text-[11px] font-semibold tracking-wider text-brand-gray num">CONCEPT 0${c.number}</span><span class="block truncate text-sm font-semibold text-brand-blue">${esc(c.short)}</span></span>
      </button>`,
      )
      .join('');

    $('#studio-layouts').innerHTML = LAYOUT_KEYS.map((k) => `<button type="button" data-studio-layout="${k}" aria-pressed="${k === state.layout}">${LAYOUT_LABEL[k]}</button>`).join('');

    $('#studio-variants').innerHTML = VARIANT_KEYS.map((k) => {
      const v = B.variants[k];
      const swatch = k.startsWith('reversed') ? `background:${v.surface}` : 'background:#fff';
      return `<button type="button" data-studio-variant="${k}" aria-pressed="${k === state.variant}" class="flex items-center gap-2.5 rounded-lg border px-2.5 py-2 text-left text-[13px] font-medium transition ${k === state.variant ? 'border-brand-blue bg-white text-brand-blue shadow-sm' : 'border-brand-line bg-white/60 text-brand-gray hover:border-brand-blue'}">
        <span class="relative grid h-6 w-6 shrink-0 place-items-center rounded-md border border-brand-line" style="${swatch}"><span class="h-2 w-2 rounded-full" style="background:${v.accent}"></span><span class="absolute bottom-0.5 left-0.5 h-1.5 w-1.5 rounded-full" style="background:${v.primary}"></span><span class="absolute bottom-0.5 right-0.5 h-1.5 w-1.5 rounded-full" style="background:${v.secondary}"></span></span>
        <span>${esc(v.label.replace('Monochrome · ', 'Mono · ').replace('Reversed · ', 'Reversed '))}</span></button>`;
    }).join('');

    $('#studio-bgs').innerHTML = BGS.map(
      (b) => `<button type="button" data-studio-bg="${b.key}" aria-pressed="${b.key === state.bg}" title="${esc(b.label)}" aria-label="${esc(b.label)} background" class="h-8 w-8 rounded-full border-2 transition ${b.key === state.bg ? 'border-brand-orange ring-2 ring-brand-orange/30' : 'border-brand-line'} ${b.color ? '' : 'checker'}" ${b.color ? `style="background:${b.color}"` : ''}></button>`,
    ).join('');
  }

  const MAXW = { emblem: 380, horizontal: 760, vertical: 360 };

  function renderStage() {
    const { concept: ci, layout, variant, bg } = state;
    const b = bgOf(bg);
    const stage = $('#studio-stage');
    stage.className = `grid min-h-[340px] place-items-center rounded-2xl border border-brand-line p-6 sm:p-10 transition-colors ${b.color ? '' : 'checker'}`;
    stage.style.background = b.color || '';
    stage.innerHTML = `<div class="fit-svg" style="aspect-ratio:${aspect(layout)};width:min(100%,${MAXW[layout]}px)">${logoHtml(ci, layout, variant)}</div>`;

    const c = concept(ci);
    const [w, h] = B.layouts[layout].viewBox;
    $('#studio-meta').innerHTML = `
      <span class="chip">${esc(c.name)}</span><span class="chip">${esc(LAYOUT_LABEL[layout])}</span><span class="chip">${esc(B.variants[variant].label)}</span>
      <span class="chip num">${w} × ${h} units</span><span class="chip">Transparent background</span><span class="chip">Text outlined to vector</span>`;

    // Legibility guard: warn when the chosen variant would fail against the chosen surface.
    const surface = b.color || '#FFFFFF';
    const v = B.variants[variant];
    const ratio = contrast(v.primary, surface);
    const warn = $('#studio-warning');
    if (ratio < 3) {
      const fix = lum(surface) < 0.2 ? 'reversed-color' : 'full-color';
      warn.hidden = false;
      warn.innerHTML = `<strong class="font-semibold">Low contrast (${ratio.toFixed(1)}:1).</strong> ${B.variants[variant].label} will not hold on ${esc(b.label)}. <button type="button" class="font-semibold underline underline-offset-2" data-studio-variant="${fix}">Switch to ${esc(B.variants[fix].label)}</button>`;
    } else {
      warn.hidden = true;
    }

    $('#studio-downloads').innerHTML = `
      <button type="button" class="btn btn-primary" data-dl="svg|${ci}|${layout}|${variant}">Download SVG</button>
      ${PNG_SIZES.map((px) => `<button type="button" class="btn" data-dl="png|${ci}|${layout}|${variant}|${px}">PNG ${px.toLocaleString()} px</button>`).join('')}
      <button type="button" class="btn" id="copy-svg">Copy SVG code</button>`;
  }

  function renderVariantGrid() {
    const ci = state.concept;
    const layout = state.layout;
    $('#variant-grid').innerHTML = VARIANT_KEYS.map((k) => {
      const v = B.variants[k];
      const dark = k.startsWith('reversed');
      return `
      <figure class="overflow-hidden rounded-2xl border border-brand-line bg-white shadow-card">
        <div class="grid place-items-center p-6" style="background:${dark ? v.surface : '#fff'};min-height:210px">
          <div class="fit-svg" style="aspect-ratio:${aspect(layout)};width:min(100%,${layout === 'horizontal' ? 330 : layout === 'vertical' ? 170 : 150}px)">${logoHtml(ci, layout, k)}</div>
        </div>
        <figcaption class="flex items-center justify-between gap-2 border-t border-brand-line px-4 py-3">
          <span class="text-sm font-semibold text-brand-blue">${esc(v.label)}</span>
          <span class="flex gap-1.5"><button type="button" class="btn btn-sm" data-dl="svg|${ci}|${layout}|${k}">SVG</button><button type="button" class="btn btn-sm" data-dl="png|${ci}|${layout}|${k}|1024">PNG</button></span>
        </figcaption>
      </figure>`;
    }).join('');
  }

  function renderLayoutGrid() {
    const ci = state.concept;
    $('#layout-grid').innerHTML = LAYOUT_KEYS.map((k) => {
      const widths = { emblem: 190, horizontal: 440, vertical: 230 };
      const tile = (variant, surface) => `<div class="grid place-items-center p-8" style="background:${surface}"><div class="fit-svg" style="aspect-ratio:${aspect(k)};width:min(100%,${widths[k]}px)">${logoHtml(ci, k, variant)}</div></div>`;
      return `
      <section class="card overflow-hidden ${k === 'horizontal' ? 'lg:col-span-2' : ''}" aria-label="${LAYOUT_LABEL[k]} layout">
        <div class="grid ${k === 'horizontal' ? 'sm:grid-cols-2' : 'grid-cols-2'}">${tile('full-color', '#fff')}${tile('reversed-color', '#123B75')}</div>
        <div class="flex items-center justify-between border-t border-brand-line px-5 py-3">
          <div><h3 class="text-sm font-bold text-brand-blue">${LAYOUT_LABEL[k]}</h3><p class="text-xs text-brand-gray">${{ emblem: 'Standalone symbol — app icons, stamps, avatars, favicons, embossing.', horizontal: 'Primary lock-up — letterheads, web headers, signage, documents.', vertical: 'Stacked lock-up — certificates, covers, banners, square formats.' }[k]}</p></div>
          <span class="flex gap-1.5"><button type="button" class="btn btn-sm" data-dl="svg|${ci}|${k}|full-color">SVG</button><button type="button" class="btn btn-sm" data-dl="png|${ci}|${k}|full-color|2048">PNG</button></span>
        </div>
      </section>`;
    }).join('');
  }

  /* ───────────────────────── palette ───────────────────────── */
  const PALETTE = [
    { name: 'Institutional Blue', hex: B.palette.blue, role: 'Primary', share: 60, use: 'Structure, wordmark, authority. Always the dominant colour.', psych: 'Trust, stability and public authority — the colour of institutions people rely on.' },
    { name: 'Innovation Green', hex: B.palette.green, role: 'Secondary', share: 25, use: 'Knowledge, growth, Green TVET programmes.', psych: 'Growth, renewal and sustainability — the “Green TVET” commitment.' },
    { name: 'Innovation Orange', hex: B.palette.orange, role: 'Accent', share: 5, use: 'One accent per composition: the spark, the rule.', psych: 'Energy, craft and ingenuity. Scarcity keeps it meaningful.' },
    { name: 'Technical Gray', hex: B.palette.gray, role: 'Neutral', share: 10, use: 'Secondary text, rules, technical drawings.', psych: 'Precision and neutrality; carries the supporting voice.' },
    { name: 'White', hex: B.palette.white, role: 'Ground', share: 0, use: 'Clear space, reversed lettering, document stock.', psych: 'Clarity, openness and inclusion.' },
  ];

  function renderPalette() {
    $('#palette-grid').innerHTML = PALETTE.map((p) => {
      const onWhite = contrast(p.hex, '#FFFFFF');
      const onBlue = contrast(p.hex, B.palette.blue);
      const light = lum(p.hex) > 0.5;
      return `
      <article class="card overflow-hidden">
        <button type="button" data-copy="${p.hex}" class="group relative block h-36 w-full text-left" style="background:${p.hex}" aria-label="Copy ${p.hex}">
          <span class="absolute bottom-3 left-4 text-lg font-bold num ${light ? 'text-brand-ink' : 'text-white'}">${p.hex}</span>
          <span class="absolute right-4 top-3 rounded-full px-2 py-0.5 text-[11px] font-semibold opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100 ${light ? 'bg-brand-ink/10 text-brand-ink' : 'bg-white/20 text-white'}">Copy</span>
        </button>
        <div class="space-y-2 p-4">
          <div class="flex items-baseline justify-between"><h3 class="font-bold text-brand-blue">${p.name}</h3><span class="eyebrow !text-brand-gray">${p.role}</span></div>
          <p class="text-[13px] leading-snug text-brand-gray">${p.use}</p>
          <p class="text-[13px] leading-snug text-brand-gray"><em class="not-italic font-semibold text-brand-ink">Psychology · </em>${p.psych}</p>
          <p class="num text-xs text-brand-gray">RGB ${rgb(p.hex)}</p>
          <p class="num text-xs text-brand-gray">${onWhite.toFixed(1)}:1 on white · ${onBlue.toFixed(1)}:1 on blue</p>
        </div>
      </article>`;
    }).join('');

    $('#palette-bar').innerHTML = PALETTE.filter((p) => p.share)
      .map((p) => `<div class="flex items-end justify-start px-3 pb-2 text-xs font-semibold ${lum(p.hex) > 0.4 ? 'text-brand-ink' : 'text-white'}" style="flex:${p.share};background:${p.hex}"><span class="num">${p.share}%</span></div>`)
      .join('');
  }

  /* ───────────────────────── scale test ───────────────────────── */
  function renderScale() {
    const sizes = [128, 64, 48, 32, 24, 16];
    $('#scale-rows').innerHTML = B.concepts
      .map((c, i) => `
      <article class="card overflow-hidden">
        <header class="flex items-center justify-between border-b border-brand-line px-5 py-3"><h3 class="font-bold text-brand-blue"><span class="num text-brand-orange">0${c.number}</span> · ${esc(c.name)}</h3></header>
        <div class="grid gap-px bg-brand-line lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1.6fr)]">
          <div class="grid place-items-center bg-white p-6"><div class="fit-svg w-full max-w-[512px]" style="aspect-ratio:1">${logoHtml(i, 'emblem', 'full-color', `${c.name} at 512 pixels`)}</div><p class="num mt-2 text-xs font-medium text-brand-gray">512 px (fluid)</p></div>
          <div class="grid bg-white">
            <div class="flex flex-wrap items-end gap-x-6 gap-y-4 p-6">${sizes.map((s) => `<figure class="text-center"><div style="width:${s}px;height:${s}px">${logoHtml(i, 'emblem', 'full-color', `${c.name} at ${s} pixels`).replace('<svg ', `<svg width="${s}" height="${s}" `)}</div><figcaption class="num mt-1.5 text-[11px] font-medium text-brand-gray">${s}</figcaption></figure>`).join('')}</div>
            <div class="flex flex-wrap items-end gap-x-6 gap-y-4 p-6" style="background:#123B75">${[64, 32, 16].map((s) => `<figure class="text-center"><div style="width:${s}px;height:${s}px">${logoHtml(i, 'emblem', 'reversed-white', '').replace('<svg ', `<svg width="${s}" height="${s}" `)}</div><figcaption class="num mt-1.5 text-[11px] font-medium text-white/70">${s}</figcaption></figure>`).join('')}
              ${[64, 32, 16].map((s) => `<figure class="text-center"><div style="width:${s}px;height:${s}px">${logoHtml(i, 'emblem', 'reversed-color', '').replace('<svg ', `<svg width="${s}" height="${s}" `)}</div><figcaption class="num mt-1.5 text-[11px] font-medium text-white/70">${s}</figcaption></figure>`).join('')}</div>
          </div>
        </div>
      </article>`)
      .join('');
  }

  /* ───────────────────────── applications (mockups) ───────────────────────── */
  const nest = (svg, x, y, width) => {
    const [w, h] = viewBoxOf(svg);
    const body = svg.replace(/<\?xml[^>]*\?>\s*/, '').replace(/<title[\s\S]*?<\/title>\s*/, '').replace(/<desc[\s\S]*?<\/desc>\s*/, '').replace(/ id="[^"]*"/g, '');
    return body.replace(/<svg [^>]*>/, `<svg x="${x}" y="${y}" width="${width}" height="${(width * h) / w}" viewBox="0 0 ${w} ${h}">`);
  };
  const bars = (x, y, widths, h = 2, gap = 5, fill = '#DCE3EC') => widths.map((w, i) => `<rect x="${x}" y="${y + i * gap}" width="${w}" height="${h}" rx="${h / 2}" fill="${fill}"/>`).join('');

  function letterheadSvg(ci) {
    const hz = svgFor(ci, 'horizontal', 'full-color');
    const em = svgFor(ci, 'emblem', 'mono-blue');
    const g = B.palette;
    return `<svg viewBox="0 0 210 297" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Official letterhead mockup" font-family="Inter, 'Noto Sans Ethiopic', sans-serif">
      <rect width="210" height="297" fill="#fff"/>
      ${nest(hz, 14, 12, 104)}
      <g font-size="3" fill="${g.gray}" text-anchor="end"><text x="196" y="22">Ref. No. ____________</text><text x="196" y="28">Date ____________</text></g>
      <rect x="14" y="52" width="182" height=".5" fill="${g.blue}"/><rect x="14" y="51.5" width="28" height="1.5" fill="${g.orange}"/>
      ${bars(14, 66, [46, 38, 30], 2.2, 5.2, '#C9D3DF')}
      <rect x="14" y="92" width="92" height="3.2" rx="1.6" fill="${g.blue}" opacity=".85"/>
      ${bars(14, 104, [182, 178, 182, 170, 182, 150], 2, 5.6)}
      ${bars(14, 143, [182, 176, 182, 182, 160], 2, 5.6)}
      ${bars(14, 178, [182, 172, 182, 120], 2, 5.6)}
      <path d="M14 232c8-12 12 8 20-4s10 8 18-2 8 4 14-1" fill="none" stroke="${g.gray}" stroke-width=".7" stroke-linecap="round"/>
      ${bars(14, 244, [40, 28], 2.2, 5.2, '#C9D3DF')}
      <g transform="translate(176 232)">${nest(em, 0, 0, 20)}</g>
      <rect x="14" y="276" width="109" height="1.2" fill="${g.blue}"/><rect x="123" y="276" width="45" height="1.2" fill="${g.green}"/><rect x="168" y="276" width="28" height="1.2" fill="${g.orange}"/>
      <g fill="${g.gray}" font-size="2.6"><text x="14" y="283">Address line · City</text><text x="105" y="283" text-anchor="middle">Telephone · +251 000 000 000</text><text x="196" y="283" text-anchor="end">name@example.gov.et</text></g>
    </svg>`;
  }

  function idCardSvg(ci) {
    const hz = svgFor(ci, 'horizontal', 'reversed-color');
    const em = svgFor(ci, 'emblem', 'reversed-color');
    const g = B.palette;
    const lcg = (seed) => () => { seed = (seed * 1103515245 + 12345) & 0x7fffffff; return seed; };
    // Deterministic placeholder barcode and QR pattern (layout only, not scannable data).
    const rnd = lcg(7);
    let code = '';
    for (let x = 0; x < 50; ) {
      const r = rnd();
      const w = 0.4 + (r % 3) * 0.35;
      code += `<rect x="${x.toFixed(2)}" y="0" width="${w.toFixed(2)}" height="6" fill="#0A1F3D"/>`;
      x += w + 0.45 + ((r >> 4) % 2) * 0.3;
    }
    const rq = lcg(11);
    let qr = '';
    for (let r = 0; r < 11; r += 1) {
      for (let c = 0; c < 11; c += 1) {
        const finder = (r < 3 && c < 3) || (r < 3 && c > 7) || (r > 7 && c < 3);
        if (finder || rq() % 5 < 2) qr += `<rect x="${c * 1.5}" y="${r * 1.5}" width="1.5" height="1.5"/>`;
      }
    }
    const strip = (y) => `<rect y="${y}" width="85.6" height="2.6" fill="${g.blue}"/><rect x="50" y="${y}" width="22" height="2.6" fill="${g.green}"/><rect x="72" y="${y}" width="13.6" height="2.6" fill="${g.orange}"/>`;
    const head = 'font-family="Inter, \'Noto Sans Ethiopic\', sans-serif"';
    const front = `<svg viewBox="0 0 85.6 54" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="ID card, front" ${head}>
      <defs><clipPath id="idc"><rect width="85.6" height="54" rx="3.2"/></clipPath></defs>
      <g clip-path="url(#idc)">
        <rect width="85.6" height="54" fill="#fff"/>
        <rect width="85.6" height="19" fill="${g.blue}"/>
        ${nest(hz, 5, 1.5, 50)}
        <rect x="6" y="23.5" width="19" height="22" rx="1.4" fill="#E6ECF3"/><circle cx="15.5" cy="31" r="4.2" fill="#B5C2D1"/><path d="M7.2 45.5c0-6 3.6-9.3 8.3-9.3s8.3 3.3 8.3 9.3Z" fill="#B5C2D1"/>
        <text x="30" y="27.5" font-size="3.9" font-weight="800" fill="${g.blue}">FULL NAME</text>
        <g fill="${g.gray}" font-size="2.3" font-weight="500"><text x="30" y="32.2">Trainee · Department</text><text x="30" y="36">ID No.  0000 0000</text><text x="30" y="39.8">Valid until  00 / 0000</text></g>
        <g transform="translate(30 42.2)">${code}</g>
        ${strip(51.4)}
      </g>
    </svg>`;
    const back = `<svg viewBox="0 0 85.6 54" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="ID card, back" ${head}>
      <defs><clipPath id="idb"><rect width="85.6" height="54" rx="3.2"/></clipPath></defs>
      <g clip-path="url(#idb)">
        <rect width="85.6" height="54" fill="${g.blue}"/>
        <g opacity=".08">${nest(svgFor(ci, 'emblem', 'reversed-white'), 40, -6, 66)}</g>
        ${nest(em, 8, 7, 26)}
        <g fill="#fff" font-size="2.5"><text x="8" y="41.5" font-weight="700">This card is the property of the Bureau.</text><text x="8" y="45.5" fill="#C9D6E3" font-weight="400">Please return it if found.</text></g>
        <rect x="61" y="28" width="18" height="18" rx="1.2" fill="#fff"/><g transform="translate(62.25 29.25)" fill="#0A1F3D">${qr}</g>
        <rect y="51.4" width="85.6" height="2.6" fill="#0A1F3D"/><rect x="50" y="51.4" width="22" height="2.6" fill="${g.green}"/><rect x="72" y="51.4" width="13.6" height="2.6" fill="${g.orange}"/>
      </g>
    </svg>`;
    return { front, back };
  }

  function renderMockups() {
    const ci = state.chosen;
    $('#mock-concept').textContent = `Concept 0${concept(ci).number} · ${concept(ci).name}`;
    $('#mock-letterhead').innerHTML = letterheadSvg(ci);
    const { front, back } = idCardSvg(ci);
    $('#mock-id-front').innerHTML = front;
    $('#mock-id-back').innerHTML = back;
    $$('[data-mock-pick]').forEach((b) => b.setAttribute('aria-pressed', String(+b.dataset.mockPick === ci)));
  }

  function renderMockPicker() {
    $('#mock-picker').innerHTML = B.concepts.map((c, i) => `<button type="button" data-mock-pick="${i}" aria-pressed="${i === state.chosen}">0${c.number}</button>`).join('');
  }

  /* ───────────────────────── compare ───────────────────────── */
  function renderCompare() {
    const list = state.compare.slice().sort();
    $('#compare-tray').innerHTML = B.concepts.map((c, i) => `
      <label class="chip cursor-pointer select-none ${list.includes(i) ? '!border-brand-blue !text-brand-blue' : ''}"><input type="checkbox" class="accent-[#123B75]" data-compare-toggle="${i}" ${list.includes(i) ? 'checked' : ''}>0${c.number} · ${esc(c.short)}</label>`).join('');
    const variant = state.cmpDark ? 'reversed-color' : 'full-color';
    const surface = state.cmpDark ? '#123B75' : '#FFFFFF';
    const grid = $('#compare-grid');
    grid.style.setProperty('--n', String(Math.max(1, Math.min(list.length, 4))));
    grid.classList.toggle('hidden', !list.length);
    $('#compare-empty').hidden = list.length > 0;
    grid.innerHTML = list.map((i) => `
      <figure class="min-w-0 overflow-hidden rounded-xl border border-brand-line" style="background:${surface}">
        <div class="grid min-h-[200px] place-items-center p-3"><div class="fit-svg" style="width:${state.cmpSize}px;height:${state.cmpSize}px;max-width:100%">${logoHtml(i, 'emblem', variant, concept(i).name)}</div></div>
        <figcaption class="border-t px-3 py-2 text-xs font-semibold ${state.cmpDark ? 'border-white/20 text-white' : 'border-brand-line text-brand-blue'}">0${concept(i).number} · ${esc(concept(i).short)}</figcaption>
      </figure>`).join('');

    const head = `<tr><th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-brand-gray">Criterion</th>${B.concepts.map((c) => `<th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-brand-blue">0${c.number}</th>`).join('')}</tr>`;
    const rows = CRITERIA.map((name, r) => `<tr class="border-t border-brand-line"><th scope="row" class="px-4 py-3 text-left text-sm font-medium text-brand-ink">${name}</th>${B.concepts.map((c, i) => {
      const s = CONTENT[i].scores[r];
      return `<td class="px-3 py-3"><div class="mx-auto flex max-w-[110px] items-center gap-2" role="img" aria-label="${s} out of 5"><div class="h-1.5 flex-1 overflow-hidden rounded-full bg-brand-line"><div class="h-full rounded-full ${s >= 5 ? 'bg-brand-green' : 'bg-brand-blue'}" style="width:${s * 20}%"></div></div><span class="num w-3 text-xs font-semibold text-brand-gray">${s}</span></div></td>`;
    }).join('')}</tr>`).join('');
    const totals = B.concepts.map((c, i) => CONTENT[i].scores.reduce((a, b) => a + b, 0));
    const top = Math.max(...totals);
    const foot = `<tr class="border-t-2 border-brand-blue bg-brand-mist"><th scope="row" class="px-4 py-3 text-left text-sm font-bold text-brand-blue">Total (of 25)</th>${totals.map((t) => `<td class="num px-3 py-3 text-center text-sm font-bold ${t === top ? 'text-brand-green' : 'text-brand-blue'}">${t}${t === top ? ' ★' : ''}</td>`).join('')}</tr>`;
    $('#compare-table').innerHTML = `<thead>${head}</thead><tbody>${rows}</tbody><tfoot>${foot}</tfoot>`;

    $('#choose-row').innerHTML = B.concepts.map((c, i) => `
      <button type="button" data-choose="${i}" aria-pressed="${i === state.chosen}" class="group flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition ${i === state.chosen ? 'border-brand-blue bg-brand-blue text-white shadow-card' : 'border-brand-line bg-white text-brand-blue hover:border-brand-blue'}">
        <span class="fit-svg h-9 w-9 shrink-0 rounded-md p-0.5 ${i === state.chosen ? 'bg-white' : ''}">${logoHtml(i, 'emblem', 'full-color', '')}</span>
        <span><span class="block text-[11px] font-semibold uppercase tracking-wider opacity-70">${i === state.chosen ? 'Selected identity' : 'Select'}</span><span class="block text-sm font-bold">0${c.number} · ${esc(c.short)}</span></span>
      </button>`).join('');
  }

  /* ───────────────────────── guidelines (live diagrams) ───────────────────────── */
  function renderClearSpace() {
    const ci = state.chosen;
    const hz = svgFor(ci, 'horizontal', 'full-color');
    const [w, h] = B.layouts.horizontal.viewBox;
    const X = 60; // ¼ of the emblem height
    const ax = 8; // artwork inset inside the 240-unit emblem box
    const pad = X + 24;
    const vw = w + pad * 2;
    const vh = h + pad * 2;
    const sq = (x, y) => `<rect x="${x}" y="${y}" width="${X}" height="${X}" fill="#E89B24" fill-opacity=".18" stroke="#E89B24" stroke-width="1.5"/><text x="${x + X / 2}" y="${y + X / 2 + 8}" text-anchor="middle" font-size="26" font-weight="700" fill="#B26F0A" font-family="Inter, sans-serif">X</text>`;
    $('#clearspace').innerHTML = `<svg viewBox="${-pad} ${-pad} ${vw} ${vh}" role="img" aria-label="Clear-space diagram: X equals one quarter of the emblem height on every side" xmlns="http://www.w3.org/2000/svg">
      <rect x="${-pad + 2}" y="${-pad + 2}" width="${vw - 4}" height="${vh - 4}" fill="#fff"/>
      <rect x="${ax - X}" y="${ax - X}" width="${w - 2 * ax + 2 * X}" height="${h - 2 * ax + 2 * X}" fill="none" stroke="#E89B24" stroke-width="2" stroke-dasharray="8 6"/>
      <rect x="${ax}" y="${ax}" width="${w - 2 * ax}" height="${h - 2 * ax}" fill="none" stroke="#123B75" stroke-opacity=".35" stroke-width="1.5"/>
      ${sq(ax - X, ax + 70)}${sq(w - ax, ax + 70)}${sq(ax + 70, ax - X)}${sq(ax + 70, h - ax)}
      ${nest(hz, 0, 0, w)}
    </svg>`;
  }

  function renderMisuse() {
    const ci = state.chosen;
    const em = (variant = 'full-color') => logoHtml(ci, 'emblem', variant, '');
    const tiles = [
      ['Don’t stretch or squash', 'transform:scaleX(1.45)', '#fff', em()],
      ['Don’t rotate or tilt', 'transform:rotate(-20deg)', '#fff', em()],
      ['Don’t recolour outside the palette', 'filter:hue-rotate(150deg) saturate(1.6)', '#fff', em()],
      ['Don’t add shadows, glows or 3D', 'filter:drop-shadow(6px 8px 5px rgba(0,0,0,.55))', '#fff', em()],
      ['Don’t outline or stroke', 'filter:drop-shadow(1.5px 0 0 #000) drop-shadow(-1.5px 0 0 #000) drop-shadow(0 1.5px 0 #000) drop-shadow(0 -1.5px 0 #000)', '#fff', em()],
      ['Don’t fade or lower opacity', 'opacity:.3', '#fff', em()],
      ['Don’t place on busy backgrounds', '', 'repeating-conic-gradient(#e89b24 0 12deg,#16a067 12deg 24deg,#123b75 24deg 36deg)', em()],
      ['Don’t use full colour on dark blue', '', '#123B75', em()],
      ['Don’t crop or rearrange elements', 'transform:translate(-34%,24%) scale(2.1)', '#fff', em()],
    ];
    $('#misuse-grid').innerHTML = tiles.map(([cap, css, bg, svg]) => `
      <figure class="overflow-hidden rounded-xl border border-brand-line bg-white">
        <div class="relative grid h-40 place-items-center overflow-hidden" style="background:${bg}">
          <div class="fit-svg h-24 w-24" style="${css}">${svg}</div>
          <span class="absolute right-2.5 top-2.5 grid h-6 w-6 place-items-center rounded-full bg-red-600 text-white shadow" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></span>
        </div>
        <figcaption class="px-3.5 py-2.5 text-[13px] font-medium text-brand-ink">${cap}</figcaption>
      </figure>`).join('');
  }

  function renderMinSizes() {
    const ci = state.chosen;
    const item = (layout, px, label) => `<figure class="flex flex-col items-center justify-end gap-2 text-center"><div style="width:${px}px;aspect-ratio:${aspect(layout)}">${logoHtml(ci, layout, 'full-color', '').replace('<svg ', `<svg width="${px}" `)}</div><figcaption class="num text-xs font-medium text-brand-gray">${label}</figcaption></figure>`;
    $('#minsize-demo').innerHTML = `${item('emblem', 24, 'Emblem · 24 px')}${item('horizontal', 320, 'Horizontal · 320 px')}${item('vertical', 240, 'Vertical · 240 px')}`;
  }

  /* ───────────────────────── typography ───────────────────────── */
  function renderType() {
    const t = B.titles;
    $('#type-specimen').innerHTML = `
      <p lang="am" class="font-ethiopic text-2xl font-semibold text-brand-gray sm:text-[1.7rem]">${esc(t.amharic.city)}</p>
      <p class="mt-1 text-sm font-semibold uppercase tracking-[0.085em] text-brand-gray">${esc(t.english.city)}</p>
      <div class="my-4 h-1 w-11 bg-brand-orange"></div>
      <p lang="am" class="font-ethiopic text-3xl font-bold leading-tight text-brand-blue sm:text-4xl">${esc(t.amharic.bureau)}</p>
      <p class="mt-2 text-lg font-bold uppercase leading-snug tracking-[0.035em] text-brand-blue sm:text-xl">${esc(t.english.bureau)}</p>`;
  }

  /* ───────────────────────── wiring ───────────────────────── */
  function refreshStudio() { renderStudioControls(); renderStage(); renderVariantGrid(); renderLayoutGrid(); }

  function setChosen(i) {
    state.chosen = i;
    store.set('tvet.chosen', i);
    renderHeaderMark(); renderMockups(); renderCompare(); renderClearSpace(); renderMisuse(); renderMinSizes(); renderMockPicker(); renderMockups();
    toast(`Selected identity: Concept 0${concept(i).number} · ${concept(i).name}`);
  }

  function toggleCompare(i, on) {
    const set = new Set(state.compare);
    if (on) set.add(i); else set.delete(i);
    state.compare = [...set];
    $$('[data-compare-toggle]').forEach((el) => { el.checked = set.has(+el.dataset.compareToggle); });
    renderCompare();
  }

  document.addEventListener('click', (e) => {
    const t = e.target.closest('button, a');
    if (!t) return;
    if (t.dataset.openStudio !== undefined) {
      state.concept = +t.dataset.openStudio;
      refreshStudio();
      $('#studio').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    } else if (t.dataset.studioConcept !== undefined) { state.concept = +t.dataset.studioConcept; refreshStudio(); }
    else if (t.dataset.studioLayout) { state.layout = t.dataset.studioLayout; refreshStudio(); }
    else if (t.dataset.studioVariant) {
      state.variant = t.dataset.studioVariant;
      if (state.variant.startsWith('reversed') && bgOf(state.bg).color && lum(bgOf(state.bg).color) > 0.4) state.bg = 'blue';
      refreshStudio();
    } else if (t.dataset.studioBg) { state.bg = t.dataset.studioBg; renderStudioControls(); renderStage(); }
    else if (t.dataset.mockPick !== undefined) setChosen(+t.dataset.mockPick);
    else if (t.dataset.choose !== undefined) setChosen(+t.dataset.choose);
    else if (t.dataset.copy) {
      navigator.clipboard?.writeText(t.dataset.copy).then(() => toast(`Copied ${t.dataset.copy}`), () => toast(t.dataset.copy));
    } else if (t.id === 'copy-svg') {
      const text = svgFor(state.concept, state.layout, state.variant);
      (navigator.clipboard?.writeText(text) ?? Promise.reject()).then(() => toast('SVG code copied to clipboard'), () => toast('Clipboard unavailable — use Download SVG', 'error'));
    } else if (t.id === 'cmp-light' || t.id === 'cmp-dark') {
      state.cmpDark = t.id === 'cmp-dark';
      $('#cmp-light').setAttribute('aria-pressed', String(!state.cmpDark));
      $('#cmp-dark').setAttribute('aria-pressed', String(state.cmpDark));
      renderCompare();
    } else if (t.id === 'menu-toggle') {
      const nav = $('#mobile-nav');
      nav.hidden = !nav.hidden;
      t.setAttribute('aria-expanded', String(!nav.hidden));
    } else if (t.closest('#mobile-nav') && t.tagName === 'A') {
      $('#mobile-nav').hidden = true;
      $('#menu-toggle').setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('change', (e) => {
    if (e.target.dataset.compareToggle !== undefined) toggleCompare(+e.target.dataset.compareToggle, e.target.checked);
    if (e.target.id === 'cmp-size') { state.cmpSize = +e.target.value; $('#cmp-size-out').textContent = `${state.cmpSize} px`; renderCompare(); }
  });
  $('#cmp-size').addEventListener('input', (e) => { state.cmpSize = +e.target.value; $('#cmp-size-out').textContent = `${state.cmpSize} px`; renderCompare(); });

  renderHero();
  renderHeaderMark();
  renderConcepts();
  refreshStudio();
  renderPalette();
  renderType();
  renderScale();
  renderMockPicker();
  renderMockups();
  renderCompare();
  renderClearSpace();
  renderMisuse();
  renderMinSizes();
  document.documentElement.dataset.ready = 'true';
})();
