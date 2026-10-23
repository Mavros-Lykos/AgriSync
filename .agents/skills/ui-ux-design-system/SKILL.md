---
name: ui-ux-design-system
description: Standardizes modern, premium UI/UX design patterns, agricultural SaaS color palettes, glassmorphism, typography, and micro-interactions for AgriSync.
---

# AgriSync UI/UX Design System Skill

This skill governs the visual architecture, styling tokens, and interaction design across all AgriSync portals (Farmer, Commercial Buyer, Admin, and Public Landing).

## 1. Color Palette & Semantic Tokens

Always use defined CSS variables from `assets/css/style.css` rather than ad-hoc hex values:

| Token | Hex | Semantic Role |
|---|---|---|
| `--clr-primary` | `#2D6A4F` | Main brand color, primary buttons, active navbar highlights |
| `--clr-primary-dark` | `#1B4332` | Header bars, footer, high-contrast text |
| `--clr-primary-light` | `#40916C` | Secondary buttons, border accents, active tabs |
| `--clr-accent` | `#95D5B2` | AI highlight badges, progress bars, success borders |
| `--clr-accent-light` | `#D8F3DC` | Soft pill backgrounds, selected row highlights |
| `--clr-bg` | `#F8F9FA` | Page body background (clean off-white) |
| `--clr-surface` | `#FFFFFF` | Card & modal background |
| `--clr-text` | `#212529` | Primary headings & high-emphasis body text |
| `--clr-text-muted` | `#6C757D` | Timestamps, secondary labels, helper text |
| `--clr-danger` | `#DC3545` | Errors, rejection buttons, high-risk warnings |
| `--clr-warning` | `#FFC107` | Pending states, medium urgency badges |
| `--clr-info` | `#0DCAF0` | Informational cards, telemetry badges |

## 2. Card Elevation & Glassmorphism

- **Standard Card Container**:
  ```css
  .agri-card {
      background: #ffffff;
      border-radius: 14px;
      border: 1px solid rgba(45, 106, 79, 0.08);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      overflow: hidden;
  }
  .agri-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 28px rgba(45, 106, 79, 0.09);
  }
  ```
- **Glassmorphic AI Card**:
  ```css
  .ai-glass-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(216, 243, 220, 0.4));
      backdrop-filter: blur(10px);
      border: 1px solid rgba(149, 213, 178, 0.4);
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(45, 106, 79, 0.08);
  }
  ```

## 3. Typography Standards

- **Font Family**: `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif`
- **Heading 1 (`h1`, `.page-title`)**: `1.75rem`, `font-weight: 700`, `letter-spacing: -0.02em`
- **Heading 2 (`h2`, `.section-title`)**: `1.35rem`, `font-weight: 600`, `letter-spacing: -0.01em`
- **Heading 3 (`h3`, `.card-title`)**: `1.15rem`, `font-weight: 600`
- **Body Regular**: `0.95rem` or `1rem`, `font-weight: 400`, `line-height: 1.5`
- **Badges & Meta (`.extra-small`)**: `0.75rem` - `0.85rem`, `font-weight: 500`

## 4. Status Badges & Lifecycle Indicators

Always represent order/listing lifecycle states with uniform badge tokens:

| Status | Badge Classes | Icon | Description |
|---|---|---|---|
| `available` / `open` | `badge bg-success-subtle text-success border border-success-subtle` | `bi bi-check-circle-fill` | Active produce listing |
| `pending` / `matching` | `badge bg-warning-subtle text-dark border border-warning-subtle` | `bi bi-hourglass-split` | Awaiting broker match |
| `matched` / `proposed` | `badge bg-primary-subtle text-primary border border-primary-subtle` | `bi bi-stars` | AI Broker proposed match |
| `accepted` / `confirmed` | `badge bg-success-subtle text-success border border-success-subtle` | `bi bi-hand-thumbs-up-fill` | Farmer/Buyer accepted |
| `in_transit` | `badge bg-info-subtle text-info border border-info-subtle` | `bi bi-truck` | Logistics underway |
| `delivered` / `completed` | `badge bg-secondary-subtle text-secondary border border-secondary-subtle` | `bi bi-check2-all` | Order fulfilled |
| `cancelled` / `rejected` | `badge bg-danger-subtle text-danger border border-danger-subtle` | `bi bi-x-circle-fill` | Cancelled / Declined |

## 5. Micro-Animations & Interactivity

- **Button Hover**: Min height `44px`, `border-radius: 8px`, `transition: all 0.2s ease`
- **Pulse Indicator on Live Elements**:
  ```css
  .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: var(--clr-primary);
      box-shadow: 0 0 0 0 rgba(45, 106, 79, 0.7);
      animation: pulse-ring 1.8s infinite;
  }
  @keyframes pulse-ring {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(45, 106, 79, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(45, 106, 79, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(45, 106, 79, 0); }
  }
  ```
- **Empty States**: Every data table or feed with 0 records MUST render a centered empty state illustration/icon, a helpful 1-2 sentence explanation, and a direct CTA button.
