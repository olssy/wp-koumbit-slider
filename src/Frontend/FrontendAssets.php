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
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
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
