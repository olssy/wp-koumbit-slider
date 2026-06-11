<?php
/**
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider;

defined( 'ABSPATH' ) || exit;

/**
 * Handles text domain loading for the plugin.
 *
 * @since 1.0.0
 */
class I18n {

	/**
	 * Registers the load_textdomain callback on the init action.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads the plugin text domain from the languages directory.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-koumbit-slider',
			false,
			dirname( WPK_SLIDER_BASENAME ) . '/languages'
		);
	}
}
