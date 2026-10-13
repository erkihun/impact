// Pure SVG composer. This function is serialised verbatim into assets/js/brand-data.js
// (via Function#toString), so the page and the build script can never drift apart.
// It must stay self-contained: no imports, no references to module scope.

export function composeSvg(layout, emblemParts, pal, meta) {
  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');
  const path = (p) =>
    `<path id="${p.id}" fill="${pal[p.role]}"` +
    (p.fillRule ? ` fill-rule="${p.fillRule}" clip-rule="${p.fillRule}"` : '') +
    ` d="${p.d}"/>`;
  const [w, h] = layout.viewBox;
  const out = [];
  out.push('<?xml version="1.0" encoding="UTF-8"?>');
  out.push(
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}" role="img" aria-labelledby="svg-title">`,
  );
  out.push(`  <title id="svg-title">${esc(meta.title)}</title>`);
  if (meta.desc) out.push(`  <desc>${esc(meta.desc)}</desc>`);
  if (layout.emblem) {
    out.push(`  <g id="emblem" transform="translate(${layout.emblem.x} ${layout.emblem.y})">`);
    emblemParts.forEach((p) => out.push('    ' + path(p)));
    out.push('  </g>');
  }
  if (layout.parts.length) {
    out.push('  <g id="wordmark">');
    layout.parts.forEach((p) => out.push('    ' + path(p)));
    out.push('  </g>');
  }
  out.push('</svg>');
  return out.join('\n') + '\n';
}
