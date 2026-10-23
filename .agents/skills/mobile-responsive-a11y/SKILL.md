---
name: mobile-responsive-a11y
description: Mobile-first responsive design, touch-friendly UI ergonomics (≥44px tap targets), accessibility standards (WCAG AA), and low-bandwidth optimization for Sri Lankan rural farmers.
---

# AgriSync Mobile Responsiveness & Accessibility (A11y) Skill

This skill guarantees that all interfaces across AgriSync remain completely responsive, accessible, and performant on mobile devices, low-cost Android phones, and rural 3G/4G network conditions.

## 1. Mobile Touch Ergonomics

- **Minimum Touch Target Size**: All interactive buttons, icon triggers, form inputs, and pagination controls MUST have a minimum clickable/tappable bounding box of **$44\text{px} \times 44\text{px}$**.
  ```css
  .btn, .form-control, .form-select, .nav-link-mobile {
      min-height: 44px;
  }
  ```
- **Tap Spacing**: Ensure at least $8\text{px}$ of margin/gap between adjacent action buttons on mobile screens to prevent accidental taps.
- **Floating Action Buttons (FAB)**: On mobile views (`max-width: 768px`), primary creation actions (*e.g., "+ Add Harvest Listing"*) can be pinned or anchored at the bottom right for ergonomic thumb reach.

## 2. Table to Card Responsive Transformation

Avoid unreadable horizontal overflow on small screens:

```css
@media (max-width: 768px) {
    .table-responsive-stack thead {
        display: none;
    }
    .table-responsive-stack tr {
        display: block;
        margin-bottom: 1rem;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0.75rem;
    }
    .table-responsive-stack td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px dashed #f0f0f0;
        padding: 0.5rem 0;
    }
    .table-responsive-stack td:last-child {
        border-bottom: none;
    }
    .table-responsive-stack td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6C757D;
        font-size: 0.85rem;
    }
}
```

## 3. High-Contrast Sunlight Readability

Farmers frequently view screens outdoors in bright tropical sunlight:

- Maintain a minimum contrast ratio of **4.5:1** for normal text and **3:1** for large text against backgrounds (WCAG 2.1 Level AA).
- Avoid light gray text (`#ced4da`) on white backgrounds for critical numeric data (prices, weights). Use `#495057` or `#212529`.
- Bold labels on key metrics (`font-weight: 600` or `700`).

## 4. Low-Bandwidth & Offline Resilience

- Use inline SVGs or vector icon fonts (Bootstrap Icons) instead of heavy PNGs for UI controls.
- Cache static CSS/JS assets with long-lived browser cache headers.
- Show clear connection warning banners if `navigator.onLine === false`.
