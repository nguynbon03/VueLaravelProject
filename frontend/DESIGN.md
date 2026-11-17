# Frontend Design System (`frontend/DESIGN.md`)

## 1. Overview
- **Product:** CleanCommerce — Standalone Vue 3.5 Storefront & Merchant Backoffice.
- **Surface Mode:** Hybrid **Persuade + Operate** (Storefront discovery with calm editorial clarity; Checkout & Admin Backoffice in high-density Operate mode).
- **Reference:** Shopify Polaris / Calm Daylight 60-30-10 (`canvas` neutral daylight 60% · `surface` & ink 30% · Deep Commerce Teal `primary` 10%).
- **Light Mode Rationale:** Shoppers inspect real apparel/gear photography, color swatches, and VietQR banking codes with highest accuracy and trust on high-contrast daylight surfaces (`>= 4.5:1` WCAG AA).

## 2. Role-Based Color Tokens (`src/styles/tokens.css`)
| Role | CSS Custom Property | Value | Usage |
| :--- | :--- | :--- | :--- |
| Canvas | `--color-canvas` | `#f7f9f9` | Page background |
| Surface | `--color-surface` / `--color-surface-muted` | `#fcfdfd` / `#eef2f2` | Cards, tables, filter bars |
| Ink | `--color-fg` / `--color-fg-muted` / `--color-fg-subtle` | `#102022` / `#465a5d` / `#64787b` | Primary, secondary, SKU metadata |
| Border | `--color-border` / `--color-border-strong` | `#dce4e5` / `#bfd0d2` | 1px hairlines & input borders |
| Primary | `--color-primary` / `--color-primary-hover` / `--color-primary-soft` | `#0f766e` / `#115e59` / `#ccfbf1` | Primary CTAs, active tabs, focus rings |
| Semantic | `--color-success`, `--color-warning`, `--color-danger` | `#15803d`, `#b45309`, `#b91c1c` | Paired with explicit Vietnamese status text |

## 3. Typography
- **Sans:** `'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif` (complete Vietnamese diacritics).
- **Mono:** `'JetBrains Mono', ui-monospace, monospace` (strictly for SKU codes, Order numbers, and HMAC/Outbox counters).
- **Numeric Rule:** `font-variant-numeric: tabular-nums` on every price, quantity, discount, and KPI figure.

## 4. Layout & Spacing (4px Base Grid)
- Spacing scale: `4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64` px.
- Container max-width: `1240px` (`24px` gutter); touch targets minimum `42px`.

## 5. Elevation & Depth
- Subtle offset+blur elevation only (`--shadow-card`, `--shadow-lift`, `--shadow-overlay`); zero neon halos.

## 6. Shapes
- `--radius-sm: 6px` · `--radius-md: 10px` · `--radius-lg: 14px` · `--radius-xl: 20px`.

## 7. Components
- **Icons:** Uniform `20px` SVG line icons (`stroke-width="1.75"`) via `AppIcon.vue` — zero emoji.
- **States:** Loading skeletons, actionable empty states, inline field errors, and instant `:focus-visible` outlines.

## 8. Do's and Don'ts
- **Do:** Keep button labels action-specific (*"Thêm vào giỏ hàng"*, *"Đặt hàng & Thanh toán"*).
- **Don't:** Never introduce gradient text, uppercase letter-spaced eyebrows, `transition: all`, or fake `Lorem ipsum` / `John Doe` copy.
