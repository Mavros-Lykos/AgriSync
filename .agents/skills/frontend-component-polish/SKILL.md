---
name: frontend-component-polish
description: Guidelines and reusable components for interactive UI elements including live search/filtering, Chart.js 4.x theme palettes, skeleton loaders, and dynamic modal workflows.
---

# AgriSync Frontend Component Polish Skill

This skill provides standardized frontend patterns for responsive AJAX operations, client-side filtering, skeleton loading states, chart visualizations, and toast notifications using Vanilla JS + Bootstrap 5.3.

## 1. Real-Time Client-Side Search & Filter Pattern

Apply instant search on table/card listings without page reload using Vanilla JS:

```javascript
/**
 * Bind live search filter to an input element and target rows
 * @param {string} inputId - ID of search input
 * @param {string} containerSelector - Selector for table body or card grid
 * @param {string} itemSelector - Selector for individual items/rows
 */
function initLiveSearch(inputId, containerSelector, itemSelector) {
    const input = document.getElementById(inputId);
    const container = document.querySelector(containerSelector);
    if (!input || !container) return;

    input.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        const items = container.querySelectorAll(itemSelector);
        let visibleCount = 0;

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const matches = text.includes(query);
            item.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        // Toggle empty search state if no matches
        const emptyState = document.getElementById('searchEmptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    });
}
```

## 2. Skeleton Loading States for Async AI Endpoints

Never show empty blank spaces or blocking alerts during async AJAX fetches. Use animated skeleton loaders:

```html
<!-- Skeleton Placeholder -->
<div class="skeleton-loader-card p-4 rounded-3 bg-white mb-3" id="aiSkeleton">
    <div class="d-flex align-items-center mb-3">
        <div class="skeleton-shimmer rounded-circle me-3" style="width: 48px; height: 48px;"></div>
        <div class="flex-grow-1">
            <div class="skeleton-shimmer rounded mb-2" style="width: 60%; height: 18px;"></div>
            <div class="skeleton-shimmer rounded" style="width: 40%; height: 12px;"></div>
        </div>
    </div>
    <div class="skeleton-shimmer rounded mb-2" style="width: 100%; height: 14px;"></div>
    <div class="skeleton-shimmer rounded" style="width: 85%; height: 14px;"></div>
</div>
```

```css
.skeleton-shimmer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
```

## 3. Chart.js 4.x AgriSync Theme Presets

When instantiating Chart.js, always use the brand green gradient palette:

```javascript
function createAgriGradient(ctx, colorTop = 'rgba(45, 106, 79, 0.4)', colorBottom = 'rgba(45, 106, 79, 0.0)') {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, colorTop);
    gradient.addColorStop(1, colorBottom);
    return gradient;
}

// Chart.js Global Default Overrides
if (window.Chart) {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6C757D';
    Chart.defaults.plugins.tooltip.backgroundColor = '#1B4332';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
}
```

## 4. Toast Notification Protocol

Use the centralized toast utility in `assets/js/main.js`:

```javascript
/**
 * Show animated toast message
 * @param {string} message - User-friendly message
 * @param {'success'|'error'|'warning'|'info'} type - Semantic notification level
 */
showToast(message, type = 'success');
```

- **Rules**:
  - Automatically dismisses in 4 seconds.
  - Pauses on mouse hover.
  - Never display raw database errors or stack traces to end users.
