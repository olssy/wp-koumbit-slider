# WP Koumbit Slider

A slider plugin for the Koumbit Suite. Create beautiful image sliders and embed them anywhere via shortcode, widget, or block — no external library required.

## Installation

1. Upload the `wp-koumbit-slider` folder to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Koumbit > Sliders** (or the configured menu location) and click **Add New**.

## Creating a slider

1. Give the slider a title (used only in the admin).
2. Click **Add Slide** to create your first slide.
3. For each slide: pick an image, enter a title, subtitle, body text, and optional call-to-action button.
4. Configure the slider behaviour in the **Slider Settings** panel below.
5. Click **Publish**. Copy the shortcode from the sidebar.

## Embedding

### Shortcode

```
[wpk_slider id="42"]
```

Replace `42` with the slider's post ID (shown in the **Shortcode** column on the Sliders list screen).

### Block editor

Search for **Koumbit Slider** in the block inserter. Select your slider from the sidebar dropdown.

### Classic widget

Go to **Appearance > Widgets** (or the Customizer). Drag the **Koumbit Slider** widget to any sidebar and select your slider.

## Slider settings

| Setting | Options | Default |
|---|---|---|
| Height | Any CSS value (px, vh, auto) | 500px |
| Effect | Slide, Fade | Slide |
| Transition speed | 100–5000 ms | 500 ms |
| Loop | On / Off | On |
| Autoplay | On / Off | Off |
| Autoplay delay | 500–30000 ms | 4000 ms |
| Pause on hover | On / Off | On |
| Navigation arrows | On / Off | On |
| Pagination | Bullets, Fraction, Progress, None | Bullets |
| Keyboard navigation | On / Off | On |
| Touch/swipe | On / Off | On |
| Slides per view | 1–6 | 1 |
| Gap between slides | 0–200 px | 0 |
| Auto height | On / Off | Off |
| Center active slide | On / Off | Off |
| Free drag mode | On / Off | Off |
| Lazy load images | On / Off | Off |
| Direction | Horizontal, Vertical | Horizontal |

## Per-slide options

| Option | Notes |
|---|---|
| Image | Full-resolution or medium-large size from the WP media library |
| Title | `<h2>` rendered over the image |
| Subtitle | Rendered as `<p>` |
| Body text | HTML allowed (`wp_kses_post` filtered) |
| Button label | Text of the CTA button |
| Button URL | Destination URL |
| Open in | Same tab or new tab |
| Button style | Primary, Secondary, Outline, Ghost |
| Text alignment | Centre, Left, Right |
| Overlay colour | Colour picker |
| Overlay opacity | 0 (none) to 1 (fully opaque) |
| Custom CSS class | Added to the slide element |

## Menu location

Change where the Sliders menu entry appears via **Settings > Koumbit Slider > Menu location**:

- **Koumbit Suite** (default) — nested under the shared Koumbit Suite top-level menu
- **WordPress main menu** — top-level entry in the WP admin sidebar
- **WordPress Tools** — nested under Tools

## Accessibility

- Full ARIA carousel markup (`aria-roledescription="carousel"`, `role="group"`, `aria-roledescription="slide"`)
- Navigation arrows and pagination bullets are fully keyboard-accessible
- `prefers-reduced-motion` is respected — transitions and autoplay are disabled when the OS accessibility setting is active
- Focusable elements inside hidden slides are not reachable by keyboard

## FAQ

**Can I use custom HTML in a slide?**  
Yes — the body text field accepts HTML filtered through `wp_kses_post` (same rules as post content).

**How do I change the arrow or bullet colours?**  
Override the CSS variables in your theme:

```css
.wpk-slider-prev,
.wpk-slider-next { background: rgba(255,255,255,0.8); color: #000; }
.wpk-pagination-bullet { background: rgba(0,0,0,0.4); }
.wpk-pagination-bullet.wpk-bullet-active { background: #000; }
```

**Does it work with caching plugins?**  
Yes — slider data is stored in post meta. There are no uncacheable runtime queries on the frontend.
