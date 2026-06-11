<?php
/**
 * Enqueues slider CSS/JS on pages that contain a slider.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues frontend assets lazily — only when a page actually contains
 * a [wpk_slider] shortcode, the widget, or the block.
 *
 * Uses the standard wp_enqueue_scripts hook with has_shortcode() and
 * is_active_widget() guards so assets are never loaded on pages that
 * don't need them.
 *
 * @since 1.0.0
 */
class FrontendAssets {

	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
	}

	/**
	 * Registers all plugin scripts/styles so they can be enqueued on demand.
	 *
	 * Swiper is loaded from a filterable CDN URL; sites that host Swiper locally
	 * can override both URLs via the wpk_slider_swiper_js_url / wpk_slider_swiper_css_url filters.
	 */
	public function register(): void {
		$swiper_js_url = apply_filters(
			'wpk_slider_swiper_js_url',
			'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js'
		);
		$swiper_css_url = apply_filters(
			'wpk_slider_swiper_css_url',
			'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css'
		);

		wp_register_script( 'swiper', $swiper_js_url, array(), '11', true );
		wp_register_style( 'swiper', $swiper_css_url, array(), '11' );

		wp_register_script(
			'wpk-slider-swiper-init',
			WPK_SLIDER_URL . 'assets/js/swiper-init.js',
			array( 'swiper' ),
			WPK_SLIDER_VERSION,
			true
		);
	}

	public function maybe_enqueue(): void {
		global $post;

		$should_load = $this->page_has_slider( $post );

		if ( ! $should_load ) {
			return;
		}

		$this->enqueue();
	}

	/**
	 * Forces the assets to load regardless of page context.
	 * Called directly when rendering a widget or block outside of singular context.
	 */
	public function enqueue(): void {
		if ( wp_style_is( 'wpk-slider-frontend', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpk-slider-frontend',
			WPK_SLIDER_URL . 'assets/css/frontend.css',
			array(),
			WPK_SLIDER_VERSION
		);

		wp_enqueue_script(
			'wpk-slider-frontend',
			WPK_SLIDER_URL . 'assets/js/slider.js',
			array(),
			WPK_SLIDER_VERSION,
			true
		);
	}

	/**
	 * Determines whether the current page has slider content.
	 *
	 * @param \WP_Post|null $post
	 * @return bool
	 */
	private function page_has_slider( ?\WP_Post $post ): bool {
		// Singular post with the shortcode.
		if ( $post && has_shortcode( $post->post_content, 'wpk_slider' ) ) {
			return true;
		}

		// Block is present.
		if ( $post && has_block( 'wpk-slider/slider', $post ) ) {
			return true;
		}

		// Widget is active in any sidebar.
		if ( is_active_widget( false, false, 'wpk_slider_widget' ) ) {
			return true;
		}

		return false;
	}
}
