<?php
/**
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider;

defined( 'ABSPATH' ) || exit;

/** @since 1.0.0 */
class I18n {
	public function init(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-koumbit-slider',
			false,
			dirname( WPK_SLIDER_BASENAME ) . '/languages'
		);
	}
}
