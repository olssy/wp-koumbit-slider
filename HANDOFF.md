# Handoff — WP Koumbit Slider

**Last updated:** 2026-06-10
**Status:** v1.0.0 complete, pending git init + remote push

---

## Current state

v1.0.0 is feature-complete and ready to ship. All files are written; git has not yet been initialised for this plugin.

### What's in v1.0.0

- `wpk_slider` CPT with title-only editing
- Slide manager meta box: WP media picker integration, drag-order (up/down buttons), inline per-slide edit panel with full config
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

### 1. Widget registration hook

`Widget.php` is registered but the `widgets_init` hook is not yet wired in the main bootstrap (`wp-koumbit-slider.php`). Verify this is connected before testing.

### 2. Block `editorScript` path

`block.json` references `file:./assets/js/block-editor.js` using `editorScript`. This requires WordPress 6.1+ for the `file:` prefix. Confirmed `requires at least: 6.4` so it's fine, but note that if the block registration fails, check that the path resolves relative to the plugin root — not `src/`.

### 3. Settings page

There is no dedicated Settings page for `wpk_slider_menu_location`. The option is written by `Activator` on activation. To change menu location, an admin must currently do it via `update_option` or a future settings tab. A settings tab should be added in v1.1.

### 4. Lazy loading (config option)

The `lazy` config flag is accepted and stored but not yet implemented in `SliderRenderer` or `slider.js`. When `lazy: true` is set, images should use `loading="lazy"` and/or IntersectionObserver deferral. Marked for v1.1.

---

## Before tagging v1.0.0

1. `git init` in this directory
2. `git remote add origin https://github.com/olssy/wp-koumbit-slider`
3. `gh repo create olssy/wp-koumbit-slider --public`
4. Stage and commit all files
5. `git tag v1.0.0 && git push origin main --tags`
6. Add `wp-koumbit-slider | WPKoumbit\Slider\ | WPK_SLIDER_` to parent `CLAUDE.md` plugin registry

---

## Known limitations

- No Settings page UI — menu location defaults to `suite` and cannot be changed from the admin without a code change or `update_option`.
- `lazy` image loading not yet implemented in the frontend runtime.
- No PHPUnit tests.
- Block registration relies on `window.wp.*` globals being available in the editor — standard for WP 6.4+ but blocks without a build step cannot access the full Gutenberg store outside the editor context.
