#!/usr/bin/env bash
set -euo pipefail

BASE="public/assets/scss"
mkdir -p "$BASE"/{core,components,utilities}

# ========= app.scss =========
cat > "$BASE/app.scss" <<'SCSS'
@use "core/brand" as brand;
@use "core/themes" as themes;
@use "core/mixins";
@use "core/reset";
@use "core/typography";

@use "components/buttons";
@use "components/forms";
@use "components/layout";
@use "components/cards";
@use "components/nav";

@use "utilities/utilities";

/* 1) Reset + Base */
@include reset.apply();
@include typography.apply();

/* 2) Emit CSS variables for themes */
@include themes.emit();

/* 3) Components */
@include buttons.apply();
@include forms.apply();
@include layout.apply();
@include cards.apply();
@include nav.apply();

/* 4) Utilities */
@include utilities.apply();

/* 5) Global page background hookup */
:root,
[data-theme="dark"] {
  background-color: var(--page-bg);
  color: var(--text);
}
SCSS

# ========= core/_brand.scss =========
cat > "$BASE/core/_brand.scss" <<'SCSS'
@use "sass:map";
@use "sass:math";
@use "sass:color";

/* Typography (IBM Plex Sans Arabic) */
$font-family-base: "IBM Plex Sans Arabic", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
$font-weight: (
  regular: 400,
  medium: 500,
  semibold: 600,
  bold: 700
);
$type-scale: (
  xs: 0.75rem,
  sm: 0.875rem,
  base: 1rem,
  lg: 1.125rem,
  xl: 1.25rem,
  h6: 1.125rem,
  h5: 1.25rem,
  h4: 1.5rem,
  h3: 1.875rem,
  h2: 2.25rem,
  h1: clamp(2rem, 2.6vw, 2.75rem)
);

/* Spacing scale */
$space: (
  0: 0,
  1: 0.25rem,
  2: 0.5rem,
  3: 0.75rem,
  4: 1rem,
  5: 1.25rem,
  6: 1.5rem,
  8: 2rem,
  10: 2.5rem,
  12: 3rem
);

/* Radii */
$radius: (
  sm: 0.375rem,
  md: 0.5rem,
  lg: 0.75rem,
  xl: 1rem,
  pill: 9999px
);

/* Shadows */
$shadow: (
  sm: 0 4px 10px rgba(0,0,0,.15),
  md: 0 8px 24px rgba(0,0,0,.2),
  lg: 0 16px 40px rgba(0,0,0,.28)
);

/* Containers */
$container: (
  sm: 640px,
  md: 768px,
  lg: 1024px,
  xl: 1200px
);

/* Palettes */
$dark-palette: (
  page-bg: #0b0f17,
  surface: #0f1624,
  surface-2: #111a2b,
  border: #1e2a3f,
  text: #e6edf7,
  text-muted: #9fb3d9,

  brand: #3b82f6,
  brand-600: #2f6de0,
  brand-700: #2458bf,
  brand-800: #1c4699,

  accent: #22d3ee,
  success: #22c55e,
  warning: #f59e0b,
  danger:  #ef4444,

  field-bg: #0c1422,
  field-bd: #1c2740,
  ring: #4f87ff
);

$light-palette: (
  page-bg: #f7f9fc,
  surface: #ffffff,
  surface-2: #f3f6fb,
  border: #e3e9f3,
  text: #0f172a,
  text-muted: #4b5568,

  brand: #2260ff,
  brand-600: #1b50d9,
  brand-700: #1744b6,
  brand-800: #11368f,

  accent: #0ea5e9,
  success: #16a34a,
  warning: #d97706,
  danger:  #dc2626,

  field-bg: #ffffff,
  field-bd: #d8e0ef,
  ring: #2b6bff
);

/* Helpers */
@function token($group, $key) { @return map.get($group, $key); }

