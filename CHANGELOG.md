# Changelog

All notable changes to this project will be documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [1.1.0] - 2026-06-10

### Added
- **Lazy image loading** — when enabled, slide background images are stored in `data-bg` and loaded via IntersectionObserver (200% viewport pre-load margin). First two slides always load immediately. Falls back to loading all at once when IntersectionObserver is unavailable. Shimmer placeholder animation while images are pending (`prefers-reduced-motion` safe).
- **Thumbnail strip pagination** — new `thumbstrip` pagination option renders a scrollable row (or column for vertical sliders) of clickable thumbnails below the slider. Pre-rendered server-side in PHP so it works without JS for initial paint; JS wires click handlers and active-state sync.
- **Per-slide timing overrides** — each slide can specify a custom transition speed (ms) and CSS easing function that override the slider-level defaults for that one transition. Configurable in the slide edit panel under "Transition timing".
- **Swiper.js integration** — new "Swiper Library" settings section per slider. When enabled, adds Swiper CSS classes to the output and loads Swiper 11 from a CDN (URL filterable via `wpk_slider_swiper_js_url` / `wpk_slider_swiper_css_url`). New Swiper-only effects: Cube (3D), Flip (3D), Coverflow, Cards. All existing config (autoplay, navigation, pagination, keyboard, etc.) maps automatically to Swiper options.

### Changed
- `slider.js` now skips `.wpk-slider-swiper` elements to avoid double-initialisation
- `FrontendAssets` registers Swiper and `swiper-init.js` at priority 5; `SliderRenderer::render()` enqueues them on demand when a slider with `use_swiper: true` is rendered
- Pagination select now includes `thumbstrip` as an option

## [1.0.0] - 2026-06-10

### Added
- `wpk_slider` custom post type for managing slider containers
- Full slide manager UI with drag-order (up/down), inline edit panel, and WP media library integration
- Per-slide: image, title, subtitle, body text, CTA button (label, URL, target, style), text alignment, overlay colour and opacity, custom CSS class
- Progressive-disclosure settings: Layout, Transitions, Autoplay, Controls, Advanced
- Slider effects: slide, fade
- Autoplay with configurable delay and pause-on-hover
- Navigation arrows and four pagination styles (bullets, fraction, progress bar, none)
- Keyboard navigation (arrow keys), touch/swipe via Pointer Events API
- `[wpk_slider id="X"]` shortcode
- Classic widget (`WP_Widget` subclass)
- Gutenberg block (`wpk-slider/slider`) with server-side render callback
- Vanilla JS runtime — no external library dependency
- Frontend assets loaded lazily (only on pages that contain a slider)
- Full ARIA carousel markup: `aria-roledescription`, `aria-label`, `aria-hidden`, live-region updates
- `prefers-reduced-motion` respected in all transitions and autoplay
- Admin-column shortcuts for shortcode and slide count
- Menu placement option: Koumbit Suite (default), WordPress main menu, WordPress Tools
- Uninstall routine removes all post data and options
