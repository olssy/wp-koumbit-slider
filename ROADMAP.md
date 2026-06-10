# Roadmap — WP Koumbit Slider

## v1.1 — Enhanced effects & library option

- Swiper.js integration (opt-in via settings) for advanced effects: cube, flip, coverflow, parallax
- Thumbnail strip pagination style
- Per-slide custom animation delay and easing override
- Lazy-load image implementation using IntersectionObserver

## v1.2 — Content types

- Video slide support (YouTube, Vimeo, self-hosted `<video>`)
- HTML/shortcode slide type — embed any content as a slide
- Multi-CTA support (up to 3 buttons per slide)

## v1.3 — Community content integration

- Event slides — link a wpk_event post; pull title, date, featured image, and registration status automatically
- Staff spotlight slides — link a wpk_staff post; pull photo, name, role, and bio snippet
- Alert banner mode — thin single-slide strip at the top of a page; pulls from wp-koumbit-alert posts
- Announcement slides — auto-populate from any post type and category, refresh on schedule via transient cache

## v1.4 — Responsive breakpoints

- Per-breakpoint `slides_per_view` and `space_between` (mobile / tablet / desktop)
- Vertical-to-horizontal direction switch on breakpoint
- Configurable arrow size and position per breakpoint

## v1.5 — Accessibility & performance

- Full keyboard focus management between slides (roving tabindex)
- `aria-live="polite"` slide title announcement for screen readers
- Play/pause button for autoplay (WCAG 2.1 SC 2.2.2)
- Critical CSS inline path — output minimal styles in `<head>` for above-the-fold sliders

## v1.6 — Analytics

- Slide view counts stored in post meta (incremented via REST endpoint on JS visibility event)
- Click-through rate per slide CTA (UTM parameter injection on button links)
- Simple dashboard widget: top-performing slides by views

## v1.7 — Import / export

- Export sliders as JSON for migration between sites
- Duplicate slider action (integrates with wp-koumbit-duplicator)
- Template library — pre-built slide sets: hero banner, team introduction, service highlights, event promotion
