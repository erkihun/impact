// Brand palette and the role → colour mapping for every official variant.

export const BRAND = {
  blue: '#123B75',
  green: '#16A067',
  orange: '#E89B24',
  gray: '#4F6578',
  white: '#FFFFFF',
};

// Rec. 709 luma of each brand colour, rounded — used for the grayscale variant.
const luma = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));
  const y = Math.round(0.2126 * r + 0.7152 * g + 0.0722 * b);
  return `#${[y, y, y].map((v) => v.toString(16).padStart(2, '0')).join('').toUpperCase()}`;
};

export const VARIANTS = {
  'full-color': {
    label: 'Full colour',
    primary: BRAND.blue,
    secondary: BRAND.green,
    accent: BRAND.orange,
    neutral: BRAND.gray,
    surface: '#FFFFFF',
  },
  'mono-blue': {
    label: 'Monochrome · Institutional Blue',
    primary: BRAND.blue,
    secondary: BRAND.blue,
    accent: BRAND.blue,
    neutral: BRAND.blue,
    surface: '#FFFFFF',
  },
  'mono-black': {
    label: 'Monochrome · Black',
    primary: '#111111',
    secondary: '#111111',
    accent: '#111111',
    neutral: '#111111',
    surface: '#FFFFFF',
  },
  grayscale: {
    label: 'Grayscale',
    primary: luma(BRAND.blue),
    secondary: luma(BRAND.green),
    accent: luma(BRAND.orange),
    neutral: luma(BRAND.gray),
    surface: '#FFFFFF',
  },
  'reversed-white': {
    label: 'Reversed · White',
    primary: '#FFFFFF',
    secondary: '#FFFFFF',
    accent: '#FFFFFF',
    neutral: '#FFFFFF',
    surface: BRAND.blue,
  },
  'reversed-color': {
    label: 'Reversed · Colour',
    primary: '#FFFFFF',
    secondary: BRAND.green,
    accent: BRAND.orange,
    neutral: '#C9D6E3',
    surface: BRAND.blue,
  },
};
