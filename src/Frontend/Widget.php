<?php
/**
 * WP_Widget subclass for embedding a slider.
 *
 * @package WPKoumbit\Slider
 */

namespace WPKoumbit\Slider\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Classic widget that embeds a slider by ID.
 *
 * @since 1.0.0
 */
class Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'wpk_slider_widget',
			esc_html__( 'Koumbit Slider', 'wp-koumbit-slider' ),
			array(
				'description' => esc_html__( 'Display a slider by selecting its title.', 'wp-koumbit-slider' ),
				'classname'   => 'wpk-slider-widget',
			)
		);
	}

	/**
	 * Renders the widget on the frontend.
	 *
	 * @param array<string,mixed> $args     Widget display args (before/after_widget, etc.)
	 * @param array<string,mixed> $instance Saved widget settings.
	 */
	public function widget( $args, $instance ): void {
		$slider_id = absint( $instance['slider_id'] ?? 0 );
		if ( $slider_id <= 0 ) {
			return;
		}

		$renderer = new SliderRenderer();
		$html     = $renderer->render( $slider_id );
		if ( '' === $html ) {
			return;
		}

		echo wp_kses_post( $args['before_widget'] );

		if ( ! empty( $instance['title'] ) ) {
			echo wp_kses_post( $args['before_title'] )
				. esc_html( apply_filters( 'widget_title', $instance['title'] ) )
				. wp_kses_post( $args['after_title'] );
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — SliderRenderer escapes all output.

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Updates the widget settings.
	 *
	 * @param array<string,mixed> $new_instance
	 * @param array<string,mixed> $old_instance
	 * @return array<string,mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title'     => sanitize_text_field( $new_instance['title'] ?? '' ),
			'slider_id' => absint( $new_instance['slider_id'] ?? 0 ),
		);
	}

	/**
	 * Renders the widget settings form in the admin.
	 *
	 * @param array<string,mixed> $instance
	 */
	public function form( $instance ): void {
		$title     = $instance['title'] ?? '';
		$slider_id = absint( $instance['slider_id'] ?? 0 );

		$sliders = get_posts(
			array(
				'post_type'      => 'wpk_slider',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title (optional):', 'wp-koumbit-slider' ); ?>
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'slider_id' ) ); ?>">
				<?php esc_html_e( 'Slider:', 'wp-koumbit-slider' ); ?>
			</label>
			<select
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'slider_id' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'slider_id' ) ); ?>"
			>
				<option value="0"><?php esc_html_e( '— Select a slider —', 'wp-koumbit-slider' ); ?></option>
				<?php foreach ( $sliders as $s ) : ?>
					<option value="<?php echo esc_attr( (string) $s->ID ); ?>" <?php selected( $slider_id, $s->ID ); ?>>
						<?php echo esc_html( $s->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}
}
