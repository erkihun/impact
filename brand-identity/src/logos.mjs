// Four original emblem concepts for the Addis Ababa City Administration TVET Bureau.
// Every emblem is drawn on a 240 × 240 grid, centred on (120,120), as a list of
// role-tagged compound paths. Roles are mapped to colours by src/palette.mjs, which is
// how the full-colour, monochrome, reversed and grayscale variants are produced.
//
// Construction rules shared by all four concepts
//  • No two shapes overlap: separations are real gaps, so one-colour reproduction works.
//  • Holes are true holes (fill-rule: evenodd) — backgrounds show through, so every
//    export is genuinely transparent.
//  • Minimum feature size is ≥ 5 units (≈ 0.7 px at 32 px), minimum gap ≥ 4 units.

import { circle, polar, pt, poly, mirrorX, lens, capsule, r2 } from './geometry.mjs';

/* ───────────────────────── Concept 1 · Integrated Skills & Technology ─────────────────────────
   A ten-tooth gear (technical education) encloses a learner whose raised arms are an open
   book (knowledge) and whose head is the single orange spark (innovation).                   */
function skillsTechnology() {
  const cx = 120;
  const cy = 120;
  const teeth = 10;
  const rRoot = 93;
  const rTip = 112;
  const rootHalf = 10.5; // degrees
  const tipHalf = 6.8;
  const step = 360 / teeth;
  let d = '';
  for (let i = 0; i < teeth; i += 1) {
    const t = -90 + i * step;
    const p1 = polar(cx, cy, rRoot, t - rootHalf);
    const p2 = polar(cx, cy, rTip, t - tipHalf);
    const p3 = polar(cx, cy, rTip, t + tipHalf);
    const p4 = polar(cx, cy, rRoot, t + rootHalf);
    d += `${i === 0 ? 'M' : 'A' + rRoot + ' ' + rRoot + ' 0 0 1 '}${pt(p1)}L${pt(p2)}A${rTip} ${rTip} 0 0 1 ${pt(p3)}L${pt(p4)}`;
  }
  const first = polar(cx, cy, rRoot, -90 - rootHalf);
  d += `A${rRoot} ${rRoot} 0 0 1 ${pt(first)}Z`;
  d += circle(cx, cy, 68);

  // Open-book "arms": two pages, mirrored about the spine, separated by a 6-unit gutter.
  const pageL = `M116 170L116 124Q94 108 68 112L68 158Q94 154 116 170Z`;
  const pageR = `M124 170L124 124Q146 108 172 112L172 158Q146 154 124 170Z`;

  return {
    shapes: [
      { id: 'gear', role: 'primary', d, fillRule: 'evenodd' },
      { id: 'page-left', role: 'secondary', d: pageL },
      { id: 'page-right', role: 'secondary', d: pageR },
      { id: 'spark', role: 'accent', d: circle(120, 84, 14) },
    ],
  };
}

/* ───────────────────────── Concept 2 · Knowledge to Productivity ─────────────────────────
   One open book, five stages. The left page is Knowledge. The right page becomes three
   saw-tooth roof steps — Skills, Innovation, Productivity — whose industrial profile climbs
   toward the orange figure: Sustainable Development, achieved through people.            */
function knowledgeProductivity() {
  const pageL = 'M20 130Q68 110 116 140L116 208Q68 178 20 192Z';
  const x = [124, 156, 188, 220];
  const rise = 40;
  const drop = 14;
  let y = 164;
  let d = `M124 ${y}`;
  for (let i = 0; i < 3; i += 1) {
    d += `L${x[i + 1]} ${y - rise}`;
    if (i < 2) {
      y = y - rise + drop;
      d += `L${x[i + 1]} ${y}`;
    }
  }
  d += 'L220 192Q172 178 124 208Z';
  return {
    shapes: [
      { id: 'page-knowledge', role: 'secondary', d: pageL },
      { id: 'page-steps', role: 'primary', d },
      { id: 'figure', role: 'accent', d: circle(204, 38, 15) },
    ],
  };
}

/* ───────────────────────── Concept 3 · Green TVET & Innovation ─────────────────────────
   A hexagonal nut / cell frames a seedling. Each leaf is a lens split by a channel — a
   leaf vein that doubles as a circuit trace. Read as a person, the leaves are raised
   arms and the orange node is the head — human development and growth as one form.     */
