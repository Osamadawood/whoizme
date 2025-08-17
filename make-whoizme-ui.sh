set -e
cd public/assets/scss

# ---------- components/_links.scss ----------
cat > components/_links.scss <<'SCSS'
@use "../core/brand" as brand;

@mixin apply() {
  a {
    color: var(--brand, #6d28d9);
    text-decoration: none;
    transition: color .15s ease, text-decoration-color .15s ease;
  }
  a:hover { color: color-mix(in oklab, var(--brand), #fff 16%); }
  a:focus-visible { outline: 2px solid color-mix(in oklab, var(--brand), #fff 25%); outline-offset: 2px; }
  .link-muted { color: var(--muted, #94a3b8); }
  .link-underline { text-decoration: underline; text-decoration-color: color-mix(in oklab, var(--brand), #000 25%); }
}
SCSS

# ---------- components/_lists.scss ----------
cat > components/_lists.scss <<'SCSS'
@use "../core/brand" as brand;

@mixin apply() {
  .list { margin: 0; padding: 0; list-style: none; }
  .list > li { padding: .5rem .75rem; border-bottom: 1px solid color-mix(in oklab, var(--surface, #0f172a), #fff 10%); }
  .list > li:last-child { border-bottom: 0; }

  .list--inline { display: flex; gap: .75rem; align-items: center; }
  .list--bullet { list-style: disc; padding-inline-start: 1.25rem; }
  .list--bullet > li { padding: .25rem 0; border: 0; }
}
SCSS

# ---------- components/_avatars.scss ----------
cat > components/_avatars.scss <<'SCSS'
@use "../core/brand" as brand;

@mixin apply() {
  .avatar {
    inline-size: 40px; block-size: 40px; border-radius: 999px;
    background: color-mix(in oklab, var(--surface, #0f172a), #fff 6%);
    border: 1px solid color-mix(in oklab, var(--surface, #0f172a), #fff 12%);
    overflow: hidden; display: inline-block;
  }
  .avatar--sm { inline-size: 28px; block-size: 28px; }
  .avatar--lg { inline-size: 56px; block-size: 56px; }

  .avatar-badge { position: relative; }
  .avatar-badge::after {
    content:""; position:absolute; right:-2px; bottom:-2px;
    inline-size: 10px; block-size: 10px; border-radius: 999px;
    background: brand.token(colors, success, null) or #16a34a;
    border: 2px solid var(--page-bg, #0b1220);
    box-shadow: 0 2px 6px rgba(0,0,0,.3);
  }

  .avatar-stack { display: inline-flex; align-items:center; }
  .avatar-stack > .avatar { margin-inline-start: -10px; border:2px solid var(--page-bg, #0b1220); }
  .avatar-stack > .avatar:first-child { margin-inline-start: 0; }
}
SCSS

# ---------- components/_states.scss ----------
cat > components/_states.scss <<'SCSS'
@mixin apply() {
  .state { padding:.75rem 1rem; border-radius: var(--radius-md, .75rem); border:1px solid transparent; }
  .state--success { background:#052e20; color:#86efac; border-color:#064e3b; }
  .state--warning { background:#2a1905; color:#fdba74; border-color:#7c2d12; }
  .state--danger  { background:#3a0a0a; color:#fecaca; border-color:#7f1d1d; }
  .state--info    { background:#0b1933; color:#93c5fd; border-color:#1e3a8a; }
}
SCSS

# ---------- components/_badges.scss ----------
cat > components/_badges.scss <<'SCSS'
@use "../core/brand" as brand;

@mixin apply() {
  .badge {
    display:inline-flex; align-items:center; gap:.35rem;
    font-size:.75rem; line-height:1; padding:.4rem .6rem;
    border-radius: var(--radius-sm, .5rem);
    background: color-mix(in oklab, var(--surface, #0f172a), #fff 8%);
    border: 1px solid color-mix(in oklab, var(--surface, #0f172a), #fff 16%);
    color: var(--text, #e5e7eb);
  }
  .badge--brand  { background: color-mix(in oklab, var(--brand, #6d28d9), #000 20%); color:#fff; border-color: transparent; }
  .badge--muted  { color: var(--muted, #94a3b8); }
  .badge--ghost  { background: transparent; border-color: color-mix(in oklab, var(--surface, #0f172a), #fff 16%); }
}
SCSS

# ---------- components/_tooltips.scss ----------
cat > components/_tooltips.scss <<'SCSS'
@mixin apply() {
  .tooltip {
    position: relative;
  }
  .tooltip[data-tip]::after {
    content: attr(data-tip);
    position: absolute; inset-inline-start: 50%; translate: -50% -8px; bottom: 100%;
    white-space: nowrap; padding:.4rem .6rem; border-radius: .5rem;
    background: #0b1220; color:#e5e7eb; border:1px solid #1f2a44;
    box-shadow: 0 10px 30px rgba(0,0,0,.35); pointer-events:none;
    opacity:0; transform: translate(-50%,-4px); transition: opacity .15s ease, transform .15s ease;
  }
  .tooltip:hover::after { opacity:1; transform: translate(-50%,-8px); }
}
SCSS

# ---------- components/_tabs.scss ----------
cat > components/_tabs.scss <<'SCSS'
@use "../core/brand" as brand;

@mixin apply() {
  .tabs { display:flex; gap:.5rem; border-bottom:1px solid color-mix(in oklab, var(--surface), #fff 12%); }
  .tab {
    padding:.6rem .9rem; border-radius:.5rem .5rem 0 0;
    color: var(--muted, #94a3b8);
  }
  .tab.is-active {
    color: var(--text, #e5e7eb);
    background: color-mix(in oklab, var(--surface, #0f172a), #fff 6%);
    border: 1px solid color-mix(in oklab, var(--surface, #0f172a), #fff 14%);
    border-bottom-color: transparent;
  }
}
SCSS

# ---------- components/_accordions.scss ----------
cat > components/_accordions.scss <<'SCSS'
@use "../core/brand" as brand;

@mixin apply() {
  .accordion { border-radius: var(--radius-md, .75rem); border:1px solid color-mix(in oklab, var(--surface), #fff 12%); overflow:hidden; }
  .accordion__item + .accordion__item { border-top: 1px solid color-mix(in oklab, var(--surface), #fff 12%); }
  .accordion__head { padding: .9rem 1rem; display:flex; align-items:center; justify-content:space-between; cursor:pointer; }
  .accordion__body { padding: .9rem 1rem; display:none; }
  .accordion__item.is-open .accordion__body { display:block; }
}
SCSS

# ---------- components/_notifications.scss ----------
cat > components/_notifications.scss <<'SCSS'
@mixin apply() {
  .note {
    display:flex; gap:.75rem; align-items:flex-start;
    background: color-mix(in oklab, var(--surface, #0f172a), #fff 6%);
    border: 1px solid color-mix(in oklab, var(--surface, #0f172a), #fff 14%);
    border-radius: var(--radius-lg, 1rem);
    padding: .9rem 1rem;
  }
  .note--success { border-color:#14532d; background:#052e20; color:#86efac; }
  .note--warning { border-color:#7c2d12; background:#2a1905; color:#fdba74; }
  .note--danger  { border-color:#7f1d1d; background:#3a0a0a; color:#fecaca; }
}
SCSS

# ---------- utilities/_utilities.scss ----------
cat > utilities/_utilities.scss <<'SCSS'
@mixin apply() {
  /* مساحة/Spacing */
  :root {
    --space-1:.25rem; --space-2:.5rem; --space-3:.75rem; --space-4:1rem;
    --space-5:1.25rem; --space-6:1.5rem; --space-8:2rem; --space-10:2.5rem; --space-12:3rem;
  }

  .u-container { inline-size:min(1100px, 100%); margin-inline:auto; padding-inline:1rem; }
  .u-grid { display:grid; gap:1rem; }
  .u-flex { display:flex; align-items:center; gap: .75rem; }
  .u-stack > * + * { margin-block-start: .75rem; }

  /* margin utilities */
  .u-m-0 { margin:0!important; } .u-mt-0 { margin-top:0!important; } .u-mb-0 { margin-bottom:0!important; }
  @each $n, $v in (1:var(--space-1), 2:var(--space-2), 3:var(--space-3), 4:var(--space-4), 5:var(--space-5), 6:var(--space-6), 8:var(--space-8), 10:var(--space-10), 12:var(--space-12)) {
    .u-m-#{$n}{ margin:$v!important; } .u-mt-#{$n}{ margin-top:$v!important; } .u-mb-#{$n}{ margin-bottom:$v!important; }
    .u-ml-#{$n}{ margin-left:$v!important; } .u-mr-#{$n}{ margin-right:$v!important; }
    .u-mx-#{$n}{ margin-left:$v!important; margin-right:$v!important; }
    .u-my-#{$n}{ margin-top:$v!important; margin-bottom:$v!important; }
  }

  /* padding utilities */
  .u-p-0 { padding:0!important; }
  @each $n, $v in (1:var(--space-1), 2:var(--space-2), 3:var(--space-3), 4:var(--space-4), 5:var(--space-5), 6:var(--space-6), 8:var(--space-8), 10:var(--space-10), 12:var(--space-12)) {
    .u-p-#{$n}{ padding:$v!important; } .u-pt-#{$n}{ padding-top:$v!important; } .u-pb-#{$n}{ padding-bottom:$v!important; }
    .u-pl-#{$n}{ padding-left:$v!important; } .u-pr-#{$n}{ padding-right:$v!important; }
    .u-px-#{$n}{ padding-left:$v!important; padding-right:$v!important; }
    .u-py-#{$n}{ padding-top:$v!important; padding-bottom:$v!important; }
  }
}
SCSS
