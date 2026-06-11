<?php
/**
 * Block registration for the Koumbit Slider block.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the wpk-slider/slider block with a server-side render callback.
 *
 * @since 1.0.0
 */
class Block {

	/**
	 * Registers the block type on the init action.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers the wpk-slider/slider block type from block.json.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register(): void {
		register_block_type(
			WPK_SLIDER_PATH . 'block.json',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Server-side render callback.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string HTML output.
	 */
	public function render( array $attributes ): string {
		$id = absint( $attributes['sliderId'] ?? 0 );
		if ( $id <= 0 ) {
			return '';
		}

		return ( new SliderRenderer() )->render( $id );
	}
}
