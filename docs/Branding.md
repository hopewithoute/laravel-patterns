# NieR: Automata — UI/UX Design Guideline
> **Codename:** YoRHa Interface System  
> **Version:** 1.0.0  
> **Maintainer:** Design System Team  
> **Last Updated:** 2026

---

## Table of Contents
1. [Design Philosophy](#1-design-philosophy)
2. [Color System](#2-color-system)
3. [Typography](#3-typography)
4. [Grid & Layout](#4-grid--layout)
5. [UI Components](#5-ui-components)
6. [Iconography & Motif](#6-iconography--motif)
7. [Motion & Animation](#7-motion--animation)
8. [HUD Design Principles](#8-hud-design-principles)
9. [Tone & Voice](#9-tone--voice)
10. [Do's & Don'ts](#10-dos--donts)

---

## 1. Design Philosophy

### Core Concept
> *"Systematic and sterile, but also beautiful."*  
> — Hisayoshi Kijima, UI Designer, PlatinumGames

The YoRHa Interface System is designed to simulate the **internal operating system of an android** — cold, precise, and efficient — yet imbued with an unexpected warmth that evokes humanity. Every design decision must answer two questions:

1. **Does it feel machine-like?** — Structure, grid, hierarchy, sterility.
2. **Does it feel alive?** — Warmth, elegance, subtle imperfection.

### Three Pillars

| Pillar | Description |
|--------|-------------|
| **Systematic** | Everything follows a strict visual grammar. No arbitrary decoration. |
| **Sterile** | Minimal color, no noise, no visual clutter. Information breathes. |
| **Beautiful** | Warm undertones, musical motifs, and elegant proportions elevate the functional. |

### Design Influences
- Flat design (base layer)
- Gothic Lolita aesthetic (elegance with darkness)
- Art Deco typography (structured ornamental fonts)
- CRT Monitor distortion (analog warmth in digital space)
- Musical score notation (horizontal rhythm and structure)

---

## 2. Color System

### Philosophy
Color is used **sparingly and intentionally**. The system relies on **value (lightness/darkness)** and **font weight** to convey hierarchy — not a rainbow of hues. Limit the palette aggressively.

### Primary Palette

| Token | Name | Hex | Usage |
|-------|------|-----|-------|
| `--color-bg-primary` | Warm Beige | `#E8D8C4` | Main background, panels |
| `--color-bg-secondary` | Sand | `#D4B896` | Secondary panels, sidebars |
| `--color-bg-dark` | Aged Paper | `#C9A87C` | Hover states, active containers |
| `--color-text-primary` | Dark Walnut | `#3F352F` | Primary body text |
| `--color-text-secondary` | Faded Walnut | `#6B5A50` | Secondary/label text |
| `--color-text-muted` | Dust | `#9E8E84` | Placeholder, disabled state |
| `--color-surface-dark` | Deep Charcoal | `#1A1714` | Dark mode background |
| `--color-surface-dark-2` | Midnight | `#252019` | Dark mode secondary surface |

### Accent Palette
Accent color digunakan **hanya untuk informasi kritis** — error, stat decrease, highlight penting.

| Token | Name | Hex | Usage |
|-------|------|-----|-------|
| `--color-accent-red` | Drab Red-Orange | `#C0392B` | Stat decrease, error, danger |
| `--color-accent-green` | Pale Sage | `#A8C4A2` | Stat increase, success (rare) |
| `--color-accent-white` | Cold White | `#F4EFE8` | High-contrast text on dark bg |

### Color Rules
- **Never use more than 2 accent colors** on a single screen simultaneously.
- Background always defaults to warm beige on light mode, deep charcoal on dark mode.
- Do not use pure `#FFFFFF` or pure `#000000` — they are too harsh for this aesthetic.
- Color alone must **never** be the sole indicator of information state (accessibility).

### Dark Mode Mapping

| Light Token | Dark Equivalent |
|-------------|-----------------|
| `--color-bg-primary` (#E8D8C4) | `--color-surface-dark` (#1A1714) |
| `--color-text-primary` (#3F352F) | `--color-accent-white` (#F4EFE8) |
| `--color-bg-secondary` (#D4B896) | `--color-surface-dark-2` (#252019) |

---

## 3. Typography

### Typeface System

#### Display / Logo Font
- **Family:** ITC Benguiat Book (or fallback: Cormorant Garamond)
- **Style:** Art Deco Serif — structured, slightly ornamental, timeless
- **Usage:** Game/app title, hero headings, splash screens

```css
font-family: 'ITC Benguiat Book', 'Cormorant Garamond', Georgia, serif;
```

#### UI / Interface Font
- **Family:** Rajdhani (atau IBM Plex Mono untuk data values)
- **Style:** Condensed, clean, semi-geometric sans-serif
- **Usage:** Menu labels, stat values, system messages, buttons

```css
font-family: 'Rajdhani', 'IBM Plex Mono', monospace;
```

#### Body / Long-form Font
- **Family:** Source Serif 4 (atau Lora)
- **Style:** Readable serif, warm
- **Usage:** Descriptions, lore text, quest logs

```css
font-family: 'Source Serif 4', 'Lora', Georgia, serif;
```

### Type Scale

| Level | Token | Size | Weight | Usage |
|-------|-------|------|--------|-------|
| Display | `--text-display` | 48px / 3rem | 300 | App/game title |
| H1 | `--text-h1` | 32px / 2rem | 400 | Screen titles |
| H2 | `--text-h2` | 24px / 1.5rem | 600 | Section headers |
| H3 | `--text-h3` | 18px / 1.125rem | 600 | Sub-section headers |
| Body | `--text-body` | 16px / 1rem | 400 | Default body text |
| Small | `--text-small` | 13px / 0.8125rem | 400 | Labels, captions |
| Micro | `--text-micro` | 11px / 0.6875rem | 500 | System info, metadata |

### Hierarchy melalui Typography (bukan warna)
Karena palet warna terbatas, hierarki **harus dibangun dari tipografi**:

```
Penting    → font-weight: 700 + color: --color-text-primary
Normal     → font-weight: 400 + color: --color-text-primary
Sekunder   → font-weight: 400 + color: --color-text-secondary
Disabled   → font-weight: 400 + color: --color-text-muted
```

---

## 4. Grid & Layout

### Base Grid
- **System:** 8px base unit
- **Columns:** 12 columns (desktop), 4 columns (mobile)
- **Gutter:** 16px (desktop), 12px (mobile)
- **Margin:** 24px (desktop), 16px (mobile)

### Layout Principles

1. **Rectangular dominance** — Semua container adalah persegi panjang. Hindari border-radius besar; gunakan 0px–4px maksimum.
2. **Left-aligned hierarchy** — Konten selalu rata kiri, bukan center (kecuali splash/hero).
3. **Breathing space** — Padding dalam panel minimal 16px; biarkan konten "bernafas".
4. **Horizontal rhythm** — Staff lines: gunakan garis horizontal tipis (1px, opacity 20–30%) sebagai pemisah, bukan garis tebal atau warna.

### Spacing Scale

```
--space-1:  4px
--space-2:  8px
--space-3:  12px
--space-4:  16px
--space-5:  24px
--space-6:  32px
--space-7:  48px
--space-8:  64px
--space-9:  96px
```

---

## 5. UI Components

### Panel / Container

```css
.panel {
  background: var(--color-bg-primary);
  border: 1px solid rgba(63, 53, 47, 0.25);
  border-radius: 2px;
  padding: var(--space-4) var(--space-5);
  position: relative;
}

/* Staff line separator (musical score motif) */
.panel::before {
  content: '';
  display: block;
  height: 1px;
  background: rgba(63, 53, 47, 0.15);
  margin-bottom: var(--space-4);
}
```

### Button

Buttons menggunakan **border, bukan fill** sebagai default. Fill hanya untuk primary action.

```css
/* Default / Secondary */
.btn {
  font-family: 'Rajdhani', monospace;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 8px 20px;
  background: transparent;
  border: 1px solid var(--color-text-primary);
  color: var(--color-text-primary);
  border-radius: 0px; /* No radius — strict rectangular */
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}

.btn:hover {
  background: var(--color-text-primary);
  color: var(--color-bg-primary);
}

/* Primary Action */
.btn-primary {
  background: var(--color-text-primary);
  color: var(--color-bg-primary);
}

/* Danger / Destructive */
.btn-danger {
  border-color: var(--color-accent-red);
  color: var(--color-accent-red);
}
```

### Input Field

```css
.input {
  font-family: 'Rajdhani', monospace;
  font-size: 14px;
  background: transparent;
  border: none;
  border-bottom: 1px solid rgba(63, 53, 47, 0.5);
  border-radius: 0;
  padding: 8px 0;
  color: var(--color-text-primary);
  width: 100%;
  outline: none;
  transition: border-color 0.2s ease;
}

.input:focus {
  border-bottom-color: var(--color-text-primary);
}

.input::placeholder {
  color: var(--color-text-muted);
  letter-spacing: 0.05em;
}
```

### Menu Navigation Item

```css
.nav-item {
  font-family: 'Rajdhani', monospace;
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--color-text-secondary);
  padding: 10px 16px;
  border-left: 2px solid transparent;
  transition: all 0.15s ease;
  cursor: pointer;
}

.nav-item:hover {
  color: var(--color-text-primary);
  border-left-color: var(--color-text-primary);
  background: rgba(63, 53, 47, 0.06);
}

.nav-item.active {
  color: var(--color-text-primary);
  font-weight: 700;
  border-left-color: var(--color-text-primary);
}
```

### Stat / Data Row

Gunakan font weight untuk menyampaikan perubahan stat (bukan warna):

```css
.stat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid rgba(63, 53, 47, 0.12);
  font-family: 'Rajdhani', monospace;
}

.stat-value.increase { color: var(--color-accent-green); font-weight: 700; }
.stat-value.decrease { color: var(--color-accent-red); font-weight: 700; }
.stat-value.neutral  { color: var(--color-text-primary); font-weight: 400; }
```

### Divider (Staff Line Motif)

```css
.divider {
  width: 100%;
  height: 1px;
  background: linear-gradient(
    to right,
    transparent,
    rgba(63, 53, 47, 0.3) 15%,
    rgba(63, 53, 47, 0.3) 85%,
    transparent
  );
  margin: var(--space-4) 0;
}

/* Double bar line (end-of-section motif) */
.divider-double {
  border-top: 1px solid rgba(63, 53, 47, 0.3);
  border-bottom: 3px solid rgba(63, 53, 47, 0.3);
  height: 4px;
}
```

---

## 6. Iconography & Motif

### Gaya Icon
- **Style:** Line icons, 1.5px stroke, no fill
- **Shape language:** Geometric, angular, minimal curves
- **Grid:** 24×24px base, 16×16px compact
- **Corner:** Sharp (0px radius)

### Motif Wajib: Musical Score

Setiap screen wajib memiliki salah satu dari motif ini:

| Motif | Representasi Visual | Implementasi |
|-------|---------------------|--------------|
| **Staff lines** | Garis horizontal tipis paralel | `border-bottom` repeating, opacity 15–20% |
| **Double bar** | Dua garis vertikal berdekatan | Divider akhir section |
| **Bracket** | Kurung sudut `[` dan `]` | Corner decoration pada panel header |
| **Colon `:` separator** | Pemisah label–value | `Label : Value` pattern di semua data display |

### Dekorasi Corner Panel
Terinspirasi dari bracket notasi musik — tambahkan corner accent pada panel utama:

```css
.panel-decorated::before,
.panel-decorated::after {
  content: '';
  position: absolute;
  width: 12px;
  height: 12px;
  border-color: var(--color-text-primary);
  border-style: solid;
  opacity: 0.4;
}

.panel-decorated::before {
  top: 8px; left: 8px;
  border-width: 1px 0 0 1px;
}

.panel-decorated::after {
  bottom: 8px; right: 8px;
  border-width: 0 1px 1px 0;
}
```

### CRT Effect (Opsional, Gunakan dengan Hemat)

```css
.crt-overlay {
  background: radial-gradient(
    ellipse at center,
    transparent 60%,
    rgba(26, 23, 20, 0.35) 100%
  );
  pointer-events: none;
  position: fixed;
  inset: 0;
  z-index: 9999;
}
```

---

## 7. Motion & Animation

### Prinsip
Animasi harus terasa **mekanis dan presisi** — bukan organic/bouncy. Gunakan ease linear atau ease-out; hindari spring physics.

### Duration Scale

```css
--duration-instant:  80ms   /* Microinteraction, highlight */
--duration-fast:    150ms   /* Button hover, icon swap */
--duration-normal:  250ms   /* Panel open, tab switch */
--duration-slow:    400ms   /* Screen transition */
--duration-enter:   500ms   /* Initial load, splash */
```

### Easing

```css
--ease-ui:       cubic-bezier(0.25, 0.1, 0.25, 1.0)  /* Default */
--ease-enter:    cubic-bezier(0.0, 0.0, 0.2, 1.0)    /* Enter screen */
--ease-exit:     cubic-bezier(0.4, 0.0, 1.0, 1.0)    /* Exit screen */
--ease-sharp:    cubic-bezier(0.4, 0.0, 0.6, 1.0)    /* System actions */
```

### Signature Animations

**Screen Enter:**
```css
@keyframes screen-enter {
  from {
    opacity: 0;
    transform: translateX(-8px);
    filter: blur(2px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
    filter: blur(0);
  }
}
```

**Glitch / System Error:**
```css
@keyframes glitch {
  0%   { transform: translate(0); }
  20%  { transform: translate(-2px, 1px); filter: hue-rotate(90deg); }
  40%  { transform: translate(2px, -1px); }
  60%  { transform: translate(0); }
  80%  { transform: translate(1px, 2px); filter: hue-rotate(-90deg); }
  100% { transform: translate(0); filter: hue-rotate(0); }
}
```

**Scan Line (loading state):**
```css
@keyframes scan {
  from { transform: translateY(-100%); }
  to   { transform: translateY(100%); }
}
```

---

## 8. HUD Design Principles

Jika produk memiliki **real-time overlay** (dashboard, live monitor, status display):

### Rules
1. **Minimal footprint** — HUD element tidak boleh menutupi konten utama lebih dari 15% layar.
2. **Contextual visibility** — Sembunyikan elemen HUD yang tidak relevan. Tampilkan hanya saat dibutuhkan (fade-in on interaction).
3. **Corner anchoring** — Elemen HUD ditempatkan di sudut atau tepi layar; bukan tengah.
4. **Monochrome first** — HUD default dalam warna beige/charcoal monokromatik; warna aksen hanya untuk state kritis.
5. **No drop shadows** — Gunakan border tipis dan background opacity sebagai gantinya.

### HUD Layout Zones

```
┌──────────────────────────────────────────┐
│ [TOP-LEFT]              [TOP-RIGHT]      │
│ Status / Minimap        System Clock     │
│                                          │
│                                          │
│           CONTENT AREA                   │
│                                          │
│                                          │
│ [BOTTOM-LEFT]           [BOTTOM-RIGHT]   │
│ Action Queue            Stat Readout     │
└──────────────────────────────────────────┘
```

---

## 9. Tone & Voice

### Writing Style

Semua teks UI harus ditulis seolah-olah **dihasilkan oleh sistem android** — presisi, impersonal, namun tidak kasar.

| Context | Tone | Contoh |
|---------|------|--------|
| System status | Terse, informatif | `"Data synchronized. 847 records processed."` |
| Error message | Faktual, non-emosional | `"Operation failed. Retry or abort mission."` |
| Empty state | Observasional | `"No data found in this sector."` |
| Confirmation | Singkat, definitif | `"Action confirmed. Proceeding."` |
| Loading | Present tense pasif | `"Retrieving combat data..."` |

### Formatting Rules
- Gunakan **titik dua** (`:`) sebagai pemisah label–value: `HP : 9500`
- **Uppercase** untuk judul, tab, dan label navigasi
- **Sentence case** untuk body text dan deskripsi
- Hindari tanda seru (`!`) — terlalu emosional untuk sistem ini
- Nomor menggunakan format **monospace** agar align secara vertikal

---

## 10. Do's & Don'ts

### ✅ DO
- Gunakan rectangles dan angular shapes secara konsisten
- Biarkan whitespace berbicara — jangan takut dengan ruang kosong
- Tambahkan motif staff line / bracket di setiap panel
- Gunakan font weight sebagai pengganti warna untuk hierarki
- Terapkan subtle CRT vignette di layer paling atas
- Tulis semua label UI dalam tone sistem yang dingin namun elegan
- Gunakan `border-radius: 0` atau maksimal `2px`

### ❌ DON'T
- Jangan gunakan lebih dari 2 warna aksen secara bersamaan
- Jangan gunakan drop-shadow — ganti dengan border tipis
- Jangan gunakan rounded cards (border-radius > 4px)
- Jangan gunakan gradien warna-warni
- Jangan gunakan ikon filled/solid — gunakan outline only
- Jangan menulis teks UI dengan nada cheerful atau informal
- Jangan gunakan pure `#000000` atau `#FFFFFF`
- Jangan campur font serif dan sans-serif dalam satu komponen yang sama

---

## Appendix: CSS Custom Properties (Full Token Sheet)

```css
:root {
  /* Colors */
  --color-bg-primary:       #E8D8C4;
  --color-bg-secondary:     #D4B896;
  --color-bg-dark:          #C9A87C;
  --color-text-primary:     #3F352F;
  --color-text-secondary:   #6B5A50;
  --color-text-muted:       #9E8E84;
  --color-surface-dark:     #1A1714;
  --color-surface-dark-2:   #252019;
  --color-accent-red:       #C0392B;
  --color-accent-green:     #A8C4A2;
  --color-accent-white:     #F4EFE8;

  /* Typography */
  --font-display:   'ITC Benguiat Book', 'Cormorant Garamond', Georgia, serif;
  --font-ui:        'Rajdhani', 'IBM Plex Mono', monospace;
  --font-body:      'Source Serif 4', 'Lora', Georgia, serif;

  --text-display:   3rem;
  --text-h1:        2rem;
  --text-h2:        1.5rem;
  --text-h3:        1.125rem;
  --text-body:      1rem;
  --text-small:     0.8125rem;
  --text-micro:     0.6875rem;

  /* Spacing */
  --space-1: 4px;   --space-2: 8px;   --space-3: 12px;
  --space-4: 16px;  --space-5: 24px;  --space-6: 32px;
  --space-7: 48px;  --space-8: 64px;  --space-9: 96px;

  /* Animation */
  --duration-instant: 80ms;
  --duration-fast:   150ms;
  --duration-normal: 250ms;
  --duration-slow:   400ms;
  --ease-ui:         cubic-bezier(0.25, 0.1, 0.25, 1.0);
  --ease-enter:      cubic-bezier(0.0,  0.0, 0.2,  1.0);
  --ease-exit:       cubic-bezier(0.4,  0.0, 1.0,  1.0);
}
```

---

*"Glory to Mankind."*  
— YoRHa Interface System v1.0.0