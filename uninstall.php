<?php
/**
 * Uninstall routine — permanently removes all plugin data.
 *
 * @package WPKoumbit\Slider
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin option.
delete_option( 'wpk_slider_menu_location' );

// Remove all wpk_slider posts and their meta.
global $wpdb;

$slider_ids = $wpdb->get_col(
	"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wpk_slider'"
);

foreach ( $slider_ids as $id ) {
	wp_delete_post( (int) $id, true );
}
