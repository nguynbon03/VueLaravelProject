# Frontend Agent Guide (`frontend/AGENTS.md`)

> Scoped instructions for AI agents working inside `frontend/` (Standalone Vue 3.5 + TypeScript SPA).

## 1. Architecture & Styling Rules
- **100% Decoupled SPA:** Zero Inertia. Communicate exclusively via `src/shared/api/client.ts` against `../docs/api/OPENAPI_V1.md`.
- **Design System First:** Read `./DESIGN.md` before editing styles or components. Every color, radius, spacing, and shadow uses CSS custom properties from `src/styles/tokens.css`.
- **Anti-Slop Guardrails:** Zero emoji/Unicode icons (use `src/shared/ui/AppIcon.vue`), zero gradients/purple glow, zero uppercase tracked eyebrow labels, zero `transition: all`, and mandatory `font-variant-numeric: tabular-nums` on prices/metrics.

## 2. Scoped Verification Commands (Must Exit `0`)
- Strict TypeScript check + Production Vite build: `npm run build`
- UI Anti-Slop Quality Gate: `node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs src --app`
