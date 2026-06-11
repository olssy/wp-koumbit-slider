<?php
/**
 * wpk_slider custom post type.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the wpk_slider post type.
 *
 * Sliders are admin-only constructs (no public URLs) — they are displayed
 * exclusively via shortcode, widget, or block.
 *
 * @since 1.0.0
 */
class SliderPostType {

	const POST_TYPE = 'wpk_slider';

	/**
	 * Registers the CPT and list-table column hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'manage_wpk_slider_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_wpk_slider_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	public function register_post_type(): void {
		$labels = array(
			'name'               => esc_html__( 'Sliders', 'wp-koumbit-slider' ),
			'singular_name'      => esc_html__( 'Slider', 'wp-koumbit-slider' ),
			'add_new'            => esc_html__( 'Add New', 'wp-koumbit-slider' ),
			'add_new_item'       => esc_html__( 'Add New Slider', 'wp-koumbit-slider' ),
			'edit_item'          => esc_html__( 'Edit Slider', 'wp-koumbit-slider' ),
			'new_item'           => esc_html__( 'New Slider', 'wp-koumbit-slider' ),
			'view_item'          => esc_html__( 'View Slider', 'wp-koumbit-slider' ),
			'search_items'       => esc_html__( 'Search Sliders', 'wp-koumbit-slider' ),
			'not_found'          => esc_html__( 'No sliders found.', 'wp-koumbit-slider' ),
			'not_found_in_trash' => esc_html__( 'No sliders in trash.', 'wp-koumbit-slider' ),
			'menu_name'          => esc_html__( 'Sliders', 'wp-koumbit-slider' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false, // Managed by AdminMenu.
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * Adds a Shortcode column to the list table.
	 *
	 * @param array<string,string> $columns
	 * @return array<string,string>
	 */
	public function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['wpk_shortcode'] = esc_html__( 'Shortcode', 'wp-koumbit-slider' );
				$new['wpk_slides']    = esc_html__( 'Slides', 'wp-koumbit-slider' );
			}
		}
		return $new;
	}

	/**
	 * Renders custom column cells.
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( 'wpk_shortcode' === $column ) {
			echo '<code>[wpk_slider id=&quot;' . esc_attr( (string) $post_id ) . '&quot;]</code>';
		}

		if ( 'wpk_slides' === $column ) {
			$slides = json_decode( get_post_meta( $post_id, '_wpk_slider_slides', true ) ?: '[]', true );
			echo esc_html( is_array( $slides ) ? count( $slides ) : 0 );
		}
	}
}
