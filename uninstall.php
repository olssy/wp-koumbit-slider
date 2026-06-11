<?php
/**
 * Uninstall routine — permanently removes all plugin data.
 *
 * @package WPKoumbit\Slider
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpk_slider_menu_location' );
// Clean up Swiper URL overrides if an admin ever set them via options (legacy from v1.1.0).
delete_option( 'wpk_slider_swiper_js_url' );
delete_option( 'wpk_slider_swiper_css_url' );

global $wpdb;

// $wpdb->prepare() used even for a hardcoded type value, per project SQL standard.
$slider_ids = $wpdb->get_col(
	$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'wpk_slider' )
);

foreach ( $slider_ids as $id ) {
	wp_delete_post( (int) $id, true );
}
