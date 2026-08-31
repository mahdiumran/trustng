# DESIGN.md — Cyber Command DNS

> Design system specification for the TRUST-NG DNS Control Panel redesign.
> All modifications to visual styling must reference this document.

---

## Brand & Style

This design system is engineered for high-performance network administration. The brand personality is **authoritative, futuristic, and hyper-precise**, evoking the feel of a mission-critical "Command Center." It targets sysadmins, DevOps engineers, and security professionals who require immediate clarity and real-time data visualization.

The visual style is a sophisticated blend of **Glassmorphism** and **High-Contrast Dark Mode**. Key characteristics include:
- **Depth through Translucency:** UI layers utilize backdrop blurs and semi-transparent fills to maintain a sense of environmental depth.
- **Precision Detailing:** 1px borders and high-precision typography reflect the technical nature of DNS management.
- **Luminance over Flatness:** Neon accents are used sparingly as functional "energy sources"—indicating status, active states, and critical alerts through soft glows.

---

## Colors

The palette is anchored in a **Deep Charcoal/Navy base** (#0B0E14), providing a void-like canvas that reduces eye strain during long monitoring sessions.

### Dark Theme (Primary)

| Token | Hex | Usage |
|---|---|---|
| `--background` | `#10131a` | App background |
| `--surface` | `#10131a` | Base surface |
| `--surface-dim` | `#10131a` | Recessed areas |
| `--surface-bright` | `#363940` | Raised areas |
| `--surface-container-lowest` | `#0b0e14` | Deepest layer (void) |
| `--surface-container-low` | `#191c22` | Card background |
| `--surface-container` | `#1d2026` | Panel background |
| `--surface-container-high` | `#272a31` | Hover states |
| `--surface-container-highest` | `#32353c` | Active/pressed |
| `--on-surface` | `#e1e2eb` | Primary text |
| `--on-surface-variant` | `#b9cacb` | Secondary text |
| `--outline` | `#849495` | Borders, dividers |
| `--outline-variant` | `#3a494b` | Subtle borders |
| `--surface-tint` | `#00dbe7` | Surface overlay tint |
| `--primary` | `#e1fdff` | Interactive elements (Cyber Blue) |
| `--on-primary` | `#00363a` | Text on primary |
| `--primary-container` | `#00f2ff` | Primary fill / active states |
| `--on-primary-container` | `#006a71` | Text on primary container |
| `--secondary` | `#f5fff3` | Healthy status (Electric Emerald) |
| `--on-secondary` | `#00391d` | Text on secondary |
| `--secondary-container` | `#27ff97` | Success indicators |
| `--on-secondary-container` | `#00723f` | Text on secondary container |
| `--tertiary` | `#fff5f5` | Critical alerts (Neon Rose) |
| `--on-tertiary` | `#670023` | Text on tertiary |
| `--tertiary-container` | `#ffcfd4` | Error/danger fills |
| `--on-tertiary-container` | `#bf0049` | Text on tertiary container |
| `--error` | `#ffb4ab` | Error text |
| `--on-error` | `#690005` | Text on error |
| `--error-container` | `#93000a` | Error background |
| `--on-error-container` | `#ffdad6` | Text on error container |

### Semantic Color Roles

| Role | Token | Usage |
|---|---|---|
| Primary (Cyber Blue) | `--primary-container` `#00f2ff` | Interactive elements, primary actions, "active/on" states |
| Secondary (Electric Emerald) | `--secondary-container` `#27ff97` | "Healthy" status, uptime, success |
| Tertiary (Neon Rose) | `--tertiary-container` `#ffcfd4` | "Critical" alerts, failures, packet loss |
| Glass Effect | `rgba(16,19,26,0.72)` + `backdrop-filter: blur(16px)` | Semi-transparent panels with blur |

### Glass Effects

```css
--glass-bg: rgba(16, 19, 26, 0.72);
--glass-border: rgba(225, 226, 235, 0.08);
--glass-blur: 16px;
--glass-blur-modal: 40px;
--glow-primary: 0 0 12px rgba(0, 242, 255, 0.25);
--glow-secondary: 0 0 12px rgba(39, 255, 151, 0.25);
--glow-tertiary: 0 0 12px rgba(255, 207, 212, 0.25);
```

---

## Typography

Dual-font strategy: **Inter** for UI, **JetBrains Mono** for data/metrics.

### Font Loading

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
```

### Type Scale

| Level | Font | Size | Weight | Line Height | Letter Spacing | Usage |
|---|---|---|---|---|---|---|
| `display-lg` | Inter | 48px | 700 | 56px | -0.02em | Hero metrics (QPS) |
| `headline-md` | Inter | 24px | 600 | 32px | 0 | Section headers |
| `headline-md-mobile` | Inter | 20px | 600 | 28px | 0 | Mobile section headers |
| `data-lg` | JetBrains Mono | 32px | 500 | 40px | 0 | Primary metrics (IPs, counts) |
| `body-md` | Inter | 16px | 400 | 24px | 0 | Body text, descriptions |
| `label-sm` | JetBrains Mono | 12px | 500 | 16px | 0.05em | Labels, metadata, technical units |

### CSS Variables

```css
--font-ui: 'Inter', system-ui, -apple-system, sans-serif;
--font-mono: 'JetBrains Mono', 'Courier New', monospace;
```

---

## Layout & Spacing

### Structure

- **Sidebar:** Fixed 260px navigation drawer
- **Main Canvas:** Fluid area, 12-column grid
- **Base unit:** 4px for all spacing

### Spacing Tokens

| Token | Value | Usage |
|---|---|---|
| `--unit` | 4px | Base alignment unit |
| `--gutter` | 24px | Grid gutters, card spacing |
| `--margin-page` | 32px | Page edge margins |
| `--panel-padding` | 20px | Card/panel internal padding |
| `--stack-gap` | 12px | Vertical stack spacing between sections |

### Breakpoints

- **Desktop** (>1024px): Full sidebar (260px) + fluid canvas
- **Tablet** (768-1024px): Sidebar collapses to icon-only rail (64px)
- **Mobile** (<768px): Sidebar → hamburger overlay, metrics stack vertically

---

## Elevation & Depth

Depth is conveyed through **Light and Blur** rather than traditional drop shadows.

### Layers

1. **Base Layer:** `--surface-container-lowest` (#0B0E14) — the deepest background
2. **Panel Layer:** Semi-transparent containers with `1px` inner stroke of `rgba(255,255,255,0.08)` — glass edge highlight
3. **Active Elevation:** Focused/active elements get `2px` outer glow using `box-shadow: 0 0 12px rgba(0,242,255,0.25)` (Cyber Blue glow)
4. **Modal Layer:** `backdrop-filter: blur(40px)` to isolate focus

### Card Top Border

Every card must have a `1px` top border that is slightly brighter than side borders to simulate a top-down light source:

```css
border-top: 1px solid rgba(225, 226, 235, 0.12);
border: 1px solid rgba(225, 226, 235, 0.06);
```

---

## Shapes

| Token | Value | Usage |
|---|---|---|
| `--rounded-sm` | 0.125rem (2px) | Small elements, tags |
| `--rounded-default` | 0.25rem (4px) | Default components |
| `--rounded-md` | 0.375rem (6px) | Medium elements |
| `--rounded-lg` | 0.5rem (8px) | Cards, panels |
| `--rounded-xl` | 0.75rem (12px) | Large containers |
| `--rounded-full` | 9999px | Status badges, pills |

Sharp corners are avoided. Pill shapes are reserved exclusively for status badges and chips.

---

## Components

### Buttons
- **Primary:** Solid Cyber Blue (`--primary-container`) fill, black text (`--on-primary`), `--rounded-md`
- **Secondary:** Ghost style — `1px` Cyber Blue border, transparent background, soft hover glow
- **Danger:** Tertiary Rose fill

### Status Indicators
- Small circular dots (8px)
- **Live states:** Subtle "breath" animation — `opacity` pulse from 1.0 to 0.4 over 1.4s infinite
- Colors: Green (`--secondary-container`) for healthy, Rose (`--tertiary-container`) for critical, Amber for warning

### Data Tables
- Row hover: `background: rgba(255,255,255,0.05)`
- IP addresses, TTL values, timestamps: **JetBrains Mono**
- High density: minimize vertical padding

### Input Fields
- Background: `--surface-container-lowest` (darker than panel)
- Border: `1px solid --outline-variant`
- Focus: border → Cyber Blue + `box-shadow: 0 0 4px rgba(0,242,255,0.3)`

### Cards/Panels
- Background: `--glass-bg` with `backdrop-filter: blur(var(--glass-blur))`
- Border: `1px` with brighter top edge (see Elevation)
- Radius: `--rounded-lg`
- Padding: `--panel-padding`

### Charts
- Thin, high-contrast lines (1.5px stroke)
- Fill areas: vertical gradient from stroke color → transparent
- Use Cyber Blue for primary data, Emerald for secondary

---

## Implementation Notes

### CSS Variable Structure

All design tokens are defined as CSS custom properties in `:root`. The dark theme is the default (and only fully implemented theme). A light theme can be added later via `:root.light-mode` overrides.

### Glassmorphism Utility

```css
.glass {
  background: var(--glass-bg);
  backdrop-filter: blur(var(--glass-blur));
  -webkit-backdrop-filter: blur(var(--glass-blur));
  border: 1px solid var(--glass-border);
  border-top-color: rgba(225, 226, 235, 0.12);
}
```

### Active Glow Utility

```css
.glow-primary { box-shadow: 0 0 12px rgba(0, 242, 255, 0.25); }
.glow-secondary { box-shadow: 0 0 12px rgba(39, 255, 151, 0.25); }
.glow-tertiary { box-shadow: 0 0 12px rgba(255, 207, 212, 0.25); }
```

---

*Cyber Command DNS · TRUST-NG DNS Control Panel*
