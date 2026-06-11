<?php
/**
 * Activation routines.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation: CPT registration, rewrite flush, and default options.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Runs once when the plugin is activated.
	 *
	 * Registers the CPT so rewrite rules resolve immediately, then seeds
	 * any global options that are not yet present in the database.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate(): void {
		( new PostType\SliderPostType() )->register_post_type();
		flush_rewrite_rules();

		// Swiper CDN URLs are set via the wpk_slider_swiper_js_url /
		// wpk_slider_swiper_css_url filter hooks, not stored as options.
		$defaults = array(
			'wpk_slider_menu_location' => 'suite',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