function greenInnovation() {
  const cx = 120;
  const cy = 120;
  const hex = (R) => poly([0, 1, 2, 3, 4, 5].map((k) => polar(cx, cy, R, -90 + 60 * k)));
  const ring = hex(110) + hex(88);

  const R = 90; // leaf arc radius
  const g = 2.6; // half-width of the vein slit
  /**
   * A lens leaf (two equal arcs, chord base→tip) with a vein slit cut in from the tip and
   * closed by a round end. Single path, no overlaps, no even-odd tricks.
   */
  const leaf = (base, tip, slitFrom = 0.34) => {
    const ux = tip[0] - base[0];
    const uy = tip[1] - base[1];
    const len = Math.hypot(ux, uy);
    const n = [-uy / len, ux / len];
    const off = (p, k) => [p[0] + n[0] * g * k, p[1] + n[1] * g * k];
    const s = [base[0] + ux * slitFrom, base[1] + uy * slitFrom];
    const tp = off(tip, 1);
    const tm = off(tip, -1);
    const sp = off(s, 1);
    const sm = off(s, -1);
    // sweep 1 bulges toward −n (outer), sweep 0 toward +n (inner)
    return (
      `M${pt(base)}A${R} ${R} 0 0 1 ${pt(tm)}L${pt(sm)}A${g} ${g} 0 0 0 ${pt(sp)}L${pt(tp)}` +
      `A${R} ${R} 0 0 1 ${pt(base)}Z`
    );
  };
  const mirror = (p) => [240 - p[0], p[1]];
  const baseL = [116, 178];
  const tipL = [72, 98];

  return {
    shapes: [
      { id: 'hexagon', role: 'primary', d: ring, fillRule: 'evenodd' },
      { id: 'leaf-left', role: 'secondary', d: leaf(baseL, tipL) },
      { id: 'leaf-right', role: 'secondary', d: leaf(mirror(baseL), mirror(tipL)) },
      { id: 'stem', role: 'secondary', d: capsule([120, 200], [120, 184], 3.8) },
      { id: 'node', role: 'accent', d: circle(120, 84, 11) },
    ],
  };
}

/* ───────────────────────── Concept 4 · Institutional Excellence ───────────────────────── */
const shieldPath = (inset = 0) => {
  const l = 30 + inset;
  const r = 210 - inset;
  const t = 16 + inset;
  const m = 118;
  const b = 226 - inset * 1.55;
  return `M${l} ${t}H${r}V${m - inset * 0.3}C${r} ${m + 52 - inset} ${r - 36 + inset * 0.4} ${b - 38} 120 ${b}C${l + 36 - inset * 0.4} ${b - 38} ${l} ${m + 52 - inset} ${l} ${m - inset * 0.3}Z`;
};
const starPath = (cx, cy, R, rr, points = 4, rot = -90) => {
  const pts = [];
  for (let k = 0; k < points * 2; k += 1) pts.push(polar(cx, cy, k % 2 === 0 ? R : rr, rot + (k * 180) / points));
  return poly(pts);
};

function institutionalExcellence() {
  const chevron = (y, half, thick, h) =>
    poly([[120 - half, y + h], [120, y], [120 + half, y + h], [120 + half, y + h + thick], [120, y + thick], [120 - half, y + h + thick]]);
  return {
    shapes: [
      { id: 'shield', role: 'primary', d: shieldPath(0) + shieldPath(13), fillRule: 'evenodd' },
      { id: 'star', role: 'accent', d: starPath(120, 62, 22, 6.2) },
      { id: 'level-3', role: 'primary', d: chevron(92, 40, 13, 20) },
      { id: 'level-2', role: 'secondary', d: chevron(117, 40, 13, 20) },
      { id: 'level-1', role: 'primary', d: chevron(142, 40, 13, 20) },
    ],
  };
}

export const CONCEPTS = [
  {
    key: 'skills-technology',
    number: 1,
    name: 'Integrated Skills & Technology',
    short: 'Skills & Technology',
    build: skillsTechnology,
  },
  {
    key: 'knowledge-productivity',
    number: 2,
    name: 'Knowledge to Productivity',
    short: 'Knowledge to Productivity',
    build: knowledgeProductivity,
  },
  {
    key: 'green-innovation',
    number: 3,
    name: 'Green TVET & Innovation',
    short: 'Green TVET & Innovation',
    build: greenInnovation,
  },
  {
    key: 'institutional-excellence',
    number: 4,
    name: 'Institutional Excellence',
    short: 'Institutional Excellence',
    build: institutionalExcellence,
  },
];

export const EMBLEM_BOX = 240;

