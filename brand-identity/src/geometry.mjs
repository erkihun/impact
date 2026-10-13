// Shared vector helpers. All artwork is built from absolute path commands so it
// opens identically in browsers, Illustrator and Inkscape.

export const r2 = (n) => Math.round(n * 100) / 100;

/** Point on a circle. Angle in degrees, 0° = +x, 90° = +y (SVG space). */
export const polar = (cx, cy, r, deg) => {
  const a = (deg * Math.PI) / 180;
  return [cx + r * Math.cos(a), cy + r * Math.sin(a)];
};

export const pt = ([x, y]) => `${r2(x)} ${r2(y)}`;

/** Full circle as a closed path (two arcs). */
export const circle = (cx, cy, r) =>
  `M${r2(cx - r)} ${r2(cy)}A${r2(r)} ${r2(r)} 0 1 0 ${r2(cx + r)} ${r2(cy)}A${r2(r)} ${r2(r)} 0 1 0 ${r2(cx - r)} ${r2(cy)}Z`;

/** Closed polygon from [x,y] pairs. */
export const poly = (points) => `M${points.map(pt).join('L')}Z`;

/** Mirror a list of points about the vertical axis x = cx. */
export const mirrorX = (points, cx = 120) => points.map(([x, y]) => [2 * cx - x, y]);

/**
 * Lens ("vesica") with the given chord endpoints, built from two equal
 * circular arcs of radius r. Used for leaves.
 */
export const lens = (a, b, r) =>
  `M${pt(a)}A${r2(r)} ${r2(r)} 0 0 1 ${pt(b)}A${r2(r)} ${r2(r)} 0 0 1 ${pt(a)}Z`;

/** Rounded stadium (capsule) between two points with half-width hw. */
export const capsule = (a, b, hw) => {
  const [ax, ay] = a;
  const [bx, by] = b;
  const len = Math.hypot(bx - ax, by - ay);
  const ux = (bx - ax) / len;
  const uy = (by - ay) / len;
  const nx = -uy * hw;
  const ny = ux * hw;
  const p1 = [ax + nx, ay + ny];
  const p2 = [bx + nx, by + ny];
  const p3 = [bx - nx, by - ny];
  const p4 = [ax - nx, ay - ny];
  return `M${pt(p1)}L${pt(p2)}A${hw} ${hw} 0 0 0 ${pt(p3)}L${pt(p4)}A${hw} ${hw} 0 0 0 ${pt(p1)}Z`;
};
