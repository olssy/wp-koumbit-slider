<?php
/**
 * Activation routines.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider;

defined( 'ABSPATH' ) || exit;

/**
 * @since 1.0.0
 */
class Activator {

	public static function activate(): void {
		// Register the CPT so rewrite rules are available.
		( new PostType\SliderPostType() )->register_post_type();
		flush_rewrite_rules();

		// Global defaults — only set if not already present.
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
