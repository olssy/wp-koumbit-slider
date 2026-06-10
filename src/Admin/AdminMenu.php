<?php
/**
 * Admin menu registration.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider\Admin;

use WPKoumbit\Slider\PostType\SliderPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Places the Sliders CPT list under the Koumbit Suite menu (default) or
 * as a top-level item, based on the wpk_slider_menu_location option.
 *
 * @since 1.0.0
 */
class AdminMenu {

	const PAGE_SLUG = 'edit.php?post_type=wpk_slider';

	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	public function register_menu(): void {
		$location = get_option( 'wpk_slider_menu_location', 'suite' );

		switch ( $location ) {
			case 'main':
				add_menu_page(
					esc_html__( 'Sliders', 'wp-koumbit-slider' ),
					esc_html__( 'Sliders', 'wp-koumbit-slider' ),
					'edit_posts',
					self::PAGE_SLUG,
					'',
					'dashicons-images-alt2',
					26
				);
				break;

			case 'tools':
				add_submenu_page(
					'tools.php',
					esc_html__( 'Sliders', 'wp-koumbit-slider' ),
					esc_html__( 'Sliders', 'wp-koumbit-slider' ),
					'edit_posts',
					self::PAGE_SLUG
				);
				break;

			case 'suite':
			default:
				global $menu;
				$suite_exists = false;
				foreach ( (array) $menu as $item ) {
					if ( isset( $item[2] ) && 'koumbit-suite' === $item[2] ) {
						$suite_exists = true;
						break;
					}
				}

				if ( ! $suite_exists ) {
					add_menu_page(
						esc_html__( 'Koumbit Suite', 'wp-koumbit-slider' ),
						esc_html__( 'Koumbit', 'wp-koumbit-slider' ),
						'edit_posts',
						'koumbit-suite',
						'__return_null',
						'dashicons-screenoptions',
						80
					);
				}

				add_submenu_page(
					'koumbit-suite',
					esc_html__( 'Sliders', 'wp-koumbit-slider' ),
					esc_html__( 'Sliders', 'wp-koumbit-slider' ),
					'edit_posts',
					self::PAGE_SLUG
				);

				remove_submenu_page( 'koumbit-suite', 'koumbit-suite' );
				break;
		}
	}
}
