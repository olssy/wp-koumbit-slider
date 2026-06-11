<?php
/**
 * [wpk_slider] shortcode.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the [wpk_slider id="X"] shortcode.
 *
 * @since 1.0.0
 */
class Shortcode {

	/**
	 * Registers the wpk_slider shortcode.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_shortcode( 'wpk_slider', array( $this, 'render' ) );
	}

	/**
	 * Shortcode callback for [wpk_slider id="X"].
	 *
	 * @since 1.0.0
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts( array( 'id' => '0' ), $atts, 'wpk_slider' );

		$id = absint( $atts['id'] );
		if ( $id <= 0 ) {
			return '';
		}

		return ( new SliderRenderer() )->render( $id );
	}
}
