# Architecture — WP Koumbit Slider

## Overview

`wp-koumbit-slider` stores sliders as a custom post type (`wpk_slider`) and renders them via shortcode, classic widget, or Gutenberg block. No custom database tables — all slider data lives in post meta as JSON.

## Directory layout

```
wp-koumbit-slider/
├── wp-koumbit-slider.php   Bootstrap, constants, PSR-4 autoloader
├── src/
│   ├── Activator.php       Activation: register CPT, set default options
│   ├── I18n.php            Text domain loader
│   ├── PostType/
│   │   └── SliderPostType.php  CPT definition, list-table columns
│   ├── Admin/
│   │   ├── AdminMenu.php   Menu placement (suite / main / tools)
│   │   └── EditScreen.php  Meta boxes: slide manager + progressive config
│   └── Frontend/
│       ├── SliderRenderer.php  Generates slider HTML from post meta
│       ├── Shortcode.php       [wpk_slider id="X"]
│       ├── Widget.php          WP_Widget subclass
│       ├── Block.php           Block registration, server-side render
│       └── FrontendAssets.php  Lazy asset enqueueing
├── assets/
│   ├── js/
│   │   ├── admin-editor.js   Slide manager UI + WP media picker
│   │   ├── slider.js         Vanilla JS slider runtime
│   │   └── block-editor.js   Block editor component (window.wp.* globals)
│   └── css/
│       ├── admin.css         Admin editor styles
│       └── frontend.css      Frontend slider styles
├── block.json              Block type definition
└── uninstall.php           Cleanup on uninstall
```

## Data model

### Post: `wpk_slider`

| Field | Purpose |
|---|---|
| `post_title` | Admin-only name (not displayed on frontend) |
| `post_status` | `publish` = active; `draft` = inactive |

### Post meta

| Key | Type | Purpose |
|---|---|---|
| `_wpk_slider_slides` | JSON string | Array of slide objects |
| `_wpk_slider_config` | JSON string | Slider configuration object |

### Slide object schema

```json
{
  "id": 1234567890,
  "image_id": 42,
  "image_url": "https://…/image.jpg",
  "image_alt": "Alt text",
  "title": "Heading",
  "subtitle": "Sub-heading",
  "content": "<p>Body HTML</p>",
  "button_text": "Learn more",
  "button_url": "https://…",
  "button_target": "_self",
  "button_style": "primary",
  "overlay_opacity": 0.4,
  "overlay_color": "#000000",
  "text_align": "center",
  "custom_class": ""
}
```

### Config object schema

```json
{
  "height": "500px",
  "effect": "slide",
  "speed": 500,
  "loop": true,
  "autoplay": false,
  "autoplay_delay": 4000,
  "autoplay_pause_on_hover": true,
  "navigation": true,
  "pagination": "bullets",
  "keyboard": true,
  "swipe": true,
  "slides_per_view": 1,
  "space_between": 0,
  "auto_height": false,
  "centered_slides": false,
  "free_mode": false,
  "lazy": false,
  "direction": "horizontal",
  "overlay_color": "#000000",
  "overlay_opacity": 0.0
}
```

## Class responsibilities

| Class | Responsibility |
|---|---|
| `WPK_Slider_Plugin` | Singleton bootstrap — wire all components |
| `Activator` | Register CPT + flush rewrite + set defaults |
| `I18n` | Load text domain on `init` |
| `SliderPostType` | Register CPT, custom list columns |
| `AdminMenu` | Add menu entry at configured location |
| `EditScreen` | Meta boxes, `save_post` handler, asset enqueuing |
| `SliderRenderer` | Pure HTML renderer from post meta |
| `Shortcode` | `[wpk_slider]` handler — delegates to SliderRenderer |
| `Widget` | `WP_Widget` subclass — delegates to SliderRenderer |
| `Block` | Block registration with `render_callback` — delegates to SliderRenderer |
| `FrontendAssets` | `wp_enqueue_scripts` guard — loads CSS/JS only when a slider is present |

## Asset loading strategy

Frontend assets (`slider.js`, `frontend.css`) are enqueued only when:
1. The current singular post contains `[wpk_slider]` shortcode, **or**
2. The current post contains the `wpk-slider/slider` block, **or**
3. The `wpk_slider_widget` widget is active in any sidebar.

The block `block.json` references `frontend.css` via `style` so WordPress also handles block-registered style loading.

## Hook map

| Hook | Handler | Purpose |
|---|---|---|
| `init` | `SliderPostType::register_post_type` | CPT registration |
| `init` | `I18n::load_textdomain` | Text domain |
| `init` | `Block::register` | Block type |
| `init` | `Shortcode::init` | Shortcode registration |
| `admin_menu` | `AdminMenu::register_menu` | Menu placement |
| `add_meta_boxes` | `EditScreen::register_meta_boxes` | Slide + config meta boxes |
| `save_post_wpk_slider` | `EditScreen::save` | Save JSON meta |
| `admin_enqueue_scripts` | `EditScreen::enqueue` | Admin editor assets |
| `wp_enqueue_scripts` | `FrontendAssets::maybe_enqueue` | Conditional frontend assets |
| `widgets_init` | `register_widget('Widget')` | Widget registration |