@mixin emit-vars($palette) {
  --page-bg: #{token($palette, page-bg)};
  --surface: #{token($palette, surface)};
  --surface-2: #{token($palette, surface-2)};
  --border: #{token($palette, border)};
  --text: #{token($palette, text)};
  --text-muted: #{token($palette, text-muted)};

  --brand: #{token($palette, brand)};
  --brand-600: #{token($palette, brand-600)};
  --brand-700: #{token($palette, brand-700)};
  --brand-800: #{token($palette, brand-800)};

  --accent: #{token($palette, accent)};
  --success: #{token($palette, success)};
  --warning: #{token($palette, warning)};
  --danger:  #{token($palette, danger)};

  --field-bg: #{token($palette, field-bg)};
  --field-bd: #{token($palette, field-bd)};
  --ring: #{token($palette, ring)};

  --radius-sm: #{map.get($radius, sm)};
  --radius-md: #{map.get($radius, md)};
  --radius-lg: #{map.get($radius, lg)};
  --radius-xl: #{map.get($radius, xl)};
  --radius-2xl: #{map.get($radius, 2xl)};
  --radius-pill: #{map.get($radius, pill)};

  --shadow-sm: #{map.get($shadow, sm)};
  --shadow-md: #{map.get($shadow, md)};
  --shadow-lg: #{map.get($shadow, lg)};

  --ff-base: #{$font-family-base};
  --fw-regular: #{map.get($font-weight, regular)};
  --fw-medium:  #{map.get($font-weight, medium)};
  --fw-semibold:#{map.get($font-weight, semibold)};
  --fw-bold:    #{map.get($font-weight, bold)};

  @each $k, $v in $type-scale { --fs-#{$k}: #{$v}; }
  @each $k, $v in $space { --sp-#{$k}: #{$v}; }
  @each $k, $v in $container { --container-#{$k}: #{$v}; }
}
SCSS

# ========= core/_themes.scss =========
cat > "$BASE/core/_themes.scss" <<'SCSS'
@use "brand";

@mixin emit() {
  :root,
  [data-theme="dark"] {
    @include brand.emit-vars(brand.$dark-palette);
  }
  [data-theme="light"] {
    @include brand.emit-vars(brand.$light-palette);
  }
}
SCSS

# ========= core/_mixins.scss =========
cat > "$BASE/core/_mixins.scss" <<'SCSS'
@use "brand";
@use "sass:color";

@mixin focus-ring($c: var(--ring)) {
  outline: none;
  box-shadow: 0 0 0 3px color-mix(in oklab, #000 0%, $c 100%);
}

@mixin card($pad: var(--sp-5)) {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: $pad;
}

@mixin container($size: lg) {
  inline-size: min(100% - 2rem, var(--container-#{$size}));
  margin-inline: auto;
}

@mixin truncate { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

@mixin button-reset {
  display: inline-flex; align-items: center; justify-content: center;
  gap: .5rem; border: 0; background: none; cursor: pointer; text-decoration: none;
  font: inherit; line-height: 1; user-select: none;
}

@mixin field-base {
  inline-size: 100%;
  padding: .75rem .9rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--field-bd);
  background: var(--field-bg);
  color: var(--text);
  transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
  &:focus { @include focus-ring(); border-color: var(--ring); }
}
SCSS

# ========= core/_reset.scss =========
cat > "$BASE/core/_reset.scss" <<'SCSS'
@mixin apply() {
  *,*::before,*::after{box-sizing:border-box}
  *{margin:0}
  html:focus-within{scroll-behavior:smooth}
  html,body{height:100%}
  body{line-height:1.5;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
  img,picture,video,canvas,svg{display:block;max-inline-size:100%}
  input,button,textarea,select{font:inherit}
  p,h1,h2,h3,h4,h5,h6{overflow-wrap:anywhere}
}
SCSS

# ========= core/_typography.scss =========
cat > "$BASE/core/_typography.scss" <<'SCSS'
@mixin apply() {
  :root { font-size: 100%; }
  body {
    font-family: var(--ff-base);
    font-size: var(--fs-base);
    color: var(--text);
    background: var(--page-bg);
  }
  h1{font-size:var(--fs-h1);font-weight:var(--fw-bold)}
  h2{font-size:var(--fs-h2);font-weight:var(--fw-bold)}
  h3{font-size:var(--fs-h3);font-weight:var(--fw-semibold)}
  h4{font-size:var(--fs-h4);font-weight:var(--fw-semibold)}
  h5{font-size:var(--fs-h5);font-weight:var(--fw-medium)}
  h6{font-size:var(--fs-h6);font-weight:var(--fw-medium)}
  .text-muted{color:var(--text-muted)}
}
SCSS

# ========= components/_buttons.scss =========
cat > "$BASE/components/_buttons.scss" <<'SCSS'
@use "../core/mixins" as *;

@mixin apply() {
  .btn { 
    @include button-reset;
    border-radius: var(--radius-md);
    padding: .7rem 1rem;
    font-weight: var(--fw-semibold);
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text);
    &:focus-visible { @include focus-ring(); }
  }
  .btn--primary {
    background: var(--brand);
    border-color: color-mix(in oklab, var(--brand), black 14%);
    color: #fff;
    &:hover { background: var(--brand-600); }
    &:active { background: var(--brand-700); }
  }
  .btn--ghost {
    background: transparent; border-color: var(--border); color: var(--text);
    &:hover { background: color-mix(in oklab, var(--surface), white 3%); }
  }
  .btn--danger {
    background: var(--danger); color: #fff; border-color: color-mix(in oklab, var(--danger), black 16%);
  }
  .btn--sm { padding: .5rem .75rem; font-size: var(--fs-sm) }
  .btn--lg { padding: .85rem 1.15rem; font-size: var(--fs-lg) }
}
SCSS

# ========= components/_forms.scss =========
cat > "$BASE/components/_forms.scss" <<'SCSS'
@use "../core/mixins" as *;

@mixin apply() {
  .form-control { @include field-base; }
  .form-label { display:block; margin-block: 0 0.35rem; font-size: var(--fs-sm); color: var(--text-muted); }
  .input-group { display:flex; align-items:stretch; gap:.5rem;
    & > .form-control { flex: 1 1 auto; }
    & > .btn { flex: 0 0 auto; } }
  .form-help { font-size: var(--fs-xs); color: var(--text-muted); margin-top:.35rem; }
  .checkbox { display:flex; align-items:center; gap:.55rem; font-size: var(--fs-sm); color: var(--text-muted);
    input[type="checkbox"]{
      inline-size: 1.05rem; block-size: 1.05rem; border-radius: .35rem;
      border:1px solid var(--field-bd); background: var(--field-bg);
      appearance: none; display:grid; place-items:center;
      &:focus { @include focus-ring(); }
      &:checked{ background: var(--brand); border-color: var(--brand); } } }
}
SCSS

# ========= components/_layout.scss =========
cat > "$BASE/components/_layout.scss" <<'SCSS'
@use "../core/mixins" as *;

@mixin apply() {
  .container { @include container(xl); }
  .page-section { padding-block: var(--sp-10); }
  .hero {
    position:relative; overflow:hidden; border-radius: var(--radius-xl);
    background: linear-gradient(180deg, color-mix(in oklab, var(--brand), transparent 85%) 0%, transparent 60%),
                var(--surface);
    border:1px solid var(--border);
    box-shadow: var(--shadow-lg);
  }
  .grid { display:grid; gap: var(--sp-6);
    &--2 { grid-template-columns: 1fr; }
    &--3 { grid-template-columns: 1fr; }
    @media (min-width: 768px){
      &--2 { grid-template-columns: repeat(2, 1fr); }
      &--3 { grid-template-columns: repeat(3, 1fr); } } }
}
SCSS

# ========= components/_cards.scss =========
cat > "$BASE/components/_cards.scss" <<'SCSS'
@use "../core/mixins" as *;

@mixin apply() {
  .card { @include card(); }
  .card--glass {
    background: color-mix(in oklab, var(--surface), transparent 30%);
    backdrop-filter: saturate(120%) blur(8px);
  }
  .card__title { font-weight: var(--fw-semibold); font-size: var(--fs-lg); margin-bottom: var(--sp-3); }
  .card__meta { color: var(--text-muted); font-size: var(--fs-sm); }
}
SCSS

# ========= components/_nav.scss =========
cat > "$BASE/components/_nav.scss" <<'SCSS'
@use "../core/mixins" as *;

@mixin apply() {
  .topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    position:sticky; inset-block-start:0; z-index:10;
  }
  .topbar__inner { @include container(xl); display:flex; align-items:center; gap:1rem; padding-block:.75rem; }
  .brand { font-weight: var(--fw-bold); }
  .nav { margin-inline-start:auto; display:flex; gap:.75rem; }
  .nav a {
    color: var(--text-muted); text-decoration:none; padding:.5rem .75rem; border-radius: var(--radius-sm);
    &:hover { background: color-mix(in oklab, var(--surface), white 3%); color: var(--text); }
  }
}
SCSS

# ========= utilities/_utilities.scss =========
cat > "$BASE/utilities/_utilities.scss" <<'SCSS'
@mixin apply() {
  .mt-4{margin-top:var(--sp-4)} .mt-6{margin-top:var(--sp-6)} .mt-8{margin-top:var(--sp-8)}
  .mb-4{margin-bottom:var(--sp-4)} .mb-6{margin-bottom:var(--sp-6)} .mb-8{margin-bottom:var(--sp-8)}
  .pt-6{padding-top:var(--sp-6)} .pb-6{padding-bottom:var(--sp-6)}
  .rounded{border-radius:var(--radius-md)} .rounded-lg{border-radius:var(--radius-lg)}
  .text-center{text-align:center}
  .flex{display:flex} .items-center{align-items:center} .justify-between{justify-content:space-between} .gap-4{gap:var(--sp-4)}
  .muted{color:var(--text-muted)}
}
SCSS

echo "✔ SCSS design system written under $BASE"
