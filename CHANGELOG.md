# Changelog

All notable changes to this project will be documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

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
