# Handoff — WP Koumbit Slider

**Last updated:** 2026-06-10
**Status:** v1.1.0 complete, pushed to GitHub

---

## Current state

v1.1.0 is feature-complete and shipped.

### What's in v1.1.0 (additions over v1.0.0)

- **Lazy loading** — `data-bg` attribute + IntersectionObserver in `slider.js`; shimmer CSS placeholder; first 2 slides always load immediately; adjacent slides preloaded on navigation; falls back to loading all slides when IntersectionObserver is unavailable
- **Thumbnail strip pagination** — `pagination: 'thumbstrip'` config value; PHP renders `<div.wpk-slider-outer>` wrapper + `<div.wpk-slider-thumbstrip>` with server-rendered `<button.wpk-thumb>` elements; JS wires click handlers; full ARIA `role="tab"` + `aria-selected`
- **Per-slide timing** — `custom_speed` (ms, 0 = inherit) and `custom_easing` fields on each slide; output as `data-speed` / `data-easing` attributes; consumed in `slider.js` `goTo()` to temporarily override track transition for that one move
- **Swiper.js** — opt-in per slider via "Swiper Library" settings section; `use_swiper` flag in config; effects: slide, fade, cube, flip, coverflow, cards; `swiper-init.js` translates our config to Swiper options; CDN URLs filterable via `wpk_slider_swiper_js_url` / `wpk_slider_swiper_css_url`

### What's in v1.0.0

- `wpk_slider` CPT with title-only editing
- Slide manager meta box: WP media picker integration, drag-order (up/down buttons), inline per-slide edit panels with full config
- Progressive-disclosure settings meta box: Layout, Transitions, Autoplay, Controls, Advanced
- Server-side slider renderer (`SliderRenderer`)
- `[wpk_slider id="X"]` shortcode
- Classic widget (`WP_Widget` subclass)
- Gutenberg block with server-side render (no build step — uses `window.wp.*` globals)
- Vanilla JS slider runtime: slide/fade effects, autoplay, arrows, bullets/fraction/progress/none pagination, keyboard, touch/swipe via Pointer Events API, ARIA, `prefers-reduced-motion`
- Frontend assets load lazily (only on pages that contain a slider)
- Menu placement option: suite / main / tools
- Uninstall routine

---

## Open decisions

### 1. Settings page

There is no dedicated Settings page for `wpk_slider_menu_location`. The option is written by `Activator` on activation. To change menu location, an admin must currently do it via `update_option`. A settings tab is planned for v1.2.

### 2. Swiper thumbstrip integration

`swiper-init.js` wires `thumbs.swiper` via an inline config object. Swiper 11 supports this pattern but it requires the Thumbs module to be included in the bundle — the CDN `swiper-bundle.min.js` includes it, so this should work. If there are issues, the fallback is to instantiate two separate Swiper instances (one for the main slider, one for the thumbstrip element).

### 3. PHPUnit tests

No tests exist yet. `SliderRenderer::render()`, `EditScreen::sanitize_config()`, and `PostDuplicator` equivalent (n/a here) should be the first targets.

---

## Known limitations

- No Settings page UI — menu location and Swiper CDN URLs have no admin UI; admins use `update_option` or filter hooks.
- Block registration relies on `window.wp.*` globals — standard for WP 6.4+ but blocks without a build step cannot access the full Gutenberg store outside the editor context.
